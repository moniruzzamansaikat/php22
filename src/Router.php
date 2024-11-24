<?php

namespace Php22;

use ReflectionMethod;

class Router
{
    private $routes = [];
    private $namedRoutes = [];
    private $currentGroup = [];
    private $middleware = [];
    private $fallback;
    private $routeCache = [];

    // New class properties for flexibility
    private $basePath = '/';
    private $controllerNamespace = '';
    private $requestMethod;
    private $requestUri;
    private $globalMiddleware = [];

    /**
     * Set the base path for the router.
     *
     * @param string $basePath
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/') . '/';
    }

    /**
     * Set the default namespace for controllers.
     *
     * @param string $namespace
     */
    public function setControllerNamespace(string $namespace)
    {
        $this->controllerNamespace = rtrim($namespace, '\\') . '\\';
    }

    /**
     * Set the request method manually (useful for testing).
     *
     * @param string $method
     */
    public function setRequestMethod(string $method)
    {
        $this->requestMethod = strtoupper($method);
    }

    /**
     * Set the request URI manually (useful for testing).
     *
     * @param string $uri
     */
    public function setRequestUri(string $uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * Add global middleware to be applied to all routes.
     *
     * @param array $middlewareClasses
     */
    public function setGlobalMiddleware(array $middlewareClasses)
    {
        $this->globalMiddleware = $middlewareClasses;
    }

    /**
     * Add a route.
     *
     * @param string|array $methods HTTP method(s) (e.g., 'GET', ['GET', 'POST']).
     * @param string $uri The URI path (e.g., /users/{id}/delete).
     * @param callable|array $action Callback or [Controller, method].
     * @param string|null $name The name of the route.
     * @param array $middleware Middleware to apply to the route.
     */
    public function addRoute($methods, string $uri, $action, string $name = null, array $middleware = [])
    {
        $methods = (array)$methods;
        $uri = $this->basePath . ltrim($this->applyGroupPrefix($uri), '/');
        $middleware = array_merge($this->middleware, $middleware);

        $route = [
            'methods' => $methods,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware,
            'regex' => $this->convertUriToRegex($uri),
            'parameters' => $this->extractParameters($uri),
        ];

        $this->routes[] = $route;

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * Dispatch the router.
     */
    public function dispatch()
    {
        $requestUri = $this->requestUri ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $requestUri = '/' . trim(str_replace($scriptName, '', $requestUri), '/');

        $requestMethod = $this->requestMethod ?? $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if (!in_array($requestMethod, $route['methods'])) {
                continue;
            }

            if (preg_match($route['regex'], $requestUri, $matches)) {
                $params = array_intersect_key($matches, array_flip($route['parameters']));

                // Apply global middleware
                $allMiddleware = array_merge($this->globalMiddleware, $route['middleware']);

                // Apply middleware
                foreach ($allMiddleware as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    if (method_exists($middleware, 'handle')) {
                        $response = $middleware->handle($params);
                        if ($response !== null) {
                            echo $response;
                            return;
                        }
                    }
                }

                if (is_callable($route['action'])) {
                    echo call_user_func_array($route['action'], $params);
                } elseif (is_array($route['action'])) {
                    $this->dispatchController($route['action'], $params);
                }
                return;
            }
        }

        if ($this->fallback) {
            call_user_func($this->fallback);
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }

    /**
     * Convert URI pattern to regex.
     *
     * @param string $uri
     * @return string
     */
    private function convertUriToRegex(string $uri): string
    {
        // Escape special regex characters, except for braces and slashes
        $escapedUri = preg_replace('/[.\\+*?[^\\]$()|]/', '\\\\$0', $uri);

        // Replace parameter placeholders with regex patterns
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)(:([^}]+))?\}/', function ($matches) {
            $paramName = $matches[1];
            $paramPattern = isset($matches[3]) ? $matches[3] : '[^/]+';
            return '(?P<' . $paramName . '>' . $paramPattern . ')';
        }, $escapedUri);

        return '#^' . $pattern . '$#';
    }

    /**
     * Extract parameter names from URI.
     *
     * @param string $uri
     * @return array
     */
    private function extractParameters(string $uri): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)(:[^\}]+)?\}/', $uri, $matches);
        return $matches[1];
    }

    /**
     * Dispatch a controller and method, resolving dependencies dynamically.
     *
     * @param array $action [Controller, method].
     * @param array $params Parameters extracted from the URL.
     * @throws \Exception
     */
    private function dispatchController(array $action, array $params)
    {
        [$controllerClass, $method] = $action;

        if (strpos($controllerClass, '\\') === false) {
            $controllerClass = $this->controllerNamespace . $controllerClass;
        }

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller class {$controllerClass} does not exist.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \Exception("Method {$method} does not exist in controller {$controllerClass}.");
        }

        // Use Reflection to resolve method parameters
        $reflection = new ReflectionMethod($controller, $method);
        $dependencies = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if (isset($params[$name])) {
                // Use parameters from the route
                $dependencies[] = $this->filterParameter($params[$name], $parameter);
            } elseif ($type && !$type->isBuiltin()) {
                // Attempt to resolve class dependencies
                $className = $type->getName();
                if (class_exists($className)) {
                    $dependencies[] = new $className();
                } else {
                    throw new \Exception("Cannot resolve class {$className} for parameter \${$name}.");
                }
            } elseif ($parameter->isDefaultValueAvailable()) {
                // Use default value if available
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception("Cannot resolve parameter '{$name}' for method {$method}.");
            }
        }

        // Call the controller method with resolved dependencies
        echo $reflection->invokeArgs($controller, $dependencies);
    }

    /**
     * Filter parameter value based on reflection parameter type.
     *
     * @param mixed $value
     * @param \ReflectionParameter $parameter
     * @return mixed
     */
    private function filterParameter($value, \ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        if ($type) {
            $typeName = $type->getName();
            settype($value, $typeName);
        }
        return $value;
    }

    /**
     * Apply group prefix to URI.
     *
     * @param string $uri
     * @return string
     */
    private function applyGroupPrefix(string $uri): string
    {
        if (!empty($this->currentGroup['prefix'])) {
            return rtrim($this->currentGroup['prefix'], '/') . '/' . ltrim($uri, '/');
        }
        return $uri;
    }

    /**
     * Define a fallback route.
     *
     * @param callable $action
     */
    public function fallback(callable $action)
    {
        $this->fallback = $action;
    }

    /**
     * Define a route group.
     *
     * @param array $attributes
     * @param callable $callback
     */
    public function group(array $attributes, callable $callback)
    {
        $parentGroup = $this->currentGroup;
        $this->currentGroup = array_merge($this->currentGroup, $attributes);

        if (isset($attributes['middleware'])) {
            $this->middleware = array_merge($this->middleware, (array)$attributes['middleware']);
        }

        $callback($this);

        $this->currentGroup = $parentGroup;
        $this->middleware = [];
    }

    /**
     * Generate a URL for a named route.
     *
     * @param string $name
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("No route named {$name}.");
        }

        $uri = $this->namedRoutes[$name]['uri'];

        foreach ($params as $key => $value) {
            $uri = preg_replace('/\{' . $key . '(:[^\}]+)?\}/', $value, $uri);
        }

        // Remove any optional parameters not provided
        $uri = preg_replace('/\{[a-zA-Z0-9_]+(\:[^\}]+)?\}/', '', $uri);

        return $uri;
    }

    // Shortcut methods for adding routes with specific HTTP methods

    public function get(string $uri, $action, string $name = null, array $middleware = [])
    {
        $this->addRoute('GET', $uri, $action, $name, $middleware);
    }

    public function post(string $uri, $action, string $name = null, array $middleware = [])
    {
        $this->addRoute('POST', $uri, $action, $name, $middleware);
    }

    public function put(string $uri, $action, string $name = null, array $middleware = [])
    {
        $this->addRoute('PUT', $uri, $action, $name, $middleware);
    }

    public function delete(string $uri, $action, string $name = null, array $middleware = [])
    {
        $this->addRoute('DELETE', $uri, $action, $name, $middleware);
    }

    public function patch(string $uri, $action, string $name = null, array $middleware = [])
    {
        $this->addRoute('PATCH', $uri, $action, $name, $middleware);
    }

    public function any(string $uri, $action, string $name = null, array $middleware = [])
    {
        $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], $uri, $action, $name, $middleware);
    }
}
