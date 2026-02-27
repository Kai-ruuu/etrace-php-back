<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Models/Profile.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";
require_once __DIR__ . "/../../utils/Password.php";

class ProfileController
{   
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new Profile($pdo);
    }

    public function updateSysad()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::UPDATE_PROFILE);
        $validated = Validator::batchValidate([
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-65"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-65"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-65"],
        ]);
        $profileId = $cUser["profile"]["id"];
        $profile = $this->model->getSysadById($profileId);

        if (!$profile) {
            Response::json(["error" => "Profile not found."], 404);
        }

        $profile["first_name"] = $validated["first name"];
        $profile["middle_name"] = $validated["middle name"];
        $profile["last_name"] = $validated["last name"];
        $tookEffect = $this->model->updateSysadById($profileId, $profile);

        if (!$tookEffect) {
            Response::json(["error" => "Unable to update profile."], 500);
        }

        Response::json([
            "message" => "Profile has been updated.",
            "id" => $cUser["id"]
        ]);
    }

    public function updateDean()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_PROFILE);
        $validated = Validator::batchValidate([
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-65"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-65"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-65"],
        ]);
        $profileId = $cUser["profile"]["id"];
        $profile = $this->model->getDeanById($profileId);

        if (!$profile) {
            Response::json(["error" => "Profile not found."], 404);
        }

        $profile["first_name"] = $validated["first name"];
        $profile["middle_name"] = $validated["middle name"];
        $profile["last_name"] = $validated["last name"];
        $tookEffect = $this->model->updateDeanById($profileId, $profile);

        if (!$tookEffect) {
            Response::json(["error" => "Unable to update profile."], 500);
        }

        Response::json([
            "message" => "Profile has been updated.",
            "id" => $cUser["id"]
        ]);
    }
}