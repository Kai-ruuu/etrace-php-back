<?php

function runScript() {
    $host = "0.0.0.0";
    $port = $_ENV["APP_PORT"] ?? 8000;
    
    exec("php -S {$host}:{$port} index.php");
}