<?php

require_once __DIR__ . "/Logger.php";

class Router
{
    private $routes = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function get($path, $callback)
    {
        $path = trim($path, "/");
        $this->routes["GET"][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $path = trim($path, "/");
        $this->routes["POST"][$path] = $callback;
    }

    public function patch($path, $callback)
    {
        $path = trim($path, "/");
        $this->routes["PATCH"][$path] = $callback;
    }

    public function delete($path, $callback)
    {
        $path = trim($path, "/");
        $this->routes["DELETE"][$path] = $callback;
    }

    public function resolve()
    {
        
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route => $callback) {
            $route = trim($route, '/');

            $pattern = preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $route);
            $pattern = "#^$pattern$#";

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                if (is_array($callback)) {
                    [$class, $action] = $callback;

                    call_user_func_array([new $class($this->pdo), $action], $matches);
                } else {
                    call_user_func_array($callback, $matches);
                }

                return;
            }
        }

        http_response_code(404);
        echo json_encode(['message' => 'Not Found']);
    }
}