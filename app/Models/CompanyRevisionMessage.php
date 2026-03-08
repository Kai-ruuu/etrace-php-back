<?php

class CompanyRevisionMessage
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM company_revision_messages WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getRevisionNotesAndAppeals($companyId, $attrName)
    {
        $statement = $this->pdo->prepare("
            SELECT
                r.id AS rid,
                r.company_id AS rcompany_id,
                r.pstaff_id AS rpstaff_id,
                r.message AS rmessage,
                r.requirement_name AS rrequirement_name,
                r.created_at AS rcreated_at,
                a.id AS aid,
                a.message AS amessage,
                a.created_at AS acreated_at
            FROM company_revision_messages r
            LEFT JOIN company_revision_appeals a ON a.resubmit_id = r.id
            WHERE
                r.company_id = ? AND
                r.requirement_name = ?
            ORDER BY r.created_at DESC
        ");
        $statement->execute([$companyId, $attrName]);
        $rows = $statement->fetchAll();
        $results = [];

        foreach ($rows as $result) {
            $results[] = self::format($result);
        }

        return $results;
    }

    public static function format($revision)
    {
        $appeal = null;

        if ($revision["aid"]) {
            $appeal = [
                "id" => $revision["aid"],
                "message" => $revision["amessage"],
                "created_at" => $revision["acreated_at"],
            ];
        }

        return [
            "id" => $revision["rid"],
            "company_id" => $revision["rcompany_id"],
            "pstaff_id" => $revision["rpstaff_id"],
            "message" => $revision["rmessage"],
            "requirement_name" => $revision["rrequirement_name"],
            "created_at" => $revision["rcreated_at"],
            "appeal" => $appeal
        ];
    }
}