<?php

class Occupation
{
    protected PDO $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("
            SELECT * FROM occupations
            WHERE id = ?
        ");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getAll()
    {
        $statement = $this->pdo->prepare("SELECT * FROM occupations");
        $statement->execute();
        return $statement->fetchAll();
    }
}