<?php

require_once __DIR__ . "/Response.php";
require_once __DIR__ . "/Logger.php";

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

class Validator
{
    public const INTEGER = "integer";
    public const FLOAT = "float";
    public const TEXT = "text";
    public const EMAIL = "email";
    public const BOOLEAN = "boolean";
    
    public static function validateInteger($label, $value, $customMsg = null)
    {
        $defaultMsg = "{$label} should be an integer.";
        $msg = $customMsg ?? $defaultMsg;
        
        if (!is_numeric($value) || intval($value) != $value) {
            http_response_code(422);
            Logger::debug(Logger::ERR_VALIDATION, $msg);
            Response::json(["message" => $msg], 422);
        }

        return (int) $value;
    }
        
    public static function validateFloat($label, $value, $customMsg = null)
    {
        $defaultMsg = "{$label} should be a float.";
        $msg = $customMsg ?? $defaultMsg;
        
        if (!is_numeric($value) || strpos((string)$value, ".") === false) {
            http_response_code(422);
            Logger::debug(Logger::ERR_VALIDATION, $msg);
            Response::json(["message" => $msg], 422);
        }

        return (float) $value;
    }

    public static function validateText($label, $value, $lenRange = null)
    {
        $isValid = true;
        $value = trim($value ?? "");
        $msg = "";
        
        if (!$lenRange) {
            return;
        }

        [$minLen, $maxLen] = explode("-", $lenRange);
        $minLen = (int) $minLen;
        $maxLen = (int) $maxLen;

        if ($minLen > 0 && empty($value)) {
            $isValid = false;
            $msg = "{$label} is required.";
        } else if ($minLen === 0 && strlen($value) > 0) {
            if (strlen($value) > $maxLen) {
                $isValid = false;
                $msg = "{$label} must only have $maxLen characters.";
            }

            return $value;
        } else if ($minLen > 0 && strlen($value) < $minLen) {
            $isValid = false;
            $msg = "{$label} must have at least $minLen characters.";
        } else if ($maxLen > 0 && strlen($value) > $maxLen) {
            $isValid = false;
            $msg = "{$label} must only have $maxLen characters.";
        }

        if (!$isValid) {
            Logger::debug(Logger::ERR_VALIDATION, $msg);
            Response::json(["message" => $msg], 422);
        }

        return $value;
    }

    public static function validateEmail($label, $value, $_ = null)
    {
        $msg = "";
        
        if (strtolower($label) === "email") {
            $msg = "Invalid email.";
        } else {
            $msg = "{$label} is not a valid email.";
        }
        
        $validator = new EmailValidator();
        $isValid = $validator->isValid($value, new RFCValidation());

        if (!$isValid) {
            Logger::debug(Logger::ERR_VALIDATION, $msg);
            Response::json(["message" => $msg], 422);
        }

        return $value;
    }

    public static function validateBoolean($label, $value, $customMsg = null)
    {
        $msg = $customMsg ?? "{$label} should be a boolean value.";

        $trueVals = ["true", "1", "yes", "high"];
        $falseVals = ["false", "0", "no", "low"];

        if (!in_array(strtolower($value), $trueVals) && !in_array(strtolower($value), $falseVals)) {
            Logger::debug(Logger::ERR_VALIDATION, $msg);
            Response::json(["message" => $msg], 422);
        }

        return in_array(strtolower($value), $trueVals);
    }
            
    public static function batchValidate($info = [])
    {
        if (!is_array($info)) {
            Logger::debug(Logger::ERR_VALUE, "\$info should be an array of validation information.");
            http_response_code(500);
            Response::json(["message" => "Server error."]);
            die();
        }

        $validated = [];

        foreach($info as $validation) {
            if (count($validation) < 4) {
                $validation[] = null;
            }

            [$type, $label, $value, $optionalInfo] = $validation;
            
            switch ($type) {
                case self::INTEGER:
                    $validated[$label] = Validator::validateInteger($label, $value, $optionalInfo);
                    break;
                case self::FLOAT:
                    $validated[$label] = Validator::validateFloat($label, $value, $optionalInfo);
                    break;
                case self::TEXT:
                    $validated[$label] = Validator::validateText($label, $value, $optionalInfo);
                    break;
                case self::EMAIL:
                    $validated[$label] = Validator::validateEmail($label, $value, $optionalInfo);
                    break;
                case self::BOOLEAN:
                    $validated[$label] = Validator::validateBoolean($label, $value, $optionalInfo);
                    break;
            }
        }

        return $validated;
    }
}