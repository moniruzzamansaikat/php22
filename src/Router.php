<?php

namespace Php22;

use Php22\http\Middleware\CsrfMiddleware;
use ReflectionMethod;

class Router
{
    private $routes = [];
    private $namedRoutes = [];
    private $currentGroup = [];
    private $middleware = [];
    private $fallback;
    private $routeCache = [];

    private $basePath = '/';
    private $controllerNamespace = '';
    private $requestMethod;
    private $requestUri;
    private $globalMiddleware = [CsrfMiddleware::class];

    private $temporaryGroupAttributes = [];

    private $currentController = '';


    public function setBasePath(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/') . '/';
    }

    public function setControllerNamespace(string $namespace)
    {
        $this->controllerNamespace = rtrim($namespace, '\\') . '\\';
    }

    public function setRequestMethod(string $method)
    {
        $this->requestMethod = strtoupper($method);
    }

    public function setRequestUri(string $uri)
    {
        $this->requestUri = $uri;
    }

    public function setGlobalMiddleware(array $middlewareClasses)
    {
        $this->globalMiddleware = $middlewareClasses;
    }

    public function addRoute($methods, string $uri, $action, string $name = null, array $middleware = [])
    {
        $methods = (array)$methods;
        $uri = $this->basePath . ltrim($this->applyGroupPrefix($uri), '/');
        $middleware = array_merge($this->middleware, $middleware);

        if (is_string($action) && $this->currentController) {
            $action = [$this->currentController, $action];
        }

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

    private function extractParameters(string $uri): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)(:[^\}]+)?\}/', $uri, $matches);
        return $matches[1];
    }

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

    private function filterParameter($value, \ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        if ($type) {
            $typeName = $type->getName();
            settype($value, $typeName);
        }
        return $value;
    }

    private function applyGroupPrefix(string $uri): string
    {
        if (!empty($this->currentGroup['prefix'])) {
            return rtrim($this->currentGroup['prefix'], '/') . '/' . ltrim($uri, '/');
        }
        return $uri;
    }

    public function fallback(callable $action)
    {
        $this->fallback = $action;
    }

    public function group(callable $callback)
    {
        $parentGroup = $this->currentGroup;
        $this->currentGroup = array_merge($this->currentGroup, $this->temporaryGroupAttributes);

        if (isset($this->temporaryGroupAttributes['middleware'])) {
            $this->middleware = array_merge($this->middleware, $this->temporaryGroupAttributes['middleware']);
        }

        $this->temporaryGroupAttributes = []; // Reset temporary attributes
        $callback($this);

        $this->currentGroup = $parentGroup;
        $this->middleware = [];
    }

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

    public function controller(string $controllerName)
    {
        $this->currentController = $this->controllerNamespace . rtrim($controllerName, '\\');
        return $this;
    }

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

    public function prefix(string $prefix)
    {
        $this->temporaryGroupAttributes['prefix'] = rtrim($prefix, '/');
        return $this;
    }

    public function middleware($middleware)
    {
        $middleware = (array)$middleware;
        $this->temporaryGroupAttributes['middleware'] = array_merge(
            $this->temporaryGroupAttributes['middleware'] ?? [],
            $middleware
        );
        return $this;
    }
}
