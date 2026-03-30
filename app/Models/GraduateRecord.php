<?php

class GraduateRecord
{   
    protected PDO $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($record)
    {
        $statement = $this->pdo->prepare("INSERT INTO graduate_records (filename, course_id, dean_uploader_id, graduation_year) VALUES (?, ?, ?, ?)");
        $statement->execute([
            $record["filename"],
            $record["course_id"],
            $record["dean_uploader_id"],
            $record["graduation_year"]
        ]);
        return $this->pdo->lastInsertId();
    }
    
    public function getById($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
                gr.id AS grid,
                gr.filename AS grfilename,
                gr.course_id AS grcourse_id,
                gr.graduation_year AS grgraduation_year,
                gr.archived AS grarchived,
                gr.dean_uploader_id AS grdean_uploader_id,
                gr.created_at AS grcreated_at,
                gr.updated_at AS grupdated_at,
                c.name AS cname,
                d.first_name AS dfirst_name,
                d.middle_name AS dmiddle_name,
                d.last_name AS dlast_name
            FROM graduate_records gr
            JOIN courses c ON gr.course_id = c.id
            JOIN deans d ON gr.dean_uploader_id = d.id
            WHERE gr.id = ?
        ");
        $statement->execute([$id]);
        $record = $statement->fetch();

        if (!$record) {
            return null;
        }
        
        return self::format($record);
    }

    
    public function getByCourseIdAndYear($courseId, $graduationYear)
    {
        $statement = $this->pdo->prepare("
            SELECT
                gr.id AS grid,
                gr.filename AS grfilename,
                gr.course_id AS grcourse_id,
                gr.graduation_year AS grgraduation_year,
                gr.archived AS grarchived,
                gr.dean_uploader_id AS grdean_uploader_id,
                gr.created_at AS grcreated_at,
                gr.updated_at AS grupdated_at,
                c.name AS cname,
                d.first_name AS dfirst_name,
                d.middle_name AS dmiddle_name,
                d.last_name AS dlast_name
            FROM graduate_records gr
            JOIN courses c ON gr.course_id = c.id
            JOIN deans d ON gr.dean_uploader_id = d.id
            WHERE gr.course_id = ? AND gr.graduation_year = ?
        ");
        $statement->execute([$courseId, $graduationYear]);
        $record = $statement->fetch();

        if (!$record) {
            return null;
        }
        
        return self::format($record);
    }
    
    public function updateById($id, $record)
    {
        $statement = $this->pdo->prepare("UPDATE graduate_records SET archived = ? WHERE id = ?");
        $statement->execute([$record["archived"], $id]);
        return $statement->rowCount() > 0;
    }

    public static function format($record)
    {
        return [
            "id" => $record["grid"],
            "filename" => $record["grfilename"],
            "archived" => $record["grarchived"],
            "course_id" => $record["grcourse_id"],
            "graduation_year" => $record["grgraduation_year"],
            "dean_uploader_id" => $record["grdean_uploader_id"],
            "created_at" => $record["grcreated_at"],
            "updated_at" => $record["grupdated_at"],
            "course" => [
                "name" => $record["cname"]
            ],
            "uploader" => [
                "first_name" => $record["dfirst_name"],
                "middle_name" => $record["dmiddle_name"],
                "last_name" => $record["dlast_name"],
            ]
        ];
    }
}