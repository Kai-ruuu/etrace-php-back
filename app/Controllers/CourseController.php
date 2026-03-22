<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Core/Paginator.php";
require_once __DIR__ . "/../Models/Course.php";
require_once __DIR__ . "/../Models/Occupation.php";
require_once __DIR__ . "/../Models/CourseAlignedOccupations.php";
require_once __DIR__ . "/../Models/Profile.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";

class CourseController
{   
    protected $pdo;
    protected $model;
    protected $profileModel;
    protected $occupationModel;
    protected $alignmentModel;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new Course($pdo);
        $this->profileModel = new Profile($pdo);
        $this->occupationModel = new Occupation($pdo);
        $this->alignmentModel = new CourseAlignedOccupations($pdo);
    }

    public function create()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::CREATE_COURSES);
        $validatedName = Validator::validateText("course name", Request::fromBody("name"), "1-65");
        $validatedCode = Validator::validateText("course code", Request::fromBody("code"), "1-10");
        $cUserProfile = $this->profileModel->getDeanByUserId($cUser["id"]);

        if ($this->model->getByCode($validatedCode, $cUserProfile["school_id"])) {
            Response::json(["message" => "Course already exists."], 409);
        }

        $courseId = $this->model->create([
            "name" => $validatedName,
            "code" => $validatedCode,
            "school_id" => $cUserProfile["school_id"]
        ]);
        $course = $this->model->getById($courseId);
        Response::json($course, 201);
    }

    public function search()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::READ_COURSES);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "archived", Request::fromQuery("archived", false)],
        ]);
        $cUserProfile = $this->profileModel->getDeanByUserId($cUser["id"]);
        
        $paginator = new Paginator($this->pdo, "courses", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT * FROM courses",
            "WHERE archived = ? AND school_id = ? AND (name LIKE ? OR code LIKE ?)",
            [$validated["archived"], $cUserProfile["school_id"], "%{$q}%", "%{$q}%"]
        );
        Response::json($result);
    }

    public function searchOccupations()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::READ_OCCUPATIONS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::INTEGER, "course_id", Request::fromQuery("course_id")],
            [Validator::BOOLEAN, "aligned", Request::fromQuery("aligned", false)],
        ]);
        $cUserProfile = $this->profileModel->getDeanByUserId($cUser["id"]);
        $paginator = new Paginator($this->pdo, "occupations", $validated["page"], $validated["per_page"]);

        if ($validated["aligned"]) {
            $result = $paginator->run(
                "SELECT o.* FROM occupations o
                JOIN course_aligned_occupations cao ON cao.occupation_id = o.id
                JOIN courses c ON c.id = cao.course_id",
                "WHERE
                    c.school_id = ?
                    AND cao.course_id = ?
                    AND o.occupation LIKE ?
                ",
                [$cUserProfile["school_id"], $validated["course_id"], "%{$q}%"]
            );
        } else {
            $result = $paginator->run(
                "SELECT o.* FROM occupations o",
                "WHERE
                    o.id NOT IN (
                        SELECT cao.occupation_id
                        FROM course_aligned_occupations cao
                        JOIN courses c ON c.id = cao.course_id
                        WHERE
                            cao.course_id = ?
                            AND c.school_id = ?
                    )
                    AND o.occupation LIKE ?
                ",
                [$validated["course_id"], $cUserProfile["school_id"], "%{$q}%"]
            );
        }

        Response::json($result);
    }

    public function getAll() {
        // also accessible outside for alumni registration
        $course = $this->model->getAll();
        Response::json($course);
    }

    public function getAllActive()
    {
        $courses = $this->model->getAllActive();
        Response::json($courses);
    }

    public function getAllUnderDeanSchool()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::READ_COURSES);
        $cUserProfile = $this->profileModel->getDeanByUserId($cUser["id"]);
        $courses = $this->model->getAllBySchoolId($cUserProfile["school_id"]);
        Response::json($courses);
    }

    public function edit($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_COURSES);
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "id", $id],
            [Validator::TEXT, "name", Request::fromBody("name"), "1-65"],
            [Validator::TEXT, "code", Request::fromBody("code"), "1-10"],
        ]);
        
        $course = $this->model->getById($validated["id"]);
        $cUserProfile = $this->profileModel->getDeanByUserId($cUser["id"]);
        
        if (!$course) {
            Response::json(["message" => "Course not found."], 404);
        }

        if ($course["school_id"] !== $cUserProfile["school_id"]) {
            Response::json(["message" => "This course is not under your school."], 400);
        }

        if ($validated["name"] !== $course["name"]) {
            if ($this->model->getByName($validated["name"], $course["school_id"])) {
                Response::json(["message" => "A course with the same name already exists."], 409);
            }
        }

        if ($validated["code"] !== $course["code"]) {
            if ($this->model->getByCode($validated["code"], $course["school_id"])) {
                Response::json(["message" => "A course with the same code already exists."], 409);
            }
        }

        $course["name"] = $validated["name"];
        $course["code"] = $validated["code"];
        $tookEffect = $this->model->updateById($validated["id"], $course);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to edit course."], 500);
        }    
            
        Response::json([
            "message" => "Course has been editd.",
            "id" => $id,
        ]);
    }

    public function archive($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_COURSES);
        $validatedId = Validator::validateInteger("id", $id);
        
        $course = $this->model->getById($id);
        $cUserProfile = $this->profileModel->getDeanByUserId($cUser["id"]);
        
        if (!$course) {
            Response::json(["message" => "Course not found."], 404);
        }
        
        if ($course["school_id"] !== $cUserProfile["school_id"]) {
            Response::json(["message" => "This course is not under your school."], 400);
        }
        
        if ($course["archived"]) {
            Response::json(["message" => "Course is already archived."], 409);
        }

        $course["archived"] = true;
        $tookEffect = $this->model->updateById($validatedId, $course);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to archive course."], 500);
        }    
            
        Response::json([
            "message" => "Course has been archived.",
            "id" => $validatedId,
        ]);
    }

    public function restore($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_COURSES);
        $validatedId = Validator::validateInteger("id", $id);
        
        $course = $this->model->getById($validatedId);
        $cUserProfile = $this->profileModel->getDeanByUserId($cUser["id"]);
        
        if (!$course) {
            Response::json(["message" => "Course not found."], 404);
        }

        if ($course["school_id"] !== $cUserProfile["school_id"]) {
            Response::json(["message" => "This course is not under your school."], 400);
        }
        
        if (!$course["archived"]) {
            Response::json(["message" => "Course is already active."], 409);
        }

        $course["archived"] = false;
        $tookEffect = $this->model->updateById($validatedId, $course);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to restore course."], 500);
        }    
            
        Response::json([
            "message" => "Course has been restored.",
            "id" => $validatedId,
        ]);
    }

    public function alignOccupation()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::CREATE_ALIGNED_OCCUPATIONS);
        $validatedCourseId = Validator::validateInteger("course_id", Request::fromQuery("course_id"));
        $validatedOccupationId = Validator::validateInteger("occupation_id", Request::fromQuery("occupation_id"));

        if (!$this->model->getById($validatedCourseId)) {
            Response::json(["message" => "Course not found."], 404);
        }

        if (!$this->occupationModel->getById($validatedOccupationId)) {
            Response::json(["message" => "Occupation not found."], 404);
        }
        
        if ($this->alignmentModel->getByCourseAndOccupationIds($validatedCourseId, $validatedOccupationId)) {
            Response::json(["message" => "Occupation is already aligned to the course."], 409);
        }

        $alignmentId = $this->alignmentModel->create([
            "course_id" => $validatedCourseId,
            "occupation_id" => $validatedOccupationId,
        ]);

        if (!$alignmentId) {
            Response::json(["message" => "Unable to align occupation to the course."], 500);
        }

        Response::json(["message" => "Occupation has been aligned to the course."], 201);
    }

    public function unalignOccupation()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::DELETE_ALIGNED_OCCUPATIONS);
        $validatedCourseId = Validator::validateInteger("course_id", Request::fromQuery("course_id"));
        $validatedOccupationId = Validator::validateInteger("occupation_id", Request::fromQuery("occupation_id"));

        if (!$this->model->getById($validatedCourseId)) {
            Response::json(["message" => "Course not found."], 404);
        }

        if (!$this->occupationModel->getById($validatedOccupationId)) {
            Response::json(["message" => "Occupation not found."], 404);
        }
        
        if (!$this->alignmentModel->getByCourseAndOccupationIds($validatedCourseId, $validatedOccupationId)) {
            Response::json(["message" => "Occupation is already not aligned to the course."], 409);
        }

        $tookEffect = $this->alignmentModel->delete([
            "course_id" => $validatedCourseId,
            "occupation_id" => $validatedOccupationId,
        ]);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to unalign occupation to the course."], 500);
        }

        Response::json(["message" => "Occupation has been unaligned to the course."], 200);
    }
}