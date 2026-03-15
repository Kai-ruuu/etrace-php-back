<?php

require_once __DIR__ . "/../App/Core/Logger.php";

class Storage
{
    static $uploadsDir = __DIR__ . "/../uploads";
    static $subdirs = [
        "tmp"              => "/tmp",
        "graduate_records" => "/dean/graduate_records",
        "logo" => "/company/logo",
        "profile" => "/company/profile",
        "permit" => "/company/permit",
        "sec" => "/company/sec",
        "dti_cda" => "/company/dti_cda",
        "reg_of_est" => "/company/reg_of_est",
        "reg_philjobnet" => "/company/reg_philjobnet",
        "cert_from_dole" => "/company/cert_from_dole",
        "cert_no_case" => "/company/cert_no_case",
        "profile_picture" => "/alumni/profile_picture",
        "cv" => "/alumni/cv",
    ];

    public static function dest($dirkey)
    {
        return self::$uploadsDir . self::$subdirs[$dirkey];
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
        $filePath = $sourceDir . "/" . $file;

        if (!is_dir($sourceDir)) {
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