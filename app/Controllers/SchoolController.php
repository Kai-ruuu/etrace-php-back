<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Core/Paginator.php";
require_once __DIR__ . "/../Models/School.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";

class SchoolController
{   
    protected $pdo;
    protected $model;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new School($pdo);
    }

    public function create()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::CREATE_SCHOOLS);
        $validatedName = Validator::validateText("school name", Request::fromBody("name"), "1-65");

        if ($this->model->getByName($validatedName)) {
            Response::json(["message" => "School already exists."], 409);
        }

        $schoolId = $this->model->create(["name" => $validatedName]);
        $school = $this->model->getById($schoolId);
        Response::json($school, 201);
    }

    public function search()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::READ_SCHOOLS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "archived", Request::fromQuery("archived", false)],
        ]);
        $paginator = new Paginator($this->pdo, "schools", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT * FROM schools",
            "WHERE archived = ? AND name LIKE ?",
            [$validated["archived"], "%{$q}%"]
        );
        Response::json($result);
    }

    public function getAll() {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::READ_SCHOOLS);
        $shools = $this->model->getAll();
        Response::json($shools);
    }

    public function getAllActive()
    {
        $schools = $this->model->getAllActive();
        Response::json($schools);
    }

    public function rename($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::UPDATE_SCHOOLS);
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "id", $id],
            [Validator::TEXT, "name", Request::fromBody("name"), "1-65"],
        ]);
        $school = $this->model->getById($validated["id"]);
        
        if (!$school) {
            Response::json(["message" => "School not found."], 404);
        }

        if ($this->model->getByName($validated["name"])) {
            Response::json(["message" => "A school with the same name already exists."], 409);
        }

        $school["name"] = $validated["name"];
        $tookEffect = $this->model->updateById($validated["id"], $school);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to rename school."], 500);
        }    
            
        Response::json([
            "message" => "School has been renamed.",
            "id" => $id,
        ]);
    }

    public function archive($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::UPDATE_SCHOOLS);
        $validatedId = Validator::validateInteger("id", $id);
        $school = $this->model->getById($id);
        
        if (!$school) {
            Response::json(["message" => "School not found."], 404);
        }
        
        if ($school["archived"]) {
            Response::json(["message" => "School is already archived."], 409);
        }

        $school["archived"] = true;
        $tookEffect = $this->model->updateById($validatedId, $school);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to archive school."], 500);
        }    
            
        Response::json([
            "message" => "School has been archived.",
            "id" => $validatedId,
        ]);
    }

    public function restore($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::UPDATE_SCHOOLS);
        $validatedId = Validator::validateInteger("id", $id);
        $school = $this->model->getById($validatedId);
        
        if (!$school) {
            Response::json(["message" => "School not found."], 404);
        }
        
        if (!$school["archived"]) {
            Response::json(["message" => "School is already active."], 409);
        }

        $school["archived"] = false;
        $tookEffect = $this->model->updateById($validatedId, $school);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to restore school."], 500);
        }    
            
        Response::json([
            "message" => "School has been restored.",
            "id" => $validatedId,
        ]);
    }
}