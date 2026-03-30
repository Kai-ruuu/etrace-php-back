<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Core/Paginator.php";
require_once __DIR__ . "/../Models/Occupation.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";

class OccupationController
{   
    protected $pdo;
    protected $model;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new Occupation($pdo);
    }

    public function getAll() {
        $occupations = $this->model->getAll();
        Response::json($occupations);
    }
}