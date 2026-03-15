<?php

class AlumniRejectionMessage
{   
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM alumni_rejection_messages WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getByAlumniId($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
                r.id AS rid,
                r.dean_id AS rdean_id,
                r.alumni_id AS ralumni_id,
                r.message AS rmessage,
                r.created_at AS rcreated_at,
                a.id AS aid,
                a.alumni_id AS aalumni_id,
                a.rejection_id AS arejection_id,
                a.message AS amessage,
                a.created_at AS acreated_at
            FROM alumni_rejection_messages r
            LEFT JOIN alumni_rejection_appeals a ON a.rejection_id = r.id
            WHERE r.alumni_id = ?
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
                "alumni_id" => $message["aalumni_id"],
                "rejection_id" => $message["arejection_id"],
                "message" => $message["amessage"],
                "created_at" => $message["acreated_at"],
            ];
        }

        return [
            "id" => $message["rid"],
            "alumni_id" => $message["ralumni_id"],
            "message" => $message["rmessage"],
            "created_at" => $message["rcreated_at"],
            "appeal" => $appeal
        ];
    }
}