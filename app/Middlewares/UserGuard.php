<?php

require_once __DIR__ . "/AuthGuard.php";
require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Permissions.php";

class UserGuard
{
    public static function run($pdo, $forRoles = [], $action = null)
    {
        [$id, $role] = AuthGuard::run();

        if (!in_array($role, $forRoles)) {
            Response::json(["message" => "Not authorized."], 401);
        }

        $userModel = new User($pdo);
        
        switch ($role) {
            case Role::SYSAD:
                $user = $userModel->getSysadById($id);
                
                if ($action !== null) {
                    Permissions::blockIfExcludes($action, $user, $user["default_sysad"]);
                }

                return $user;
            case Role::DEAN:
                $user = $userModel->getDeanById($id);
                
                if ($action !== null) {
                    Permissions::blockIfExcludes($action, $user, false);
                }
                
                return $user;
            case Role::PSTAFF:
                $user = $userModel->getPstaffById($id);
                
                if ($action !== null) {
                    Permissions::blockIfExcludes($action, $user, false);
                }
                
                return $user;
            case Role::COMPANY:
                $user = $userModel->getCompanyById($id);
                
                if ($action !== null) {
                    Permissions::blockIfExcludes($action, $user, false);
                }
                
                return $user;
            case Role::ALUMNI:
                $user = $userModel->getAlumniById($id);

                if ($action !== null) {
                    Permissions::blockIfExcludes($action, $user, false);
                }
                
                return $user;
        }
    }
}