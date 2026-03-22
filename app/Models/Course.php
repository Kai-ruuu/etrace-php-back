<?php

class Course
{   
    protected $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($course)
    {
        $statement = $this->pdo->prepare("INSERT INTO courses (name, code, school_id) VALUES (?, ?, ?)");
        $statement->execute([$course["name"], $course["code"], $course["school_id"]]);
        return $this->pdo->lastInsertId();
    }
    
    public function getAll()
    {
        $statement = $this->pdo->prepare("SELECT * FROM courses");
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getByName($name, $schoolId)
    {
        $statement = $this->pdo->prepare("SELECT * FROM courses WHERE LOWER(name) = LOWER(?) AND school_id = ?");
        $statement->execute([$name, $schoolId]);
        return $statement->fetch();
    }

    public function getByCode($code, $schoolId)
    {
        $statement = $this->pdo->prepare("SELECT * FROM courses WHERE LOWER(code) = LOWER(?) AND school_id = ?");
        $statement->execute([$code, $schoolId]);
        return $statement->fetch();
    }
    
    public function getAllBySchoolId($id)
    {
        $statement = $this->pdo->prepare("
            SELECT * FROM courses
            WHERE school_id = ?
        ");
        $statement->execute([$id]);
        return $statement->fetchAll();
    }

    public function getAllActive()
    {
        $statement = $this->pdo->prepare("SELECT * FROM courses WHERE archived = FALSE");
        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function updateById($id, $course)
    {
        $statement = $this->pdo->prepare("UPDATE courses SET archived = ?, name = ?, code = ? WHERE id = ?");
        $statement->execute([$course["archived"], $course["name"], $course["code"], $id]);
        return $statement->rowCount() > 0;
    }
}