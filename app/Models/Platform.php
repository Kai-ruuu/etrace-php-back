<?php

class Platform
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $statement = $this->pdo->prepare("SELECT * FROM platforms ORDER BY name ASC");
        $statement->execute();
        return $statement->fetchAll();
    }
}