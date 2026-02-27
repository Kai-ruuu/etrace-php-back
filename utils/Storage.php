<?php

require_once __DIR__ . "/../App/Core/Logger.php";

class Storage
{
    static $uploadsDir = __DIR__ . "/../uploads";
    static $subdirs = [
        "tmp"              => "/tmp",
        "graduate_records" => "/dean/graduate_records",
    ];

    public static function dest($dirkey)
    {
        return self::$uploadsDir . "/" . self::$subdirs[$dirkey];
    }
    
    public static function set()
    {
        foreach (self::$subdirs as $key => $subdir) {
            $fullPath = self::$uploadsDir . $subdir;

            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0775, true);
                Logger::info($subdir . " has been created.");
            }
        }
    }

    public static function reset()
    {
        if (is_dir(self::$uploadsDir)) {
            self::deleteDir(self::$uploadsDir);
            Logger::info("Storage has been reset.");
        }
    }

    public static function delete($sourceDir, $file)
    {
        $dir = self::$uploadsDir . $sourceDir;
        $filePath = $dir . "/" . $file;

        if (!is_dir($dir)) {
            Logger::error(Logger::ERR_FILE_SYSTEM, "Directory {$dir} does not exist.");
            return false;
        }

        if (!file_exists($filePath)) {
            Logger::error(Logger::ERR_FILE_SYSTEM, "File {$filePath} does not exist.");
            return false;
        }

        unlink($filePath);
        return true;
    }

    public static function deleteDir($path)
    {
        $files = glob($path . '/*');

        foreach ($files as $file) {
            is_dir($file) ? self::deleteDir($file) : unlink($file);
        }

        rmdir($path);
    }
}