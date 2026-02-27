<?php

class Role
{
    public const SYSAD = "sysad";
    public const DEAN = "dean";
    public const PSTAFF = "pstaff";
    public const COMPANY = "company";
    public const ALUMNI = "alumni";

    public static function core() {
        return [self::SYSAD, self::DEAN, self::PSTAFF];
    }

    public static function nonCore() {
        return [self::COMPANY, self::ALUMNI];
    }

    public static function all() {
        return [self::SYSAD, self::DEAN, self::PSTAFF, self::COMPANY, self::ALUMNI];
    }
}