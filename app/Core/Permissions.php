<?php

require_once __DIR__ . "/Logger.php";
require_once __DIR__ . "/Actions.php";
require_once __DIR__ . "/Response.php";
require_once __DIR__ . "/Constants.php";

class Permissions
{
    public static function blockIfExcludes($action, $user, $defaultSysad = false)
    {
        $permissions = [
            "sysad" => [
                Action::CREATE_SCHOOLS,
                Action::CREATE_DEANS,
                Action::CREATE_PSTAFFS,
                Action::READ_SCHOOLS,
                Action::READ_DEANS,
                Action::READ_PSTAFFS,
                Action::UPDATE_SCHOOLS,
                Action::ENDIS_DEANS,
                Action::ENDIS_PSTAFFS,
                ],
            "dean" => [
                Action::CREATE_COURSES,
                Action::CREATE_RECORDS,
                Action::CREATE_ALIGNED_OCCUPATIONS,
                Action::READ_COURSES,
                Action::READ_RECORDS,
                Action::READ_OCCUPATIONS,
                Action::READ_ALIGNED_OCCUPATIONS,
                Action::UPDATE_COURSES,
                Action::UPDATE_RECORDS,
                Action::UPDATE_PROFILE,
                Action::DELETE_ALIGNED_OCCUPATIONS,
            ]
        ];

        if ($user["role"] === Role::SYSAD) {
            if ($defaultSysad) {
                $permissions["sysad"][] = Action::CREATE_SYSADS;
                $permissions["sysad"][] = Action::READ_SYSADS;
                $permissions["sysad"][] = Action::ENDIS_SYSADS;
            } else {
                $permissions["sysad"][] = Action::UPDATE_PROFILE;
            }
        }

        if (!in_array($action, $permissions[$user["role"]])) {
            Response::json(["message" => "You are not permitted to perform this action."], 403);
        }
    }
}