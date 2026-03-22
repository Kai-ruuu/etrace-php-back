<?php

class CourseAlignedOccupations
{
    protected $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByCourseAndOccupationIds($courseId, $occupationId)
    {
        $statement = $this->pdo->prepare("
            SELECT * FROM course_aligned_occupations
            WHERE
                course_id = ?
                AND occupation_id = ?
        ");
        $statement->execute([$courseId, $occupationId]);
        return $statement->fetch();
    }

    public function create($occupationInfo)
    {
        $statement = $this->pdo->prepare("
            INSERT INTO course_aligned_occupations
                (
                    course_id,
                    occupation_id
                )
            VALUES (?, ?)
        ");
        $statement->execute([
            $occupationInfo["course_id"],
            $occupationInfo["occupation_id"],
        ]);
        return $this->pdo->lastInsertId();
    }

    public function delete($occupationInfo)
    {
        $statement = $this->pdo->prepare("
            DELETE FROM course_aligned_occupations
            WHERE course_id = ? AND occupation_id = ?
        ");
        $statement->execute([
            $occupationInfo["course_id"],
            $occupationInfo["occupation_id"],
        ]);
        return $statement->rowCount() > 0;
    }
}