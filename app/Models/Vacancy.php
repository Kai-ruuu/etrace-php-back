<?php

class Vacancy
{   
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($vacancy)
    {
        $companyId = $vacancy["company_id"];
        $jobTitle = $vacancy["job_title"];
        $slots = $vacancy["slots"];
        $qualifications = $vacancy["qualifications"];

        $statement = $this->pdo->prepare("
            INSERT INTO vacancies
                (company_id, job_title, slots, qualifications)
            VALUES (?, ?, ?, ?)
        ");
        $statement->execute([$companyId, $jobTitle, $slots, $qualifications]);
        return $this->pdo->lastInsertId();
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("
            SELECT * 
            FROM vacancies
            WHERE id = ?
        ");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function updateById($id, $vacancy)
    {
        $jobTitle = $vacancy["job_title"];
        $slots = $vacancy["slots"];
        $qualifications = $vacancy["qualifications"];
        
        $statement = $this->pdo->prepare("
            UPDATE vacancies
            SET
                job_title = ?,
                slots = ?,
                qualifications = ?
            WHERE id =?
        ");
        $statement->execute([$jobTitle, $slots, $qualifications, $id]);
        return $statement->rowCount() > 0;
    }

    public static function format($vacancy)
    {
        return [
            "id"             => $vacancy["id"],
            "job_title"      => $vacancy["job_title"],
            "slots"          => $vacancy["slots"],
            "qualifications" => json_decode($vacancy["qualifications"], true),
        ];
    }
}