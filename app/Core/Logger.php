<?php

class Logger
{
    public const ERR_AUTH = "auth_error";
    public const ERR_VALUE = "value_error";
    public const ERR_DATABASE = "database_error";
    public const ERR_VALIDATION = "validation_error";
    public const ERR_FILE_SYSTEM = "err_file_system";
    public const ERR_MAILING_SERVICE = "err_mailing_service";
    
    private static string $logDir = __DIR__ . "/../../logs";

    public static function error(string $err, string $message): void
    {
        self::writeLog("ERROR", $err, $message);
    }

    public static function info(string $message): void
    {
        self::writeLog("INFO", "INFO", $message);
    }

    public static function warning(string $message): void
    {
        self::writeLog("WARNING", "WARNING", $message);
    }

    public static function debug(string $message): void
    {
        if (self::isDev()) {
            self::writeLog("DEBUG", "DEBUG", $message);
        }
    }

    private static function writeLog(string $level, string $type, string $message): void
    {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0777, true);
        }

        $time = date("Y-m-d H:i:s");
        $logMessage = "[$time] [$level] [$type] $message" . PHP_EOL;
        $fileName = self::$logDir . "/log_" . date("Y-m-d") . ".log";

        file_put_contents($fileName, $logMessage, FILE_APPEND | LOCK_EX);
        error_log($logMessage);
    }

    private static function isDev(): bool
    {
        return $_ENV["APP_ENV"] === "development";
    }

    public static function logArray(string $label, array $data, int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);
        error_log("{$indent}--- $label ---");
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                self::logArray($key, $value, $depth + 1);
            } else {
                error_log("{$indent}  $key: $value");
            }
        }
        error_log("{$indent}--- end $label ---");
    }
}