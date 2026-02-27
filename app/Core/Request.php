<?php

class Request
{
    public static function body()
    {
        return json_decode(file_get_contents("php://input"), true) ?? [];
    }

    public static function fromBody($key)
    {
        return self::body()[$key] ?? null;
    }

    public static function fromQuery($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}