<?php

require_once __DIR__ . "/../app/Core/Logger.php";

function runScript() {
    $host = $_ENV["APP_HOSTNAME"] ?? "localhost";
    $port = $_ENV["APP_PORT"] ?? 8000;
    
    exec("php -S {$host}:{$port} index.php");
}