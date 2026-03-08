<?php

class CompanyRejectionAppeal
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($appeal)
    {
        $statement = $this->pdo->prepare("INSERT INTO company_rejection_appeals (company_id, rejection_id, message) VALUES (?, ?, ?)");
        $statement->execute([
            $appeal["company_id"],
            $appeal["rejection_id"],
            $appeal["message"]
        ]);
        return $this->pdo->lastInsertId();
    }
}