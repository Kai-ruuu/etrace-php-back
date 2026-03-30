<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Models/Vacancy.php";
require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ . "/../Models/Social.php";
require_once __DIR__ . "/../Models/Profile.php";
require_once __DIR__ . "/../Models/CompanyRevisionAppeal.php";
require_once __DIR__ . "/../Models/CompanyRevisionMessage.php";
require_once __DIR__ . "/../Models/CompanyRejectionAppeal.php";
require_once __DIR__ . "/../Models/CompanyRejectionMessage.php";
require_once __DIR__ . "/../Models/AlumniRejectionAppeal.php";
require_once __DIR__ . "/../Models/AlumniRejectionMessage.php";
require_once __DIR__ . "/../Services/MailingService.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";
require_once __DIR__ . "/../../utils/Upload.php";
require_once __DIR__ . "/../../utils/Password.php";

class ProfileController
{   
    protected MailingService $mailingSvc;
    protected $pdo;
    protected $model;
    protected $userModel;
    protected $vacancyModel;
    protected $companyRevisionAppealModel;
    protected $companyRejectionAppealModel;
    protected $companyRevisionMessageModel;
    protected $companyRejectionMessageModel;
    protected $socialModel;
    protected $alumniRejectionAppealModel;
    protected $alumniRejectionMessageModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new Profile($pdo);
        $this->userModel = new User($pdo);
        $this->vacancyModel = new Vacancy($pdo);
        $this->companyRevisionAppealModel = new CompanyRevisionAppeal($pdo);
        $this->companyRejectionAppealModel = new CompanyRejectionAppeal($pdo);
        $this->companyRevisionMessageModel = new CompanyRevisionMessage($pdo);
        $this->companyRejectionMessageModel = new CompanyRejectionMessage($pdo);
        $this->socialModel = new Social($pdo);
        $this->alumniRejectionAppealModel = new AlumniRejectionAppeal($pdo);
        $this->alumniRejectionMessageModel = new AlumniRejectionMessage($pdo);
        $mailingCfg = MailingConfig::buildFromEnv();
        $this->mailingSvc = new MailingService($mailingCfg);
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

        $user = $this->userModel->getCompanyByProfileId($validatedId);
        $sent = $this->mailingSvc->sendCompanyUnderReviewMail($cUser, $user);

