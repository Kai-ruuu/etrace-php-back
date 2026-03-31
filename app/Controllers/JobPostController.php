<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Core/Paginator.php";
require_once __DIR__ . "/../Models/Course.php";
require_once __DIR__ . "/../Models/JobPost.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";

class JobPostController
{
    protected $pdo;
    protected $model;
    protected $courseModel;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new JobPost($pdo);
        $this->courseModel = new Course($pdo);
    }

    public function post()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);

        if ($cUser["profile"]["ver_stat_sysad"] !== "Verified"|| $cUser["profile"]["ver_stat_pstaff"] !== "Verified") {
            Response::json(["message" => "Cannot post job yet. Your account needs to be fully verified first."], 403);
        }
        
        $position = Validator::validateText("position", Request::fromBody("position"), "1-255");
        $description = Validator::validateText("description", Request::fromBody("description"), "1-0");
        $rawQualifications = Request::fromBody("qualifications");
        $qualifications = Validator::validateText("qualifications", json_encode(Request::fromBody("qualifications")), "1-0");
        $address = Validator::validateText("address", Request::fromBody("address"), "1-512");
        $salaryMin = Validator::validateInteger("salary_min", Request::fromBody("salary_min"));
        $salaryMax = Validator::validateInteger("salary_max", Request::fromBody("salary_max"));
        $workShift = Validator::validateEnum("work_shift", Request::fromBody("work_shift"), [
            'Day', 'Evening / Swing', 'Night / Graveyard', 'Morning'
        ]);
        $workSetup = Validator::validateEnum("work_setup", Request::fromBody("work_setup"), [
            'On-site', 'Remote', 'Hybrid'
        ]);
        $employmentType = Validator::validateEnum("employment_type", Request::fromBody("employment_type"), [
            'Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'
        ]);
        $slots = Validator::validateInteger("slots", Request::fromBody("slots"));
        $additionalInfo = Validator::validateText("additional_info", Request::fromBody("additional_info"), "0-0");
        $openUntil = Request::fromBody("open_until");
        $targetCourses = Request::fromBody("target_courses");

        if (count(json_decode($rawQualifications)) === 0) {
            Response::json(["message" => "Job post should have at least 1 requirement."], 400);
            }
            
        if ($salaryMin > $salaryMax) {
            Response::json(["message" => "Minimim salary should be lower than the maximum salary."], 400);
        }
            
        if ($salaryMax < $salaryMin) {
            Response::json(["message" => "Maximum salary should be higher than the minimum salary."], 400);
        }

        $dateToday = new DateTime('today');
        $openUntil = new DateTime($openUntil);

        if ($openUntil < $dateToday) {
            Response::json(["message" => "Open until date is in the past."], 400);
        }

        if (count($targetCourses) === 0) {
            Response::json(["message" => "Job post should have at least 1 target course."], 400);
        }

        $postId = $this->model->create([
            "company_id" => $cUser["profile"]["id"],
            "position" => $position,
            "description" => $description,
            "qualifications" => $rawQualifications,
            "address" => $address,
            "salary_min" => $salaryMin,
            "salary_max" => $salaryMax,
            "work_shift" => $workShift,
            "work_setup" => $workSetup,
            "employment_type" => $employmentType,
            "slots" => $slots,
            "additional_info" => $additionalInfo,
            "open_until" => $openUntil
        ], $targetCourses);

        if (!$postId) {
            Response::json(["message" => "Failed to create job post."], 500);
        }

        Response::json(["message" => "Job has been successfully posted."], 201);
    }

    public function getFullById($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $id = Validator::validateInteger("id", $id);
        $post = $this->model->getFullById($id);

        if (!$post) {
            Response::json(["message" => "Post not found"], 404);
        }

        Response::json($post);
    }

    public function searchAsCompany()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "published", Request::fromQuery("published", true)]
        ]);
        
        $openFilter = $validated["published"]
            ? "jp.open_until >= CURDATE() AND jp.active = TRUE"
            : "jp.open_until < CURDATE() OR jp.active = FALSE";
        $paginator = new Paginator($this->pdo, "job_posts", $validated["page"], $validated["per_page"]);
        $result = $paginator->run("
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
            JOIN companies com ON com.id = jp.company_id",
            "WHERE
                jp.company_id = ? AND
                {$openFilter} AND
                (
                    jp.position LIKE ? OR
                    jp.address LIKE ?  OR
                    jp.salary_min LIKE ? OR
                    jp.salary_max LIKE ? OR
                    jp.work_shift LIKE ? OR
                    jp.work_setup LIKE ? OR
                    jp.employment_type LIKE ? OR
                    jp.slots LIKE ?
                )
                ORDER BY jp.created_at DESC
            ",
            [
                $cUser["profile"]["id"],
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
            ],
            [JobPost::class, "format"], "
            SELECT COUNT(DISTINCT jp.id) FROM job_posts jp
            JOIN job_post_courses jpc ON jpc.job_post_id = jp.id
            JOIN courses c ON c.id = jpc.course_id"
        );
        Response::json($result);
    }

    public function searchAsAlumni()
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
        ]);
        $alumniId = $cUser["profile"]["id"];

        $paginator = new Paginator($this->pdo, "job_posts", $validated["page"], $validated["per_page"]);
        $result = $paginator->run("
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
                ) AS submissions,
                EXISTS (
                    SELECT 1 FROM job_post_likes jpl
                    WHERE jpl.job_post_id = jp.id AND jpl.alumni_id = {$alumniId}
                ) AS is_liked,
                EXISTS (
                    SELECT 1 FROM job_post_cv_submissions jps
                    WHERE
                        jps.job_post_id = jp.id AND
                        jps.alumni_id = {$alumniId}
                ) AS is_submitted
            FROM job_posts jp
            JOIN job_post_courses jpc ON jpc.job_post_id = jp.id
            JOIN courses c ON c.id = jpc.course_id
            JOIN companies com ON com.id = jp.company_id",
            "WHERE
                jpc.course_id = ? AND
                jp.open_until >= CURDATE() AND
                jp.active = TRUE AND
                (
                    jp.position LIKE ? OR
                    jp.address LIKE ?  OR
                    jp.salary_min LIKE ? OR
                    jp.salary_max LIKE ? OR
                    jp.work_shift LIKE ? OR
                    jp.work_setup LIKE ? OR
                    jp.employment_type LIKE ? OR
                    jp.slots LIKE ?
                )
                ORDER BY jp.created_at DESC
            ",
            [
                $cUser["profile"]["course"]["id"],
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
            ],
            [JobPost::class, "format"], "
            SELECT COUNT(DISTINCT jp.id) FROM job_posts jp
            JOIN job_post_courses jpc ON jpc.job_post_id = jp.id
            JOIN courses c ON c.id = jpc.course_id"
        );
        Response::json($result);
    }

    public function close($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $validatedId = Validator::validateInteger("post id", $id);
        
        if (!$this->model->getById($validatedId)) {
            Response::json(["message" => "Job post not found."], 404);
        }

        $tookEffect = $this->model->closeById($validatedId);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to close job post."], 500);
        }

        Response::json(["message" => "Job post has been closed."]);
    }

    public function delete($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $validatedId = Validator::validateInteger("post id", $id);
        
        if (!$this->model->getById($validatedId)) {
            Response::json(["message" => "Job post not found."], 404);
        }

        $tookEffect = $this->model->deleteById($validatedId);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to delete job post."], 500);
        }

        Response::json(["message" => "Job post has been deleted."]);
    }

    public function repost($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $validatedId = Validator::validateInteger("post id", $id);
        $openUntil = Request::fromBody("open_until");
        
        if (!$this->model->getById($validatedId)) {
            Response::json(["message" => "Job post not found."], 404);
        }

        $dateToday = new DateTime('today');
        $openUntil = new DateTime($openUntil);

        if ($openUntil < $dateToday) {
            Response::json(["message" => "Open until date is in the past."], 400);
        }

        $tookEffect = $this->model->repostById($validatedId, $openUntil);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to repost."], 500);
        }

        Response::json(["message" => "Job has been reposted."]);
    }
}