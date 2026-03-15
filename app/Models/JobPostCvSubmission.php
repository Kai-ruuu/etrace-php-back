<?php

class JobPostCvSubmission
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function create($submission)
    {
        $statement = $this->pdo->prepare("INSERT INTO job_post_cv_submissions (alumni_id, job_post_id) VALUES (?, ?)");
        $statement->execute([$submission["alumni_id"], $submission["job_post_id"]]);
        return $this->pdo->lastInsertId();
    }

    // only allow the alumni to resubmit if the other submissions are already reviewed
    public function canSubmit($submission)
    {
        $statement = $this->pdo->prepare("SELECT * FROM job_post_cv_submissions
            WHERE 
                alumni_id = ? AND
                job_post_id = ? AND
                status = 'Pending'
        ");
        $statement->execute([$submission["alumni_id"], $submission["job_post_id"]]);
        return $statement->fetch() === false;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM job_post_cv_submissions WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getByIds($alumniId, $jobPostId)
    {
        $statement = $this->pdo->prepare("
            SELECT
                cvs.id AS cvsid,
                cvs.alumni_id AS cvsalumni_id,
                cvs.job_post_id AS cvsjob_post_id,
                cvs.status AS cvsstatus,
                cvs.created_at AS cvscreated_at,
                jp.id AS jpid,
                jp.position AS jpposition,
                c.id AS cid,
                c.name AS cname,
                c.req_logo AS creq_logo,
                a.id AS aid,
                a.user_id AS auser_id,
                a.name_extension AS aname_extension,
                a.first_name AS afirst_name,
                a.middle_name AS amiddle_name,
                a.last_name AS alast_name,
                a.file_cv AS afile_cv,
                crs.name AS crsname
            FROM job_post_cv_submissions cvs
            JOIN job_posts jp ON jp.id = cvs.job_post_id
            JOIN companies c ON c.id = jp.company_id
            JOIN alumni a ON a.id = cvs.alumni_id
            JOIN courses crs ON crs.id = a.course_id
            WHERE cvs.alumni_id = ? AND cvs.job_post_id = ?
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
            DELETE FROM job_post_cv_submissions
            WHERE alumni_id = ? AND job_post_id = ?
        ");
        $statement->execute([$alumniId, $jobPostId]);
        return $statement->rowCount() > 0;
    }

    // for checking if the alumni is still able to delete the submission
    public function canDeleteByIds($alumniId, $jobPostId)
    {
        $statement = $this->pdo->prepare("
            SELECT * FROM job_post_cv_submissions
            WHERE 
                alumni_id = ? AND
                job_post_id = ? AND
                status = 'Pending'
        ");
        $statement->execute([$alumniId, $jobPostId]);
        return $statement->fetch() !== false;
    }

    public function setAsReviewed($id)
    {
        $statement = $this->pdo->prepare("
            UPDATE job_post_cv_submissions
            SET status = 'Reviewed'
            WHERE id = ?
        ");
        $statement->execute([$id]);
        return $statement->rowCount() > 0;
    }

    public static function format($submission)
    {
        return [
            "id" => $submission["cvsid"],
            "alumni_id" => $submission["cvsalumni_id"],
            "job_post_id" => $submission["cvsjob_post_id"],
            "status" => $submission["cvsstatus"],
            "created_at" => $submission["cvscreated_at"],
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
                "user_id" => $submission["auser_id"],
                "name_extension" => $submission["aname_extension"],
                "first_name" => $submission["afirst_name"],
                "middle_name" => $submission["amiddle_name"],
                "last_name" => $submission["alast_name"],
                "file_cv" => $submission["afile_cv"],
                "course" => $submission["crsname"],
            ],
        ];
    }
}