        if ($sent) {
            Logger::info("Under review email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Under review email not sent");
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

        $user = $this->userModel->getCompanyByProfileId($validatedId);
        $sent = $this->mailingSvc->sendCompanyVerifiedMail($cUser, $user);

        if ($sent) {
            Logger::info("Verified email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Verified email not sent");
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

        $user = $this->userModel->getCompanyByProfileId($validatedId);
        $sent = $this->mailingSvc->sendCompanyRejectedMail($cUser, $user, $message);

        if ($sent) {
            Logger::info("Rejection email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Rejection email not sent");
        }
        
        Response::json(["message" => "Company has been rejected."]);
    }

    public function forReviseRequirement()
    {
        $cUser = UserGuard::run($this->pdo, [Role::PSTAFF], Action::UPDATE_REQUIREMENT);
        $companyId = Validator::validateInteger("company_id", Request::fromBody("company_id"));
        $message = Validator::validateText("message", Request::fromBody("message"), "1-0");
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

        $reqStatDisplay = [
            'stat_req_company_profile' => 'Company Profile',
            'stat_req_business_permit' => 'Business Permit',
            'stat_req_sec' => 'SEC',
            'stat_req_dti_cda' => 'DTI / CDA Reg.',
            'stat_req_reg_of_est' => 'Registry of Establishment fr. DOLE',
            'stat_req_cert_from_dole' => 'Certification from DOLE Provincial Office',
            'stat_req_cert_no_case' => 'Certification of No Pending Case',
            'stat_req_philjobnet_reg' => 'Phil-JobNet Reg.',
            'stat_req_list_of_vacancies' => 'List of Vacancies',
        ];

        $user = $this->userModel->getCompanyByProfileId($companyId);
        $sent = $this->mailingSvc->sendCompanyRequirementRevisionMail($cUser, $user, $reqStatDisplay[$requirementStat], $message);

        if ($sent) {
            Logger::info("Revision request email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Revision request email not sent");
        }

        Response::json(["message" => "Requirement has been marked for revision."]);
    }

    public function approveRequirement()
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

        $reqStatDisplay = [
            'stat_req_company_profile' => 'Company Profile',
            'stat_req_business_permit' => 'Business Permit',
            'stat_req_sec' => 'SEC',
            'stat_req_dti_cda' => 'DTI / CDA Reg.',
            'stat_req_reg_of_est' => 'Registry of Establishment fr. DOLE',
            'stat_req_cert_from_dole' => 'Certification from DOLE Provincial Office',
            'stat_req_cert_no_case' => 'Certification of No Pending Case',
            'stat_req_philjobnet_reg' => 'Phil-JobNet Reg.',
            'stat_req_list_of_vacancies' => 'List of Vacancies',
        ];

        $user = $this->userModel->getCompanyByProfileId($id);
        $sent = $this->mailingSvc->sendCompanyRequirementApprovedMail($cUser, $user, $reqStatDisplay[$requirementStat]);

        if ($sent) {
            Logger::info("Requirement approval email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Requirement approval email not sent");
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
                $messageAppeals = $this->companyRejectionMessageModel->getByCompanyIdAsSysad($id);
            } else {
                $messageAppeals = $this->companyRejectionMessageModel->getByCompanyIdAsPstaff($id);
            }
        } else {
            if ($cUser["role"] === Role::SYSAD) {
                $messageAppeals = $this->companyRejectionMessageModel->getByCompanyIdAsSysad($id);
            } else {
                $messageAppeals = $this->companyRejectionMessageModel->getByCompanyIdAsPstaff($id);
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
        
        $revisionsAndAppeals = $this->companyRevisionMessageModel->getRevisionNotesAndAppeals($id, $attrName);

        Response::json($revisionsAndAppeals);
    }

    public function reviseRequirement()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY], Action::UPDATE_REQUIREMENT);
        
        $fileKey = Validator::validateEnum("key", $_POST["key"], [
            "req_company_profile", "req_business_permit", "req_sec",
            "req_dti_cda", "req_reg_of_est", "req_philjobnet_reg",
            "req_cert_from_dole", "req_cert_no_case",
        ]);
        $fileDisplay = Validator::validateText("display", $_POST["display"], "1-50");
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

        $filename = $uploads->getFileName(0);
        $companyProfile[$fileKey] = $filename;
        
        if (in_array($companyProfile["ver_stat_pstaff"], ["Pending", "Verified"])) {
            $companyProfile["ver_stat_pstaff"] = "Pending";
        }
        
        $tookEffect = $this->model->updateCompanyById($companyProfile["id"], $companyProfile);

        if (!$tookEffect) {
            $uploads->rollback();
            Response::json(["message" => "Unable to update requirement."], 500);
        }

        $uploads->commit();
        Storage::delete(Storage::dest($fileDest), $currentFile);
        Response::json([
            "message" => "Requirement has been updated.",
            "filename" => $filename
        ]);
    }

    public function writeCompanyRejectionAppeal()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $message = Validator::validateText("message", Request::fromBody("message"), "1-0");
        $rejectionId = Validator::validateInteger("rejection_id", Request::fromBody("rejection_id"));

        $rejection = $this->companyRejectionMessageModel->getById($rejectionId);
        
        if (!$rejection) {
            Response::json(["message" => "Rejection not found."], 404);
        }

        $appealId = $this->companyRejectionAppealModel->create([
            "company_id" => $cUser["profile"]["id"],
            "rejection_id" => $rejectionId,
            "message" => $message,
        ]);

        if (!$appealId) {
            Response::json(["message" => "Unable to write an appeal"], 500);
        }

        Response::json(["message" => "Your appeal has been sent."], 201);
    }

    public function writeCompanyRevisionAppeal()
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $message = Validator::validateText("message", Request::fromBody("message"), "1-0");
        $revisionId = Validator::validateInteger("revision_id", Request::fromBody("revision_id"));

        $rejection = $this->companyRevisionMessageModel->getById($revisionId);
        
        if (!$rejection) {
            Response::json(["message" => "Revision request not found."], 404);
        }

        $appealId = $this->companyRevisionAppealModel->create([
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
        $rawQualifications = Request::fromBody("qualifications");
        $newQualifications = json_encode($rawQualifications);
        $vacancy = $this->vacancyModel->getById($valId);

        if (!$vacancy) {
            Response::json(["message" => "Vacancy not found."]);
        }

        if (count($rawQualifications) < 0) {
            Response::json(["message" => "There should be at least 1 qualification in a vacancy."], 400);
        }

        $vacancy["qualifications"] = $newQualifications;
        $tookEffect = $this->vacancyModel->updateById($valId, $vacancy);

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
        $qualifications = Request::fromBody("qualifications");

        if ($slots < 1) {
            Response::json(["message" => "There should be at least 1 available slot in a vacancy."], 400);
        }

        if (count(json_decode($qualifications)) < 0) {
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
        $vacancy["qualifications"] = json_encode($vacancy["qualifications"]);
        
        Response::json($vacancy, 201);
    }

    public function deleteVacancy($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $vacancyId = Validator::validateInteger("id", $id);
        $vacancy = $this->vacancyModel->getById($vacancyId);

        if (!$vacancy) {
            Response::json(["message" => "Vacancy not found."], 404);
        }

        $tookEffect = $this->vacancyModel->deleteById($vacancyId);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to remove vacancy."], 500);
        }
        
        Response::json(["message" => "Vacancy has been removed."]);
    }

    public function updateAlumniPersonalInfo()
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $civilStatus = Validator::validateEnum("civil status", Request::fromBody("civil_status"), [
            'Single', 'Married', 'Widowed', 'Separated'
        ]);
        $profile = $this->model->getAlumniById($cUser["profile"]["id"]);

        if (!$profile) {
            Response::json(["message" => "Alumni profile not found."], 404);
        }

        $profile["civil_status"] = $civilStatus;
        $tookEffect = $this->model->updateAlumniById($profile["id"], $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to update profile information."], 500);
        }

        Response::json(["message" => "Profile information has been updated."]);
    }

    public function updateAlumniContactInfo()
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $phoneNumber = Validator::validateText("phone number", Request::fromBody("phone_number"), "11-25");
        $address = Validator::validateText("address", Request::fromBody("address"), "1-512");
        $profile = $this->model->getAlumniById($cUser["profile"]["id"]);

        if (!$profile) {
            Response::json(["message" => "Alumni profile not found."], 404);
        }

        $profile["phone_number"] = $phoneNumber;
        $profile["address"] = $address;
        $tookEffect = $this->model->updateAlumniById($profile["id"], $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to update contact information."], 500);
        }

        Response::json(["message" => "Contact information has been updated."]);
    }

    public function updateAlumniCareerInfo() {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $profile = $this->model->getAlumniById($cUser["profile"]["id"]);

        $employmentStatus = Validator::validateEnum("employment status", Request::fromBody("employment_status"), [
            "Unemployed", "Employed", "Self-employed",
        ]);
        $occupations = json_decode(Request::fromBody("occupations"), true);

        if (!$profile) {
            Response::json(["message" => "Profile not found."], 404);
        }

        $tookEffect = $this->model->updateAlumniCareerInfoById($profile["id"], [
            "employment_status" => $employmentStatus,
            "occupations" => $occupations,
        ]);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to update career information"], 500);
        }

        Response::json(["message" => "Career information has been updated."]);
    }

    public function createAlumniSocial() {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $platform = Validator::validateText("platform", Request::fromBody("platform"), "1-25");
        $url = Validator::validateText("url", Request::fromBody("url"), "1-512");
        $alumniId = $cUser["profile"]["id"];

        $newSocialId = $this->socialModel->create([
            "platform" => $platform,
            "url" => $url,
            "alumni_id" => $alumniId,
        ]);

        if (!$newSocialId) {
            Response::json(["message" => "Unable to add social."], 500);
        }

        Response::json([
            "message" => "Social has been added.",
            "id" => $newSocialId
        ], 201);
    }

    public function deleteAlumniSocialById($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $id = Validator::validateInteger("id", $id);
        $tookEffect = $this->socialModel->deleteById($id);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to remove social."], 500);
        }

        Response::json(["message" => "Social has been removed."], 201);
    }

    public function pendAlumni($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_ALUMNI);
        $validatedId = Validator::validateInteger("id", $id);
        $profile = $this->model->getAlumniById($id);

        if (!$profile) {
            Response::json(["message" => "Alumni profile not found."], 404);
        }

        $profile["ver_stat_dean"] = "Pending";
        $tookEffect = $this->model->updateAlumniById($validatedId, $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to pend the alumni."], 500);
        }

        $user = $this->userModel->getAlumniByProfileId($validatedId);
        $sent = $this->mailingSvc->sendAlumniUnderReviewMail($cUser, $user);

        if ($sent) {
            Logger::info("Under review email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Under review email not sent");
        }

        Response::json(["message" => "Alumni has been enlisted as pending."]);
    }
    
    public function verifyAlumni($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_ALUMNI);
        $validatedId = Validator::validateInteger("id", $id);
        $profile = $this->model->getAlumniById($id);
        
        if (!$profile) {
            Response::json(["message" => "Profile not found."], 404);
        }

        $profile["ver_stat_dean"] = "Verified";
        $tookEffect = $this->model->updateAlumniById($validatedId, $profile);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to verify the alumni."], 500);
        }

        $user = $this->userModel->getAlumniByProfileId($validatedId);
        $sent = $this->mailingSvc->sendAlumniVerifiedMail($cUser, $user);

        if ($sent) {
            Logger::info("Verified email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Verified email not sent");
        }
        
        Response::json(["message" => "Alumni has been verified."]);
    }

    public function rejectAlumni($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::UPDATE_ALUMNI);
        $validatedId = Validator::validateInteger("id", $id);
        $message = Validator::validateText("reason", Request::fromBody("message"), "1-0");
        $alumniProfile = $this->model->getAlumniById($id);

        if (!$alumniProfile) {
            Response::json(["message" => "Alumni profile not found."], 404);
        }

        $alumniProfile["ver_stat_sysad"] = "Rejected";
        $tookEffect = $this->model->rejectAlumni([
            "dean_id" => $cUser["profile"]["id"],
            "alumni_id" => $id,
            "message" => $message,
        ]);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to reject the alumni."], 500);
        }

        $user = $this->userModel->getAlumniByProfileId($validatedId);
        $sent = $this->mailingSvc->sendAlumniRejectedMail($cUser, $user, $message);

        if ($sent) {
            Logger::info("Rejection email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Rejection email not sent");
        }

        Response::json(["message" => "Alumni has been rejected."]);
    }

    public function getAlumniRejectionAppeals($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN, Role::ALUMNI]);
        $id = Validator::validateInteger("id", $id);

        if (!$this->model->getAlumniById($id)) {
            Response::json(["message" => "Alumni profile not found."], 404);
        }

        $messageAppeals = $this->alumniRejectionMessageModel->getByAlumniId($id);
        Response::json($messageAppeals);
    }

    public function writeAlumniRejectionAppeal()
    {
        $cUser = UserGuard::run($this->pdo, [Role::ALUMNI]);
        $message = Validator::validateText("message", Request::fromBody("message"), "1-0");
        $rejectionId = Validator::validateInteger("rejection_id", Request::fromBody("rejection_id"));

        $rejection = $this->alumniRejectionMessageModel->getById($rejectionId);
        
        if (!$rejection) {
            Response::json(["message" => "Rejection not found."], 404);
        }

        $appealId = $this->alumniRejectionAppealModel->create([
            "alumni_id" => $cUser["profile"]["id"],
            "rejection_id" => $rejectionId,
            "message" => $message,
        ]);

        if (!$appealId) {
            Response::json(["message" => "Unable to write an appeal"], 500);
        }

        Response::json(["message" => "Your appeal has been sent."], 201);
    }

    public function updateProfile($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $address = Validator::validateText("address", $_POST["address"], "1-512");
        $logo = isset($_FILES["logo"]) ? $_FILES["logo"] : null;
        $hasLogo = !empty($logo) && $logo["error"] === UPLOAD_ERR_OK;
        $companyProfile = $this->model->getCompanyById($cUser["profile"]["id"]);
        $currentLogo = $cUser["profile"]["req_logo"];
        
        $addressChanged = $companyProfile["address"] !== $address;

        if (!$hasLogo && !$addressChanged) {
            Response::json(["message" => "No changes were made."]);
        }

        $uploads = null;

        if ($hasLogo) {
            $uploads = new Uploads([
                new Upload("logo", Storage::dest("logo"), "logo", ["image/png", "image/jpg", "image/jpeg"]),
            ]);
            $uploads->stage();
            $errs = $uploads->getErrors();

            if (!empty($errs)) {
                $uploads->rollback();
                Response::json(["message" => $errs[0]], 422);
            }

            $companyProfile["req_logo"] = $uploads->getFileName(0);
        }

        if ($addressChanged) {
            $companyProfile["address"] = $address;
        }

        $tookEffect = $this->model->updateCompanyById($companyProfile["id"], $companyProfile);

        if (!$tookEffect) {
            if ($hasLogo) $uploads->rollback();
            Response::json(["message" => "Unable to save changes."], 500);
        }

        if ($hasLogo) {
            $uploads->commit();
            Storage::delete(Storage::dest("logo"), $currentLogo);
        }

        Response::json(["message" => "Changes saved."]);
    }
}