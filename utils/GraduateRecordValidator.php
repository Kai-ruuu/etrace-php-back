<?php

require_once __DIR__ . "/../app/Core/Response.php";

class GraduateRecordValidator
{
    private static $optionalFields = [
        "Middle Name",
    ];
    private static $requiredFields = [
        "Student Number",
        "Birthdate",
        "Birthplace",
        "First Name",
        "Last Name",
        "Gender",
        "Full Address",
        "Contact Number",
    ];

    public static function validate($fieldname)
    {
        if (empty($_FILES[$fieldname]) || $_FILES[$fieldname]['error'] !== UPLOAD_ERR_OK) {
            Response::json(["error" => "Graduate record is required."], 400);
        }

        $file = file($_FILES[$fieldname]["tmp_name"]);
        $rows = array_map("str_getcsv", $file);

        // handle empty trailing columns
        $headers = array_shift($rows);
        $headers = array_map('trim', $headers);

        // remove UTF-8 BOM
        $headers = array_map(function($h) {
            $h = str_replace("\xEF\xBB\xBF", '', $h);
            return trim($h);
        }, $headers);
        $validIndices = array_keys(array_filter($headers, fn($h) => trim($h) !== ''));
        $headers = array_values(array_intersect_key($headers, array_flip($validIndices)));

        $rows = array_map(function($row) use ($validIndices) {
            $filtered = array_intersect_key($row, array_flip($validIndices));
            return array_values($filtered);
        }, $rows);

        $rows = array_filter($rows, fn($row) => !empty(array_filter($row, fn($col) => trim($col) !== '')));
        $data = array_map(fn($row) => array_combine($headers, $row), $rows);

        // all fields (required + optional) must be present as headers
        $allFields = array_unique(array_merge(self::$requiredFields, self::$optionalFields));
        $missingFields = array_filter($allFields, fn($field) => !in_array($field, $headers));

        if (!empty($missingFields)) {
            $joinedMissingFields = implode(", ", array_map(fn($f) => "'{$f}'", $missingFields));
            Response::json(["error" => "Graduate record requires these fields to be present: {$joinedMissingFields}."], 422);
        }

        // validate row values — only required fields must have values
        $errMsgs = [];
        $row = 1;

        foreach ($data as $d) {
            foreach ($d as $column => $value) {
                if (in_array($column, self::$requiredFields) && empty(trim($value))) {
                    $errMsgs[] = "Missing value in row {$row} for '{$column}'.";
                }

                if ($column === "Birthdate" && !empty(trim($value)) && !self::isDateFormatValid($value)) {
                    $errMsgs[] = "Invalid date format in row {$row} for '{$column}'. Expected m/d/yyyy without leading zeros.";
                }
            }

            $row++;
        }

        if (!empty($errMsgs)) {
            Response::json(["errors" => $errMsgs], 422);
        }

        return $data;
    }

    private static function isDateFormatValid($dateString)
    {
        $d = DateTime::createFromFormat('n/j/Y', $dateString);
        return $d && $d->format('n/j/Y') === $dateString;
    }
}