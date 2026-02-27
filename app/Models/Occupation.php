<?php

class Occupation
{
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
}