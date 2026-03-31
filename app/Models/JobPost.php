<?php

class JobPost
{
    protected PDO $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM job_posts WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getFullById($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
                jp.id AS jpid,
                jp.company_id AS jpcompany_id,
                jp.position AS jpposition,
                jp.description AS jpdescription,
                jp.qualifications AS jpqualifications,
                jp.address AS jpaddress,
                jp.salary_min AS jpsalary_min,
                jp.salary_max AS jpsalary_max,
                jp.work_shift AS jpwork_shift,
                jp.work_setup AS jpwork_setup,
                jp.employment_type AS jpemployment_type,
                jp.slots AS jpslots,
                jp.additional_info AS jpadditional_info,
                jp.open_until AS jpopen_until,
                jp.active AS jpactive,
                jp.created_at AS jpcreated_at,
                jp.updated_at AS jpupdated_at,
                jpc.id AS jpcid,
                jpc.job_post_id AS jpcjob_post_id,
                jpc.course_id AS jpccourse_id,
                c.id AS cid,
                c.name AS cname,
                com.name AS comname,
                com.req_logo AS comreq_logo,
                (
                    SELECT COUNT(*) FROM job_post_likes jpl
                    WHERE jpl.job_post_id = jp.id
                ) AS likes,
                (
                    SELECT COUNT(DISTINCT jps.alumni_id) FROM job_post_cv_submissions jps
                    WHERE jps.job_post_id = jp.id
                ) AS submissions
            FROM job_posts jp
            JOIN job_post_courses jpc ON jpc.job_post_id = jp.id
            JOIN courses c ON c.id = jpc.course_id
            JOIN companies com ON com.id = jp.company_id
            WHERE
                jp.id = ? AND
                jp.open_until >= CURDATE() AND jp.active = TRUE
        ");
        $statement->execute([$id]);
        $result = $statement->fetch();
        return $result ? self::formatNoTargetCourses($result) : null;
    }

