<?php

class JobPostLike
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function create($like)
    {
        $statement = $this->pdo->prepare("INSERT INTO job_post_likes (alumni_id, job_post_id) VALUES (?, ?)");
        $statement->execute([$like["alumni_id"], $like["job_post_id"]]);
        return $this->pdo->lastInsertId();
    }

    public function canLike($like)
    {
        $statement = $this->pdo->prepare("SELECT * FROM job_post_likes WHERE alumni_id = ? AND job_post_id = ?");
        $statement->execute([$like["alumni_id"], $like["job_post_id"]]);
        return $statement->fetch() === false;
    }

    public function getByIds($alumniId, $jobPostId)
    {
        $statement = $this->pdo->prepare("
            SELECT
                l.id AS lid,
                l.alumni_id AS lalumni_id,
                l.job_post_id AS ljob_post_id,
                l.created_at AS lcreated_at,
                jp.id AS jpid,
                jp.position AS jpposition,
                c.id AS cid,
                c.name AS cname,
                c.req_logo AS creq_logo,
                a.id AS aid,
                a.name_extension AS aname_extension,
                a.first_name AS afirst_name,
                a.middle_name AS amiddle_name,
                a.last_name AS alast_name,
                crs.name AS crsname
            FROM job_post_likes l
            JOIN job_posts jp ON jp.id = l.job_post_id
            JOIN companies c ON c.id = jp.company_id
            JOIN alumni a ON a.id = l.alumni_id
            JOIN courses crs ON crs.id = a.course_id
            WHERE l.alumni_id = ? AND l.job_post_id = ?
        ");
        $statement->execute([$alumniId, $jobPostId]);
        $result = $statement->fetch();

        if ($result) {
            return self::format($result);
        }
        
        return $result;
    }

    public function deleteByIds($alumniId, $jobPostId)
    {
        $statement = $this->pdo->prepare("
            DELETE FROM job_post_likes
            WHERE alumni_id = ? AND job_post_id = ?
        ");
        $statement->execute([$alumniId, $jobPostId]);
        return $statement->rowCount() > 0;
    }

    // for checking if the alumni is still able to delete the like
    public function canDeleteByIds($alumniId, $jobPostId)
    {
        $statement = $this->pdo->prepare("
            SELECT * FROM job_post_likes
            WHERE alumni_id = ? AND job_post_id = ?
        ");
        $statement->execute([$alumniId, $jobPostId]);
        return $statement->fetch() !== false;
    }

    public static function format($submission)
    {
        return [
            "id" => $submission["lid"],
            "alumni_id" => $submission["lalumni_id"],
            "job_post_id" => $submission["ljob_post_id"],
            "created_at" => $submission["lcreated_at"],
            "job_post" => [
                "id" => $submission["jpid"],
                "position" => $submission["jpposition"],
            ],
            "company" => [
                "id" => $submission["cid"],
                "name" => $submission["cname"],
                "req_logo" => $submission["creq_logo"],
            ],
            "alumni" => [
                "id" => $submission["aid"],
                "name_extension" => $submission["aname_extension"],
                "first_name" => $submission["afirst_name"],
                "middle_name" => $submission["amiddle_name"],
                "last_name" => $submission["alast_name"],
                "course" => $submission["crsname"],
            ],
        ];
    }
}