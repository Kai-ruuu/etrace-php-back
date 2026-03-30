<?php

class AlumniPreVerifier
{
    public static function parseRecordCsv(string $recordFilePath): array
    {
        $csvFile = file($recordFilePath);
        $rows = array_map("str_getcsv", $csvFile);
        $headers = array_shift($rows);
        $headers = array_map('trim', $headers);
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

        return $data;
    }

    public static function formatCreateInfo(array $createInfo): array
    {

        return [
            "Student Number" => $createInfo["student_number"] ?? "",
            "Birthdate"      => $createInfo["birth_date"]->format('n/j/Y'),
            "Birthplace"     => $createInfo["birth_place"],
            "First Name"     => $createInfo["first_name"],
            "Middle Name"    => $createInfo["middle_name"] ?? "",
            "Last Name"      => $createInfo["last_name"],
            "Gender"         => $createInfo["gender"],
            "Full Address"   => $createInfo["address"],
            "Contact Number" => $createInfo["phone_number"],
        ];
    }
    
    public static function preverify(string $recordFilePath, array $createInfo): string
    {
        if (!file_exists($recordFilePath)) {
            return "Pending";
        }

        $verifyThreshold = 0.75;
        $alum = self::formatCreateInfo($createInfo);
        $rows = self::parseRecordCsv($recordFilePath);

        foreach ($rows as $row) {
            $rowScore = 0;
            $rowMaxScore = 9;

            foreach ($alum as $key => $value) {
                $pVals = explode(" ", strtolower($value));
                $rVals = explode(" ", strtolower($row[$key]));
                $matched = !empty(array_intersect($pVals, $rVals)) ? 1 : 0;
                $rowScore += $matched;
                error_log("{$key}: [{$value}] vs [{$row[$key]}] => {$matched}");
            }
            
            error_log("Row Score: " . $rowScore / $rowMaxScore);
            
            if ($rowScore / $rowMaxScore >= $verifyThreshold) {
                return "Verified";
            }
        }

        return "Pending";
    }
}