<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Core/Paginator.php";
require_once __DIR__ . "/../Models/JobPost.php";
require_once __DIR__ . "/../Models/JobPostLike.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";

class JobPostLikeController
{
    protected $pdo;
    protected $jobPostModel;
    protected $model;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->jobPostModel = new JobPost($pdo);
        $this->model = new JobPostLike($pdo);
    }

    public function like($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $validatedId = Validator::validateInteger("job_post_id", $id);

        if (!$this->jobPostModel->getById($validatedId)) {
            Response::json(["message" => "Job post not found"], 404);
        }

        $like = ["alumni_id" => $cUser["profile"]["id"], "job_post_id" => $validatedId];

        if (!$this->model->canLike($like)) {
            Response::json(["message" => "You've already liked the post."], 409);
        }
        
        $likeId = $this->model->create($like);

        if (!$likeId) {
            Response::json(["message" => "Unable to like post."], 500);
        }

        Response::json(["message" => "Liked post."], 201);
    }

    public function dislike($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $validatedId = Validator::validateInteger("like_id", $id);

        if (!$this->model->getByIds($cUser["profile"]["id"], $id)) {
            Response::json(["message" => "Like not found."], 404);
        }

        if (!$this->model->canDeleteByIds($cUser["profile"]["id"], $id)) {
            Response::json(["message" => "you haven't liked the post."], 404);
        }

        if (!$this->model->deleteByIds($cUser["profile"]["id"], $id)) {
            Response::json(["message" => "Unable to dislike the post."], 500);
        }

        Response::json(["message" => "Post has been disliked."], 200);
    }

    public function myLikes()
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
        ]);

        $paginator = new Paginator($this->pdo, "job_post_likes", $validated["page"], $validated["per_page"]);
        $result = $paginator->run("
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
            JOIN courses crs ON crs.id = a.course_id",
            "WHERE l.alumni_id = ? ORDER BY l.created_at DESC",
            [$cUser["profile"]["id"]],
            [JobPostLike::class, "format"],
        );
        Response::json($result);
    }
}