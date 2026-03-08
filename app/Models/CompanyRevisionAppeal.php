<?php

class CompanyRevisionAppeal
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($appeal)
    {
        $statement = $this->pdo->prepare("
            INSERT INTO company_revision_appeals
                (company_id, resubmit_id, message)
            VALUES (?, ?, ?)"
        );
        $statement->execute([
            $appeal["company_id"],
            $appeal["resubmit_id"],
            $appeal["message"]
        ]);
        return $this->pdo->lastInsertId();
    }
}