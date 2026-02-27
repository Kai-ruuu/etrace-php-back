<?php

require_once __DIR__ . "/Storage.php";

function normalizeFilename($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9]+/', '-', $name);
    $name = trim($name, '-');
    return $name . '.' . strtolower($ext);
}

class Upload
{
    public function __construct(
        $label,
        $dest,
        $fieldname,
        $allowedMimes = [],
        $required = true,
        $maxSizeMb = 5
    ) {
        $this->label = $label;
        $this->dest = $dest;
        $this->allowedMimes = $allowedMimes;
        $this->required = $required;
        $this->maxSizeMb = $maxSizeMb;
        $this->tmpPath = null;
        $this->errMsg = null;
        $this->finalFilename = null;
        $this->file = null;
        $this->fileRaw = null;

        if (!empty($_FILES[$fieldname]) && $_FILES[$fieldname]['error'] === UPLOAD_ERR_OK) {
            $this->file = $_FILES[$fieldname]['tmp_name'];
            $this->fileRaw = $_FILES[$fieldname];
        }
    }

    public function stage()
    {        
        if ($this->file === null) {
            if ($this->required) {
                $this->errMsg = "{$this->label} is required.";
            }
            return;
        }

        $maxSizeB = $this->maxSizeMb * 1024 * 1024;

        if (filesize($this->file) > $maxSizeB) {
            $this->errMsg = "{$this->label} must not exceed {$this->maxSizeMb} MB.";
            return;
        }

        $this->finalFilename = self::getUniqueName($this->fileRaw['name']);
        $this->tmpPath = Storage::dest("tmp") . "/" . $this->finalFilename;

        if (!move_uploaded_file($this->file, $this->tmpPath)) {
            $this->errMsg = "Failed to stage {$this->label}.";
            return;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($this->tmpPath);

        if (!in_array($mimeType, $this->allowedMimes)) {
            unlink($this->tmpPath);
            $this->tmpPath = null;
            $this->errMsg = "{$this->label} has an invalid file format.";
            return;
        }
    }

    public function commit()
    {
        if ($this->errMsg !== null || $this->tmpPath === null) {
            return;
        }

        $finalPath = $this->dest . "/" . $this->finalFilename;

        if (!rename($this->tmpPath, $finalPath)) {
            $this->errMsg = "Failed to commit {$this->label}.";
        }
    }

    public function rollback()
    {
        if ($this->tmpPath !== null && file_exists($this->tmpPath)) {
            unlink($this->tmpPath);
            $this->tmpPath = null;
        }
    }

    public function hasError()
    {
        return $this->errMsg !== null;
    }

    public function getError()
    {
        return $this->errMsg;
    }

    public function getFilename()
    {
        return $this->finalFilename;
    }

    public static function getUniqueName($rawName)
    {
        $normalizedName = normalizeFilename($rawName);
        $name = pathinfo($normalizedName, PATHINFO_FILENAME);
        $ext = pathinfo($normalizedName, PATHINFO_EXTENSION);
        $uniqueTrail = substr(bin2hex(random_bytes(16)), 0, 8);
        return $name . "-" . $uniqueTrail . "." . $ext;
    }
}

class Uploads
{
    public function __construct($uploads = [])
    {
        $this->uploads = $uploads;
    }

    public function stage()
    {
        foreach ($this->uploads as $upload) {
            $upload->stage();

            if ($upload->hasError()) {
                $this->rollback();
                return;
            }
        }
    }

    public function commit()
    {
        foreach ($this->uploads as $upload) {
            $upload->commit();

            if ($upload->hasError()) {
                $this->rollback();
                return;
            }
        }
    }

    public function rollback()
    {
        foreach ($this->uploads as $upload) {
            $upload->rollback();
        }
    }

    public function hasErrors()
    {
        foreach ($this->uploads as $upload) {
            if ($upload->hasError()) return true;
        }
        
        return false;
    }

    public function getErrors()
    {
        $errors = [];

        foreach ($this->uploads as $upload) {
            if ($upload->hasError()) {
                $errors[] = $upload->getError();
            }
        }

        return $errors;
    }

    public function getFilename($index) {
        if ($index < 0 || $index > count($this->uploads) - 1) {
            return null;
        }

        return $this->uploads[$index]->getFilename();
    }
}