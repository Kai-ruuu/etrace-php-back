<?php

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . "/app/Core/Router.php";
require_once __DIR__ . "/app/Core/Database.php";

$allowedOrigins = [
    "http://localhost:5173",
    "http://192.168.56.1:5173",
    "http://192.168.1.37:5173",
];

$origin = $_SERVER["HTTP_ORIGIN"] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if (is_file(__DIR__ . $path)) {
    return false;
} 

$config = Database::loadConfig();
$db = new Database($config);
$pdo = $db->connect();

$router = new Router($pdo);

require_once __DIR__ . "/routes.php";
$router->resolve();
