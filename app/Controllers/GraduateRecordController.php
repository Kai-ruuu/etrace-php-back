<?php

require_once __DIR__ . "/../Models/Course.php";
require_once __DIR__ . "/../Models/Profile.php";
require_once __DIR__ . "/../Models/GraduateRecord.php";
require_once __DIR__ . "/../../utils/Upload.php";
require_once __DIR__ . "/../../utils/GraduateRecordValidator.php";

class GraduateRecordController
{
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new GraduateRecord($pdo);
        $this->courseModel = new Course($pdo);
        $this->profileModel = new Profile($pdo);
    }

    public function create()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::CREATE_RECORDS);
        $validatedCourseId = Validator::validateInteger("course_id", $_POST["course_id"]);

        if (!$this->courseModel->getById($validatedCourseId)) {
            Response::json(["message" => "Course not found."], 404);
        }

        // validate formatting and data first
        GraduateRecordValidator::validate("graduate_record");
        
        // validate remaining
        $uploads = new Uploads([
            new Upload("graduate record", Storage::dest("graduate_records"), "graduate_record", ["text/csv"])
        ]);
        $uploads->stage();
        $errs = $uploads->getErrors();

        if (!empty($errs)) {
            $uploads->rollback();
            Response::json(["message" => $errs[0]], 422);
        }

        $uploads->commit();

        $cUserProfile = $this->profileModel->getDeanByUserId($cUser["id"]);
        $recordId = $this->model->create([
            "filename" => $uploads->getFilename(0),
            "course_id" => $validatedCourseId,
            "dean_uploader_id" => $cUserProfile["id"]
        ]);
        $record = $this->model->getById($recordId);
        Response::json($record, 201);
    }

    public function search()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::READ_RECORDS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::INTEGER, "course_id", Request::fromQuery("course_id")],
            [Validator::BOOLEAN, "archived", Request::fromQuery("archived", false)],
        ]);

        $paginator = new Paginator($this->pdo, "graduate_records", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT
                gr.id AS grid,
                gr.filename AS grfilename,
                gr.course_id AS grcourse_id,
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
            JOIN deans d ON gr.dean_uploader_id = d.id",
            "WHERE
                gr.archived = ?
                AND gr.course_id = ?
                AND gr.filename LIKE ?
            ",
            [$validated["archived"], $validated["course_id"], "%{$q}%"],
            [GraduateRecord::class, "format"]
        );
        Response::json($result);
    }

    public function archive($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_RECORDS);
        $validatedId = Validator::validateInteger("id", $id);
        $record = $this->model->getById($id);
        
        if (!$record) {
            Response::json(["message" => "Graduate record not found."], 404);
        }
        
        if ($record["archived"]) {
            Response::json(["message" => "Graduate record is already archived."], 409);
        }

        $record["archived"] = true;
        $tookEffect = $this->model->updateById($validatedId, $record);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to archive graduate record."], 500);
        }
            
        Response::json([
            "message" => "Graduate record has been archived.",
            "id" => $validatedId,
        ]);
    }

    public function restore($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_RECORDS);
        $validatedId = Validator::validateInteger("id", $id);
        $record = $this->model->getById($validatedId);
        
        if (!$record) {
            Response::json(["message" => "Graduate record not found."], 404);
        }
        
        if (!$record["archived"]) {
            Response::json(["message" => "Graduate record is already active."], 409);
        }

        $record["archived"] = false;
        $tookEffect = $this->model->updateById($validatedId, $record);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to restore graduate record."], 500);
        }    
            
        Response::json([
            "message" => "Graduate record has been restored.",
            "id" => $validatedId,
        ]);
    }
}