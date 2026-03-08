<?php

class CompanyRejectionMessage
{   
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM company_rejection_messages WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getByCompanyIdAsSysad($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
                r.id AS rid,
                r.sysad_id AS rsysad_id,
                r.pstaff_id AS rpstaff_id,
                r.company_id AS rcompany_id,
                r.message AS rmessage,
                r.created_at AS rcreated_at,
                a.id AS aid,
                a.company_id AS acompany_id,
                a.rejection_id AS arejection_id,
                a.message AS amessage,
                a.created_at AS acreated_at
            FROM company_rejection_messages r
            LEFT JOIN company_rejection_appeals a ON a.rejection_id = r.id
            WHERE
                r.company_id = ? AND
                r.sysad_id IS NOT NULL
            ORDER BY r.created_at DESC
        ");
        $statement->execute([$id]);
        $rows = $statement->fetchAll();
        $results = [];

        foreach ($rows as $result) {
            $results[] = self::format($result);
        }

        return $results;
    }

    public function getByCompanyIdAsPstaff($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
                r.id AS rid,
                r.company_id AS rcompany_id,
                r.message AS rmessage,
                r.created_at AS rcreated_at,
                a.id AS aid,
                a.company_id AS acompany_id,
                a.rejection_id AS arejection_id,
                a.message AS amessage,
                a.created_at AS acreated_at
            FROM company_rejection_messages r
            LEFT JOIN company_rejection_appeals a ON a.rejection_id = r.id
            WHERE
                r.company_id = ? AND
                r.pstaff_id IS NOT NULL
            ORDER BY r.created_at DESC
        ");
        $statement->execute([$id]);
        $rows = $statement->fetchAll();
        $results = [];

        foreach ($rows as $result) {
            $results[] = self::format($result);
        }

        return $results;
    }

    public static function format($message)
    {
        $appeal = null;

        if ($message["aid"]) {
            $appeal = [
                "id" => $message["aid"],
                "company_id" => $message["acompany_id"],
                "rejection_id" => $message["arejection_id"],
                "message" => $message["amessage"],
                "created_at" => $message["acreated_at"],
            ];
        }

        return [
            "id" => $message["rid"],
            "company_id" => $message["rcompany_id"],
            "message" => $message["rmessage"],
            "created_at" => $message["rcreated_at"],
            "appeal" => $appeal
        ];
    }
}