<?php

class Social
{   
    protected PDO $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($social)
    {
        $statement = $this->pdo->prepare("SELECT id FROM platforms WHERE LOWER(name) = LOWER(?)");
        $statement->execute([$social["platform"]]);
        $platformId = $statement->fetchColumn();

        if (!$platformId) {
            $statement = $this->pdo->prepare("INSERT INTO platforms (name) VALUES (?)");
            $statement->execute([$social["platform"]]);
            $platformId = $this->pdo->lastInsertId();
        }
        
        $statement = $this->pdo->prepare("INSERT INTO socials (alumni_id, platform_id, url) VALUES (?, ?, ?)");
        $statement->execute([$social["alumni_id"], $platformId, $social["url"]]);
        return $this->pdo->lastInsertId();
    }

    public function deleteById($id)
    {
        $statement = $this->pdo->prepare("DELETE FROM socials WHERE id = ?");
        $statement->execute([$id]);
        return $statement->rowCount() > 0;
    }
}