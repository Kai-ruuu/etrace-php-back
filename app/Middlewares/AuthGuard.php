<?php

require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../../utils/AuthToken.php";

class AuthGuard
{
    public static function run()
    {
        $token = $_COOKIE["token"] ?? null;

        if (!$token) {
            Response::json(["message" => "Not authenticated."], 401);
        }
            
        $result = AuthToken::decode($token);
        
        if (!$result) {
            Response::json(["message" => "Invalid session."], 400);
        }

        return $result;
    }
}