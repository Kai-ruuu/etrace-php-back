<?php

require_once __DIR__ . "/Logger.php";

class Router
{
    protected $pdo;
    private $routes = [];
    private $rateLimitConfig = [
        'max_requests' => 60,
        'window'       => 60,
    ];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function setRateLimit(int $maxRequests, int $windowSeconds): void
    {
        $this->rateLimitConfig = [
            'max_requests' => $maxRequests,
            'window'       => $windowSeconds,
        ];
    }

    private function getRateLimitKey(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'unknown';

        $ip = trim(explode(',', $ip)[0]);

        return 'rate_limit_' . md5($ip);
    }

    private function checkRateLimit(): bool
    {
        ['max_requests' => $max, 'window' => $window] = $this->rateLimitConfig;
        $key = $this->getRateLimitKey();
        $now = time();

        $dir  = sys_get_temp_dir() . '/rate_limits';
        $file = "$dir/$key.json";

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : null;

        if (!$data || $now - $data['start'] >= $window) {
            file_put_contents($file, json_encode(['start' => $now, 'count' => 1]), LOCK_EX);
            return true;
        }

        if ($data['count'] >= $max) {
            return false;
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }

    private function sendRateLimitResponse(): void
    {
        ['max_requests' => $max, 'window' => $window] = $this->rateLimitConfig;

        http_response_code(429);
        header('Content-Type: application/json');
        header("Retry-After: $window");
        echo json_encode([
            'message' => 'Too Many Requests',
            'limit'   => $max,
            'window'  => "$window seconds",
        ]);
    }

    public function get($path, $callback)    { $this->addRoute('GET',    $path, $callback); }
    public function post($path, $callback)   { $this->addRoute('POST',   $path, $callback); }
    public function patch($path, $callback)  { $this->addRoute('PATCH',  $path, $callback); }
    public function delete($path, $callback) { $this->addRoute('DELETE', $path, $callback); }

    private function addRoute(string $method, string $path, $callback): void
    {
        $this->routes[$method][trim($path, '/')] = $callback;
    }

    public function resolve()
    {
        if (!$this->checkRateLimit()) {
            $this->sendRateLimitResponse();
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route => $callback) {
            $pattern = '#^' . preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', trim($route, '/')) . '$#';

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