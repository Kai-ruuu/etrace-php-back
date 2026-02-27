<?php

class Response
{
    public static function json($data, $statusCode = null) {
        header("Content-Type: application/json");

        if ($statusCode) {
            http_response_code($statusCode);
        }
        
        echo json_encode($data);
        exit();
    }
}