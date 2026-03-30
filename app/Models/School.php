<?php

class School
{   
    protected PDO $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($school)
    {
        $statement = $this->pdo->prepare("INSERT INTO schools (name) VALUES (?)");
        $statement->execute([$school["name"]]);
        return $this->pdo->lastInsertId();
    }
    
    public function getAll()
    {
        $statement = $this->pdo->prepare("SELECT * FROM schools");
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function getAllActive()
    {
        $statement = $this->pdo->prepare("SELECT * FROM schools WHERE archived IS FALSE");
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM schools WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getByName($name)
    {
        $statement = $this->pdo->prepare("SELECT * FROM schools WHERE LOWER(name) = LOWER(?)");
        $statement->execute([$name]);
        return $statement->fetch();
        }
    
    public function updateById($id, $school)
    {
        $statement = $this->pdo->prepare("UPDATE schools SET archived = ?, name = ? WHERE id = ?");
        $statement->execute([$school["archived"], $school["name"], $id]);
        return $statement->rowCount() > 0;
    }
}