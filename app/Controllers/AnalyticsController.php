<?php

require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Models/Analytics.php";

class AnalyticsController
{
    protected $pdo;
    protected $model;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new Analytics($pdo);
    }

    public function get()
    {
        $user = UserGuard::run($this->pdo, [Role::SYSAD, Role::DEAN, Role::PSTAFF]);
        $analytics = $this->model->getByRole($user);
        Response::json($analytics);
    }

    public function getReport()
    {
        $user = UserGuard::run($this->pdo, [Role::SYSAD, Role::DEAN, Role::PSTAFF]);
        $this->model->getReportByRole($user);
        exit;
    }
}