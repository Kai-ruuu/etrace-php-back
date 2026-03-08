<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Models/Vacancy.php";
require_once __DIR__ . "/../Models/Profile.php";
require_once __DIR__ . "/../Models/CompanyRevisionAppeal.php";
require_once __DIR__ . "/../Models/CompanyRevisionMessage.php";
require_once __DIR__ . "/../Models/CompanyRejectionAppeal.php";
require_once __DIR__ . "/../Models/CompanyRejectionMessage.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";
require_once __DIR__ . "/../../utils/Upload.php";
require_once __DIR__ . "/../../utils/Password.php";

class ProfileController
{   
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new Profile($pdo);
        $this->vacancyModel = new Vacancy($pdo);
        $this->revisionAppealModel = new CompanyRevisionAppeal($pdo);
        $this->rejectionAppealModel = new CompanyRejectionAppeal($pdo);
        $this->revisionMessageModel = new CompanyRevisionMessage($pdo);
        $this->rejectionMessageModel = new CompanyRejectionMessage($pdo);
    }

    public function updateSysad()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::UPDATE_PROFILE);
        $validated = Validator::batchValidate([
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-65"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-65"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-65"],
        ]);
        $profileId = $cUser["profile"]["id"];
        $profile = $this->model->getSysadById($profileId);

        if (!$profile) {
            Response::json(["message" => "Profile not found."], 404);
        }

        $profile["first_name"] = $validated["first name"];
        $profile["middle_name"] = $validated["middle name"];
        $profile["last_name"] = $validated["last name"];
        $tookEffect = $this->model->updateSysadById($profileId, $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to update profile."], 500);
        }

        Response::json(["message" => "Profile has been updated."]);
    }

    public function updateDean()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_PROFILE);
        $validated = Validator::batchValidate([
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-65"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-65"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-65"],
        ]);
        $profileId = $cUser["profile"]["id"];
        $profile = $this->model->getDeanById($profileId);

        if (!$profile) {
            Response::json(["message" => "Profile not found."], 404);
        }

        $profile["first_name"] = $validated["first name"];
        $profile["middle_name"] = $validated["middle name"];
        $profile["last_name"] = $validated["last name"];
        $tookEffect = $this->model->updateDeanById($profileId, $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to update profile."], 500);
        }

        Response::json(["message" => "Profile has been updated."]);
    }

    public function updatePstaff()
    {
        $cUser = UserGuard::run($this->pdo, [Role::PSTAFF], Action::UPDATE_PROFILE);
        $validated = Validator::batchValidate([
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-65"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-65"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-65"],
        ]);
        $profileId = $cUser["profile"]["id"];
        $profile = $this->model->getPstaffById($profileId);

        if (!$profile) {
            Response::json(["message" => "Profile not found."], 404);
        }

        $profile["first_name"] = $validated["first name"];
        $profile["middle_name"] = $validated["middle name"];
        $profile["last_name"] = $validated["last name"];
        $tookEffect = $this->model->updatePstaffById($profileId, $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to update profile."], 500);
        }

        Response::json(["message" => "Profile has been updated."]);
    }

    public function pendCompany($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD, Role::PSTAFF], Action::UPDATE_COMPANY);
        $validatedId = Validator::validateInteger("id", $id);
        $companyProfile = $this->model->getCompanyById($id);

        if (!$companyProfile) {
            Response::json(["message" => "Company profile not found."], 404);
        }

        if ($cUser["role"] === Role::SYSAD) {
            $companyProfile["ver_stat_sysad"] = "Pending";
            $tookEffect = $this->model->updateCompanyById($validatedId, $companyProfile);
        } else {
            $companyProfile["ver_stat_pstaff"] = "Pending";
            $tookEffect = $this->model->updateCompanyById($validatedId, $companyProfile);
        }

        if (!$tookEffect) {
            Response::json(["message" => "Unable to pend the company."], 500);
        }

        Response::json(["message" => "Company has been enlisted as pending."]);
    }
    
    public function verifyCompany($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD, Role::PSTAFF], Action::UPDATE_COMPANY);
        $validatedId = Validator::validateInteger("id", $id);
        $profile = $this->model->getCompanyById($id);
        
        if (!$profile) {
            Response::json(["message" => "Profile not found."], 404);
        }

        if ($cUser["role"] === Role::SYSAD) {
            $profile["ver_stat_sysad"] = "Verified";
        } else {
            if (!(
                $profile["stat_req_company_profile"]   === "Approved" &&
                $profile["stat_req_business_permit"]   === "Approved" &&
                $profile["stat_req_sec"]               === "Approved" &&
                $profile["stat_req_dti_cda"]           === "Approved" &&
                $profile["stat_req_reg_of_est"]        === "Approved" &&
                $profile["stat_req_cert_from_dole"]    === "Approved" &&
                $profile["stat_req_cert_no_case"]      === "Approved" &&
                $profile["stat_req_philjobnet_reg"]    === "Approved" &&
                $profile["stat_req_list_of_vacancies"] === "Approved"
            )) {
                Response::json(["message" => "The company's requirements are still not fully approved."], 404);
            }

            $profile["ver_stat_pstaff"] = "Verified";
        }

        $tookEffect = $this->model->updateCompanyById($validatedId, $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to verify the company."], 500);
        }

        Response::json(["message" => "Company has been verified."]);
    }

    public function rejectCompany($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD, Role::PSTAFF], Action::UPDATE_COMPANY);
        $validatedId = Validator::validateInteger("id", $id);
        $message = Validator::validateText("reason", Request::fromBody("message"), "1-0");
        $companyProfile = $this->model->getCompanyById($id);

        if (!$companyProfile) {
            Response::json(["message" => "Company profile not found."], 404);
        }

        if ($cUser["role"] === Role::SYSAD) {
            $companyProfile["ver_stat_sysad"] = "Rejected";
            $tookEffect = $this->model->rejectCompany([
                "sysad_id" => $cUser["profile"]["id"],
                "pstaff_id" => null,
                "company_id" => $id,
                "message" => $message,
            ]);
        } else {
            $companyProfile["ver_stat_pstaff"] = "Rejected";
            $tookEffect = $this->model->rejectCompany([
                "sysad_id" => null,
                "pstaff_id" => $cUser["profile"]["id"],
                "company_id" => $id,
                "message" => $message,
            ]);
        }

        if (!$tookEffect) {
            Response::json(["message" => "Unable to reject the company."], 500);
        }

        Response::json(["message" => "Company has been rejected."]);
    }

    public function forReviseRequrement()
    {
        $cUser = UserGuard::run($this->pdo, [Role::PSTAFF], Action::UPDATE_REQUIREMENT);
        $companyId = Validator::validateInteger("company_id", Request::fromBody("company_id"));
        $message = Validator::validateText("message", Request::fromBody("message"), "1-0");
        $requirementStat = Validator::validateText("requirement_stat", Request::fromBody("requirement_stat"), "1-30");
        $companyProfile = $this->model->getCompanyById($companyId);

        if (!$companyProfile) {
            Response::json(["message" => "Company profile not found."], 404);
        }

        $tookEffect = $this->model->forReviseComanyRequirement([
            "pstaff_id" => $cUser["profile"]["id"],
            "company_id" => $companyId,
            "message" => $message,
            "requirement_stat" => $requirementStat
        ]);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to mark requirement as for revision."], 500);
        }

        Response::json(["message" => "Requirement has been marked for revision."]);
    }

    public function approveRequrement()
    {
        $cUser = UserGuard::run($this->pdo, [Role::PSTAFF], Action::UPDATE_REQUIREMENT);
        $id = Validator::validateInteger("id", Request::fromBody("id"));
        $requirementStat = Validator::validateEnum("requirement_stat", Request::fromBody("requirement_stat"), [
            "stat_req_company_profile",
            "stat_req_business_permit",
            "stat_req_sec",
            "stat_req_dti_cda",
            "stat_req_reg_of_est",
            "stat_req_cert_from_dole",
            "stat_req_cert_no_case",
            "stat_req_philjobnet_reg",
            "stat_req_list_of_vacancies",
        ]);

        $profile = $this->model->getCompanyById($id);
        
        if (!$profile) {
            Response::json(["message" => "Profile not found."], 404);
        }

        if ($profile[$requirementStat] === "Approved") {
            Response::json(["message" => "Requirement is already approved."], 409);
        }

        $profile[$requirementStat] = "Approved";
        $tookEffect = $this->model->updateCompanyById($id, $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to approve requirement."], 500);
        }

        Response::json(["message" => "Requirement has been approved."]);
    }

    public function getCompanyRejectionAppeals($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD, Role::PSTAFF, Role::COMPANY]);
        $id = Validator::validateInteger("id", $id);
        $sourceRole = Request::fromQuery("source_role", null);

        if ($sourceRole !== null) {
            $sourceRole = Validator::validateEnum("source_role", $sourceRole, [Role::SYSAD, Role::PSTAFF]);
        }

        if (!$this->model->getCompanyById($id)) {
            Response::json(["message" => "Company profile not found."], 404);
        }

        if ($sourceRole !== null) {
            if ($sourceRole === Role::SYSAD) {
                $messageAppeals = $this->rejectionMessageModel->getByCompanyIdAsSysad($id);
            } else {
                $messageAppeals = $this->rejectionMessageModel->getByCompanyIdAsPstaff($id);
            }
        } else {
            if ($cUser["role"] === Role::SYSAD) {
                $messageAppeals = $this->rejectionMessageModel->getByCompanyIdAsSysad($id);
            } else {
                $messageAppeals = $this->rejectionMessageModel->getByCompanyIdAsPstaff($id);
            }
        }

        Response::json($messageAppeals);
    }

    public function getCompanyNotesAndAppeals($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::PSTAFF, Role::COMPANY]);
        $id = Validator::validateInteger("id", $id);
        $attrName = Validator::validateEnum("attr_name", Request::fromQuery("attr_name"), [
            "req_company_profile",
            "req_business_permit",
            "req_sec",
            "req_dti_cda",
            "req_reg_of_est",
            "req_cert_from_dole",
            "req_cert_no_case",
            "req_philjobnet_reg",
            "req_list_of_vacancies",
        ]);

        if (!$this->model->getCompanyById($id)) {
            Response::json(["message" => "Company profile not found."], 404);
        }
        
        $revisionsAndAppeals = $this->revisionMessageModel->getRevisionNotesAndAppeals($id, $attrName);

        Response::json($revisionsAndAppeals);
    }

    public function reviseRequirement()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY], Action::UPDATE_REQUIREMENT);

        error_log("Key: " . $_POST["key"] . " Display: " . $_POST["display"] . " Source: " .  $_POST["source"]);
        
        $fileKey = Validator::validateEnum("key", $_POST["key"], [
            "req_company_profile", "req_business_permit", "req_sec",
            "req_dti_cda", "req_reg_of_est", "req_philjobnet_reg",
            "req_cert_from_dole", "req_cert_no_case",
        ]);
        $fileDisplay = Validator::validateText("display", $_POST["display"], "1-30");
        $fileDest = Validator::validateEnum("source", $_POST["source"], [
            "profile", "permit", "sec", "dti_cda", "reg_of_est",
            "reg_philjobnet", "cert_from_dole", "cert_no_case",
        ]);

        $companyProfile = $this->model->getCompanyById($cUser["profile"]["id"]);
        $currentFile = $cUser["profile"][$fileKey];

        $uploads = new Uploads([
            new Upload($fileDisplay, Storage::dest($fileDest), "file", ["application/pdf"]),
        ]);
        $uploads->stage();
        $errs = $uploads->getErrors();

        if (!empty($errs)) {
            $uploads->rollback();
            Response::json(["message" => $errs[0]], 422);
        }

        $companyProfile[$fileKey] = $uploads->getFileName(0);
        $tookEffect = $this->model->updateCompanyById($companyProfile["id"], $companyProfile);

        if (!$tookEffect) {
            $uploads->rollback();
            Response::json(["message" => "Unable to update requirement."], 500);
        }

        $uploads->commit();
        Storage::delete(Storage::dest($fileDest), $currentFile);
        Response::json(["message" => "Requirement has been updated."]);
    }

    public function writeRejectionAppeal()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $message = Validator::validateText("message", Request::fromBody("message"), "1-0");
        $rejectionId = Validator::validateInteger("rejection_id", Request::fromBody("rejection_id"));

        $rejection = $this->rejectionMessageModel->getById($rejectionId);
        
        if (!$rejection) {
            Response::json(["message" => "Rejection not found."], 404);
        }

        $appealId = $this->rejectionAppealModel->create([
            "company_id" => $cUser["profile"]["id"],
            "rejection_id" => $rejectionId,
            "message" => $message,
        ]);

        if (!$appealId) {
            Response::json(["message" => "Unable to write an appeal"], 500);
        }

        Response::json(["message" => "Your appeal has been sent."], 201);
    }

    public function writeRevisionAppeal()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $message = Validator::validateText("message", Request::fromBody("message"), "1-0");
        $revisionId = Validator::validateInteger("revision_id", Request::fromBody("revision_id"));

        $rejection = $this->revisionMessageModel->getById($revisionId);
        
        if (!$rejection) {
            Response::json(["message" => "Revision request not found."], 404);
        }

        $appealId = $this->revisionAppealModel->create([
            "company_id" => $cUser["profile"]["id"],
            "resubmit_id" => $revisionId,
            "message" => $message,
        ]);

        if (!$appealId) {
            Response::json(["message" => "Unable to write an appeal"], 500);
        }

        Response::json(["message" => "Your appeal has been sent."], 201);
    }

    public function editVacancy($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $valId = Validator::validateInteger("id", $id);
        $newSlots = Validator::validateInteger("slots", Request::fromBody("slots"));
        $newName = Validator::validateText("newName", Request::fromBody("newName"), "1-255");
        $vacancy = $this->vacancyModel->getById($valId);

        if (!$vacancy) {
            Response::json(["message" => "Vacancy not found."]);
        }

        $vacancy["slots"] = $newSlots;
        $vacancy["job_title"] = $newName;
        $tookEffect = $this->vacancyModel->updateById($id, $vacancy);

        if (!$tookEffect) {
            Response::json(["message" => "Failed to update vancancy."], 500);
        }

        Response::json(["message" => "Vacancy has been updated."]);
    }

    public function editVacancyQualifications($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $valId = Validator::validateInteger("id", $id);
        $newQualifications = Validator::validateText("qualifications", json_encode(Request::fromBody("qualifications")), "1-0");
        $vacancy = $this->vacancyModel->getById($valId);

        if (!$vacancy) {
            Response::json(["message" => "Vacancy not found."]);
        }

        $vacancy["qualifications"] = $newQualifications;
        $tookEffect = $this->vacancyModel->updateById($id, $vacancy);

        if (!$tookEffect) {
            Response::json(["message" => "Failed to update qualification."], 500);
        }

        Response::json(["message" => "Qualification has been updated."]);
    }

    public function addVacancy()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $jobTitle = Validator::validateText("job_title", Request::fromBody("job_title"), "1-255");
        $slots = Validator::validateInteger("slots", Request::fromBody("slots"));
        $rawQualifications = Request::fromBody("qualifications");
        $qualifications = Validator::validateText("qualifications", json_encode($rawQualifications), "1-0");

        if ($slots < 1) {
            Response::json(["message" => "There should be at least 1 available slot in a vacancy."], 400);
        }

        if (count(json_decode($rawQualifications)) < 0) {
            Response::json(["message" => "There should be at least 1 qualification in a vacancy."], 400);
        }

        $vacancyId = $this->vacancyModel->create([
            "company_id" => $cUser["profile"]["id"],
            "job_title" => $jobTitle,
            "slots" => $slots,
            "qualifications" => $qualifications
        ]);

        if (!$vacancyId) {
            Response::json(["message" => "Unable to add vacancy."], 500);
        }

        $vacancy = $this->vacancyModel->getById($vacancyId);
        $vacancy = Vacancy::format($vacancy);
        
        Response::json($vacancy, 201);
    }
}