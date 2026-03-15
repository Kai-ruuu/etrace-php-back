<?php

require_once __DIR__ . "/../Core/Cookie.php";
require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ . "/../Middlewares/AuthGuard.php";
require_once __DIR__ . "/../../utils/Password.php";
require_once __DIR__ . "/../../utils/AuthToken.php";

class AuthController
{   
    public function __construct($pdo)
    {
        $this->model = new User($pdo);
    }

    private function getFullUser($user)
    {
        switch ($user["role"]) {
            case Role::SYSAD:
                return $this->model->getSysadById($user["id"]);
            case Role::DEAN:
                return $this->model->getDeanById($user["id"]);
            case Role::PSTAFF:
                return $this->model->getPstaffById($user["id"]);
            case Role::COMPANY:
                return $this->model->getCompanyById($user["id"]);
            case Role::ALUMNI:
                return $this->model->getAlumniById($user["id"]);
        }
    }
    
    public function login()
    {
        $email = Request::fromBody("email");
        $password = Request::fromBody("password");
        $validated = Validator::batchValidate([
            [Validator::EMAIL, "email", $email],
            [Validator::TEXT, "password", $password, "8-64"],
        ]);
        $user = $this->model->getByEmail($validated["email"]);

        if (!$user || !Password::correct($validated["password"], $user["password_hash"])) {
            Response::json(["message" => "Invalid credentials."], 401);
        }

        $authToken = AuthToken::encode($user["id"], $user["role"]);
        $fullUser = $this->getFullUser($user);
        
        Cookie::setAuth($authToken);
        Response::json($fullUser);
    }

    public function logout()
    {
        AuthGuard::run();
        Cookie::unsetAuth();
        Response::json(["message" => "Signed out successfully!"]);
    }
}