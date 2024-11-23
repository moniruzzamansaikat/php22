<?php

namespace Php22;

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
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['uri'] === $requestUri && $route['method'] === $requestMethod) {
                if (is_callable($route['action'])) {
                    call_user_func($route['action']);
                } elseif (is_array($route['action'])) {
                    $controller = new $route['action'][0]();
                    $method = $route['action'][1];
                    call_user_func([$controller, $method]);
                }
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }
}
