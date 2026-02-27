<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Core/Paginator.php";
require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";
require_once __DIR__ . "/../../utils/Password.php";

class UserController
{   
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new User($pdo);
    }

    public function createSysad()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::CREATE_SYSADS);
        $passwordHash = Password::generate();
        $validated = Validator::batchValidate([
            [Validator::EMAIL, "email", Request::fromBody("email")],
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-50"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-50"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-50"],
        ]);

        if ($this->model->getByEmail($validated["email"])) {
            Response::json(["message" => "System Administrator already exists."], 409);
        }

        $userId = $this->model->createSysad([
            "email" => $validated["email"],
            "password_hash" => $passwordHash,
            "first_name" => $validated["first name"],
            "middle_name" => $validated["middle name"],
            "last_name" => $validated["last name"],
        ]);

        if ($userId === null) {
            Response::json(["message" => "Unable to create system administrator."], 500);
        }

        $user = $this->model->getSysadById($userId);
        Response::json($user, 201);
    }

    public function searchSysads()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::READ_SYSADS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "enabled", Request::fromQuery("enabled", true)],
        ]);
        $paginator = new Paginator($this->pdo, "users", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT
                u.id AS uid,
                u.email AS uemail,
                u.role AS urole,
                u.enabled AS uenabled,
                u.created_at AS ucreated_at,
                u.updated_at AS uupdated_at,
                p.id AS pid,
                p.user_id AS puser_id,
                p.first_name AS pfirst_name,
                p.middle_name AS pmiddle_name,
                p.last_name AS plast_name
            FROM users u
            JOIN sysads p ON p.user_id = u.id
            ",
            "WHERE
                u.role = 'sysad'
                AND u.enabled = ?
                AND (
                    u.email LIKE ? OR
                    p.first_name LIKE ? OR
                    p.middle_name LIKE ? OR
                    p.last_name LIKE ?
                )
            ",
            [$validated["enabled"], "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"],
            [User::class, "format"]
        );
        Response::json($result);
    }

    public function enableSysad($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_SYSADS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "System Administrator does not exist."], 404);
        }
            
        if ($user["email"] === $_ENV["SYSAD_EMAIL"]) {
            Response::json(["message" => "User info should not be modified."], 403);
        }

        $user["enabled"] = true;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to enable system administrator."], 500);
        }    
            
        Response::json([
            "message" => "System administrator has been enabled.",
            "id" => $validatedId,
        ]);
    }

    public function disableSysad($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_SYSADS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "System Administrator does not exist."], 404);
        }
            
        if ($user["email"] === $_ENV["SYSAD_EMAIL"]) {
            Response::json(["message" => "User info should not be modified."], 403);
        }

        $user["enabled"] = false;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to disable system administrator."], 500);
        }    
            
        Response::json([
            "message" => "System administrator has been disabled.",
            "id" => $validatedId,
        ]);
    }

    public function createDean()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::CREATE_DEANS);
        $passwordHash = Password::generate();
        $validated = Validator::batchValidate([
            [Validator::EMAIL, "email", Request::fromBody("email")],
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-50"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-50"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-50"],
            [Validator::INTEGER, "school id", Request::fromBody("school_id")],
        ]);

        if ($this->model->getByEmail($validated["email"])) {
            Response::json(["message" => "Dean already exists."], 409);
        }

        $userId = $this->model->createDean([
            "email" => $validated["email"],
            "password_hash" => $passwordHash,
            "first_name" => $validated["first name"],
            "middle_name" => $validated["middle name"],
            "last_name" => $validated["last name"],
            "school_id" => $validated["school id"],
        ]);

        if ($userId === null) {
            Response::json(["message" => "Unable to create dean."], 500);
        }

        $user = $this->model->getDeanById($userId);
        Response::json($user, 201);
    }

    public function searchDeans()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::READ_DEANS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "school_id", Request::fromQuery("school_id")],
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "enabled", Request::fromQuery("enabled", true)],
        ]);
        $paginator = new Paginator($this->pdo, "users", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT
                u.id AS uid,
                u.email AS uemail,
                u.role AS urole,
                u.enabled AS uenabled,
                u.created_at AS ucreated_at,
                u.updated_at AS uupdated_at,
                p.id AS pid,
                p.school_id AS pschool_id,
                p.user_id AS puser_id,
                p.first_name AS pfirst_name,
                p.middle_name AS pmiddle_name,
                p.last_name AS plast_name,
                s.id AS sid,
                s.name AS sname
            FROM users u
            JOIN deans p ON p.user_id = u.id
            JOIN schools s ON s.id = p.school_id
            ",
            "WHERE
                u.role = 'dean'
                AND u.enabled = ?
                AND p.school_id = ?
                AND (
                    u.email LIKE ? OR
                    p.first_name LIKE ? OR
                    p.middle_name LIKE ? OR
                    p.last_name LIKE ? OR
                    s.name LIKE ?
                )
            ",
            [$validated["enabled"], $validated["school_id"], "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"],
            [User::class, "format"]
        );
        Response::json($result);
    }

    public function enableDean($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_DEANS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "Dean does not exist."], 404);
        }

        $user["enabled"] = true;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to enable dean."], 500);
        }    
            
        Response::json([
            "message" => "Dean has been enabled.",
            "id" => $validatedId,
        ]);
    }

    public function disableDean($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_DEANS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "Dean does not exist."], 404);
        }

        $user["enabled"] = false;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to disable dean."], 500);
        }    
            
        Response::json([
            "message" => "Dean has been disabled.",
            "id" => $validatedId,
        ]);
    }

    public function createPstaff()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::CREATE_PSTAFFS);
        $passwordHash = Password::generate();
        $validated = Validator::batchValidate([
            [Validator::EMAIL, "email", Request::fromBody("email")],
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-50"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-50"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-50"],
        ]);

        if ($this->model->getByEmail($validated["email"])) {
            Response::json(["message" => "PESO Staff already exists."], 409);
        }

        $userId = $this->model->createPstaff([
            "email" => $validated["email"],
            "password_hash" => $passwordHash,
            "first_name" => $validated["first name"],
            "middle_name" => $validated["middle name"],
            "last_name" => $validated["last name"],
        ]);

        if ($userId === null) {
            Response::json(["message" => "Unable to create peso staff."], 500);
        }

        $user = $this->model->getPstaffById($userId);
        Response::json($user, 201);
    }

    public function searchPstaffs()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::READ_PSTAFFS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "enabled", Request::fromQuery("enabled", true)],
        ]);
        $paginator = new Paginator($this->pdo, "users", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT
                u.id AS uid,
                u.email AS uemail,
                u.role AS urole,
                u.enabled AS uenabled,
                u.created_at AS ucreated_at,
                u.updated_at AS uupdated_at,
                p.id AS pid,
                p.user_id AS puser_id,
                p.first_name AS pfirst_name,
                p.middle_name AS pmiddle_name,
                p.last_name AS plast_name
            FROM users u
            JOIN pstaffs p ON p.user_id = u.id
            ",
            "WHERE
                u.role = 'pstaff'
                AND u.enabled = ?
                AND (
                    u.email LIKE ? OR
                    p.first_name LIKE ? OR
                    p.middle_name LIKE ? OR
                    p.last_name LIKE ?
                )
            ",
            [$validated["enabled"], "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"],
            [User::class, "format"]
        );
        Response::json($result);
    }

    public function enablePstaff($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_PSTAFFS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "PESO Staff does not exist."], 404);
        }

        $user["enabled"] = true;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to enable dean."], 500);
        }    
            
        Response::json([
            "message" => "PESO Staff has been enabled.",
            "id" => $validatedId,
        ]);
    }

    public function disablePstaff($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_PSTAFFS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "PESO Staff does not exist."], 404);
        }

        $user["enabled"] = false;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to disable dean."], 500);
        }    
            
        Response::json([
            "message" => "PESO Staff has been disabled.",
            "id" => $validatedId,
        ]);
    }
    
    public function me()
    {
        $cUser = UserGuard::run($this->pdo, Role::all());
        Response::json($cUser);
    }

    public function changePassword()
    {
        $cUser = UserGuard::run($this->pdo, Role::all());

        if ($cUser["email"] === $_ENV["SYSAD_EMAIL"]) {
            Response::json(["message" => "User info should not be modified."], 403);
        }
        
        $validated = Validator::batchValidate([
            [Validator::TEXT, "current password", Request::fromBody("current_password"), "8-65"],
            [Validator::TEXT, "new password", Request::fromBody("new_password"), "8-65"],
        ]);
        
        $fullCUserr = $this->model->getById($cUser["id"]);
        
        if (!Password::correct($validated["current password"], $fullCUserr["password_hash"])) {
            Response::json(["error" => "Password incorrect."], 401);
        }

        $fullCUserr["password_hash"] = Password::hash($validated["new password"]);
        $tookEffect = $this->model->updateById($fullCUserr["id"], $fullCUserr);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to change password."], 500);
        }

        Response::json([
            "message" => "Password has been updated.",
            "id" => $fullCUserr["id"],
        ]);
    }
}