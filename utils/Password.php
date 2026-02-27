<?php

class Password
{
    public static function generate($show = false, $len = 8)
    {
        $randPass = substr(base64_encode(random_bytes(16)), 0, $len);
        return self::hash($randPass);
    }
    
    public static function hash($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function correct($password, $hash)
    {
        return password_verify($password, $hash);
    }
}