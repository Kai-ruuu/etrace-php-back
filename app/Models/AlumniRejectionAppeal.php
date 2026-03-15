<?php

class AlumniRejectionAppeal
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($appeal)
    {
        $statement = $this->pdo->prepare("INSERT INTO alumni_rejection_appeals (alumni_id, rejection_id, message) VALUES (?, ?, ?)");
        $statement->execute([
            $appeal["alumni_id"],
            $appeal["rejection_id"],
            $appeal["message"]
        ]);
        return $this->pdo->lastInsertId();
    }
}