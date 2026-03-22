<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Core/Paginator.php";
require_once __DIR__ . "/../Models/JobPost.php";
require_once __DIR__ . "/../Models/JobPostCvSubmission.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";

class JobPostCvSubmissionController
{
    protected $pdo;
    protected $jobPostModel;
    protected $model;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->jobPostModel = new JobPost($pdo);
        $this->model = new JobPostCvSubmission($pdo);
    }

    public function submitCv($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $validatedId = Validator::validateInteger("job_post_id", $id);

        if (!$this->jobPostModel->getById($validatedId)) {
            Response::json(["message" => "Job post not found"], 404);
        }

        $submission = ["alumni_id" => $cUser["profile"]["id"], "job_post_id" => $validatedId];

        if (!$this->model->canSubmit($submission)) {
            Response::json(["message" => "Your submission latest submission is still not reviewed by the company."], 409);
        }
        
        $submissionId = $this->model->create($submission);

        if (!$submissionId) {
            Response::json(["message" => "Unable to submit CV."], 500);
        }

        Response::json(["message" => "CV submitted."], 201);
    }

    public function review($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $validatedId = Validator::validateInteger("submission id", $id);
        
        if (!$this->model->getById($validatedId)) {
            Response::json(["message" => "Submission not found."], 404);
        }
            
        $tookEffect = $this->model->setAsReviewed($validatedId);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to set submission as reviewed."], 500);
        }

        Response::json(["message" => "Submission has been set as reviewed."]);
    }

    public function deleteSubmission($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $validatedId = Validator::validateInteger("submission_id", $id);

        if (!$this->model->getByIds($cUser["profile"]["id"], $id)) {
            Response::json(["message" => "Submission not found."], 404);
        }

        if (!$this->model->canDeleteByIds($cUser["profile"]["id"], $id)) {
            Response::json(["message" => "Failed to unsibmit. Your CV is already reviewed."], 403);
        }

        if (!$this->model->deleteByIds($cUser["profile"]["id"], $id)) {
            Response::json(["message" => "Unable to unsubmit CV."], 500);
        }

        Response::json(["message" => "CV unsubmitted."], 200);
    }

    public function getSubmissionsAsCompany($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
        ]);

        $paginator = new Paginator($this->pdo, "job_post_cv_submissions", $validated["page"], $validated["per_page"]);
        $result = $paginator->run("
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
            JOIN courses crs ON crs.id = a.course_id",
            "WHERE cvs.job_post_id = ? ORDER BY cvs.created_at ASC",
            [$id],
            [JobPostCvSubmission::class, "format"],
        );
        Response::json($result);
    }

    public function getSubmissionsAsAlumni()
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
        ]);

        $paginator = new Paginator($this->pdo, "job_post_cv_submissions", $validated["page"], $validated["per_page"]);
        $result = $paginator->run("
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
            JOIN courses crs ON crs.id = a.course_id",
            "WHERE cvs.alumni_id = ?",
            [$cUser["profile"]["id"]],
            [JobPostCvSubmission::class, "format"],
        );
        Response::json($result);
    }
}