<?php

namespace Php22;

use ReflectionMethod;

class Router
{
    private $routes = [];

    /**
     * Add a route.
     *
     * @param string $method HTTP method (e.g., GET, POST).
     * @param string $uri The URI path (e.g., /users).
     * @param callable|array $action Callback or [Controller, method].
     */
    public function addRoute(string $method, string $uri, $action)
    {
        $this->routes[] = compact('method', 'uri', 'action');
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch()
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Clean up query parameters
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['uri'] === $requestUri && $route['method'] === $requestMethod) {
                if (is_callable($route['action'])) {
                    call_user_func($route['action']);
                } elseif (is_array($route['action'])) {
                    $this->dispatchController($route['action']);
                }
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    /**
     * Dispatch a controller and method, resolving dependencies dynamically.
     *
     * @param array $action [Controller, method].
     * @throws \Exception
     */
    private function dispatchController(array $action)
    {
        [$controllerClass, $method] = $action;

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller class {$controllerClass} does not exist.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \Exception("Method {$method} does not exist in controller {$controllerClass}.");
        }

        // Use Reflection to resolve method parameters
        $reflection = new ReflectionMethod($controller, $method);
        $parameters = $reflection->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $parameterType = $parameter->getType();

            if ($parameterType && class_exists($parameterType->getName())) {
                // Instantiate the dependency dynamically
                $dependencies[] = new ($parameterType->getName())();
            } elseif ($parameter->isDefaultValueAvailable()) {
                // Use default value if available
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception("Cannot resolve dependency {$parameter->getName()} for method {$method}.");
            }
        }

        // Call the controller method with resolved dependencies
        $reflection->invokeArgs($controller, $dependencies);
    }
}