    public function create($job, $targetCourses)
    {
        $postId = null;
        
        try {
            $companyId = $job["company_id"];
            $position = $job["position"];
            $description = $job["description"];
            $qualifications = $job["qualifications"];
            $address = $job["address"];
            $salaryMin = $job["salary_min"];
            $salaryMax = $job["salary_max"];
            $workShift = $job["work_shift"];
            $workSetup = $job["work_setup"];
            $employmentType = $job["employment_type"];
            $slots = $job["slots"];
            $additionalInfo = $job["additional_info"];
            $openUntil = $job["open_until"];

            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare("
                INSERT INTO job_posts
                    (
                        company_id,
                        position,
                        description,
                        qualifications,
                        address,
                        salary_min,
                        salary_max,
                        work_shift,
                        work_setup,
                        employment_type,
                        slots,
                        additional_info,
                        open_until
                    )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $statement->execute([$companyId, $position, $description, $qualifications, $address, $salaryMin, $salaryMax, $workShift, $workSetup, $employmentType, $slots, $additionalInfo, $openUntil->format("Y-m-d")]);

            $postId = $this->pdo->lastInsertId();

            foreach ($targetCourses as $courseId) {
                $statement = $this->pdo->prepare("
                    INSERT INTO job_post_courses
                        (job_post_id, course_id)
                    VALUES (?, ?)
                ");
                $statement->execute([$postId, $courseId]);
            }

            $this->pdo->commit();
            
            return $postId;
        } catch (PDOException $e) {
            return $postId;
        }
    }

    public function closeById($id)
    {
        // delete likes
        $statement = $this->pdo->prepare("
            DELETE FROM job_post_likes
            WHERE job_post_id = ?
        ");
        $statement->execute([$id]);
        
        // delete submissions
        $statement = $this->pdo->prepare("
            DELETE FROM job_post_cv_submissions
            WHERE job_post_id = ?
        ");
        $statement->execute([$id]);
        
        // set post as innactive
        $statement = $this->pdo->prepare("
            UPDATE job_posts
            SET active = FALSE
            WHERE id = ?
        ");
        $statement->execute([$id]);
        return $statement->rowCount() > 0;
    }

    public function deleteById($id)
    {
        // delete likes
        $statement = $this->pdo->prepare("
            DELETE FROM job_post_likes
            WHERE job_post_id = ?
        ");
        $statement->execute([$id]);
        
        // delete submissions
        $statement = $this->pdo->prepare("
            DELETE FROM job_post_cv_submissions
            WHERE job_post_id = ?
        ");
        $statement->execute([$id]);

        // delete post
        $statement = $this->pdo->prepare("
            DELETE FROM job_posts
            WHERE id = ?
        ");
        $statement->execute([$id]);
        return $statement->rowCount() > 0;
    }

    public function repostById($id, $openUntil)
    {
        try {
            $statement = $this->pdo->prepare("
                UPDATE job_posts
                SET
                    active = TRUE,
                    open_until = ?,
                    created_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $statement->execute([$openUntil->format("Y-m-d"), $id]);
            return $statement->rowCount() > 0;
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }

    public static function format($jobPost)
    {
        $formatted = [
            "id" => $jobPost["jpid"],
            "company_id" => $jobPost["jpcompany_id"],
            "company" => [
                "name" => $jobPost["comname"],
                "req_logo" => $jobPost["comreq_logo"],
            ],
            "position" => $jobPost["jpposition"],
            "description" => $jobPost["jpdescription"],
            "qualifications" => $jobPost["jpqualifications"],
            "address" => $jobPost["jpaddress"],
            "salary_min" => $jobPost["jpsalary_min"],
            "salary_max" => $jobPost["jpsalary_max"],
            "work_shift" => $jobPost["jpwork_shift"],
            "work_setup" => $jobPost["jpwork_setup"],
            "employment_type" => $jobPost["jpemployment_type"],
            "slots" => $jobPost["jpslots"],
            "additional_info" => $jobPost["jpadditional_info"],
            "open_until" => $jobPost["jpopen_until"],
            "active" => $jobPost["jpactive"],
            "target_courses" => $jobPost["target_courses"],
            "likes" => $jobPost["likes"],
            "submissions" => $jobPost["submissions"],
            "created_at" => $jobPost["jpcreated_at"],
            "updated_at" => $jobPost["jpupdated_at"],
        ];

        if (isset($jobPost["is_liked"])) {
            $formatted["is_liked"] = $jobPost["is_liked"];
        }

        if (isset($jobPost["is_submitted"])) {
            $formatted["is_submitted"] = $jobPost["is_submitted"];
        }

        return $formatted;
    }

    public static function formatNoTargetCourses($jobPost)
    {
        $formatted = [
            "id" => $jobPost["jpid"],
            "company_id" => $jobPost["jpcompany_id"],
            "company" => [
                "name" => $jobPost["comname"],
                "req_logo" => $jobPost["comreq_logo"],
            ],
            "position" => $jobPost["jpposition"],
            "description" => $jobPost["jpdescription"],
            "qualifications" => $jobPost["jpqualifications"],
            "address" => $jobPost["jpaddress"],
            "salary_min" => $jobPost["jpsalary_min"],
            "salary_max" => $jobPost["jpsalary_max"],
            "work_shift" => $jobPost["jpwork_shift"],
            "work_setup" => $jobPost["jpwork_setup"],
            "employment_type" => $jobPost["jpemployment_type"],
            "slots" => $jobPost["jpslots"],
            "additional_info" => $jobPost["jpadditional_info"],
            "open_until" => $jobPost["jpopen_until"],
            "active" => $jobPost["jpactive"],
            "likes" => $jobPost["likes"],
            "submissions" => $jobPost["submissions"],
            "created_at" => $jobPost["jpcreated_at"],
            "updated_at" => $jobPost["jpupdated_at"],
        ];

        if (isset($jobPost["is_liked"])) {
            $formatted["is_liked"] = $jobPost["is_liked"];
        }

        if (isset($jobPost["is_submitted"])) {
            $formatted["is_submitted"] = $jobPost["is_submitted"];
        }

        return $formatted;
    }
}