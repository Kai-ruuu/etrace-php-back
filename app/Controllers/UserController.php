<?php

require_once __DIR__ . "/../Core/Request.php";
require_once __DIR__ . "/../Core/Actions.php";
require_once __DIR__ . "/../Core/Response.php";
require_once __DIR__ . "/../Core/Constants.php";
require_once __DIR__ . "/../Core/Validator.php";
require_once __DIR__ . "/../Core/Paginator.php";
require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ . "/../Middlewares/UserGuard.php";
require_once __DIR__ . "/../../utils/Upload.php";
require_once __DIR__ . "/../../utils/Password.php";
require_once __DIR__ . "/../Services/MailingService.php";

class UserController
{   
    protected $pdo;
    protected $model;
    protected MailingService $mailingSvc;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->model = new User($pdo);
        $mailingCfg = MailingConfig::buildFromEnv();
        $this->mailingSvc = new MailingService($mailingCfg);
    }

    public function createSysad()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::CREATE_SYSADS);
        $passwordHash = Password::generate();
        $validated = Validator::batchValidate([
            [Validator::EMAIL, "email", Request::fromBody("email")],
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-50"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-50"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-50"],
        ]);

        if ($this->model->getByEmail($validated["email"])) {
            Response::json(["message" => "System Administrator already exists."], 409);
        }

        $userId = $this->model->createSysad([
            "email" => $validated["email"],
            "password_hash" => $passwordHash,
            "first_name" => $validated["first name"],
            "middle_name" => $validated["middle name"],
            "last_name" => $validated["last name"],
        ]);

        if ($userId === null) {
            Response::json(["message" => "Unable to create system administrator."], 500);
        }

        $user = $this->model->getSysadById($userId);
        $sent = $this->mailingSvc->sendNewlyAssignedMail($cUser, $user);

        if ($sent) {
            Logger::info("Assignment email sent");
        } else {
            Logger::error(Logger::ERR_MAILING_SERVICE, "Assignment email not sent");
        }

        Response::json($user, 201);
    }

    public function searchSysads()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::READ_SYSADS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "enabled", Request::fromQuery("enabled", true)],
        ]);
        $paginator = new Paginator($this->pdo, "users", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT
                u.id AS uid,
                u.email AS uemail,
                u.role AS urole,
                u.enabled AS uenabled,
                u.created_at AS ucreated_at,
                u.updated_at AS uupdated_at,
                p.id AS pid,
                p.user_id AS puser_id,
                p.first_name AS pfirst_name,
                p.middle_name AS pmiddle_name,
                p.last_name AS plast_name
            FROM users u
            JOIN sysads p ON p.user_id = u.id
            ",
            "WHERE
                u.role = 'sysad'
                AND u.enabled = ?
                AND (
                    u.email LIKE ? OR
                    p.first_name LIKE ? OR
                    p.middle_name LIKE ? OR
                    p.last_name LIKE ?
                )
            ",
            [$validated["enabled"], "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"],
            [User::class, "format"]
        );
        Response::json($result);
    }

    public function enableSysad($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_SYSADS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "System Administrator does not exist."], 404);
        }
            
        if ($user["email"] === $_ENV["SYSAD_EMAIL"]) {
            Response::json(["message" => "User info should not be modified."], 403);
        }

        $user["enabled"] = true;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to enable system administrator."], 500);
        }    
        
        $this->mailingSvc->sendEnableMail($cUser, $user);
        Response::json([
            "message" => "System administrator has been enabled.",
            "id" => $validatedId,
        ]);
    }

    public function disableSysad($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_SYSADS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "System Administrator does not exist."], 404);
        }
            
        if ($user["email"] === $_ENV["SYSAD_EMAIL"]) {
            Response::json(["message" => "User info should not be modified."], 403);
        }

        $user["enabled"] = false;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to disable system administrator."], 500);
        }    
        
        $this->mailingSvc->sendDisableMail($cUser, $user);
        Response::json([
            "message" => "System administrator has been disabled.",
            "id" => $validatedId,
        ]);
    }

    public function createDean()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::CREATE_DEANS);
        $passwordHash = Password::generate();
        $validated = Validator::batchValidate([
            [Validator::EMAIL, "email", Request::fromBody("email")],
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-50"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-50"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-50"],
            [Validator::INTEGER, "school id", Request::fromBody("school_id")],
        ]);

        if ($this->model->getByEmail($validated["email"])) {
            Response::json(["message" => "Dean already exists."], 409);
        }

        $userId = $this->model->createDean([
            "email" => $validated["email"],
            "password_hash" => $passwordHash,
            "first_name" => $validated["first name"],
            "middle_name" => $validated["middle name"],
            "last_name" => $validated["last name"],
            "school_id" => $validated["school id"],
        ]);

        if ($userId === null) {
            Response::json(["message" => "Unable to create dean."], 500);
        }

        $user = $this->model->getDeanById($userId);
        $this->mailingSvc->sendNewlyAssignedMail($cUser, $user);
        Response::json($user, 201);
    }

    public function searchDeans()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::READ_DEANS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "school_id", Request::fromQuery("school_id")],
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "enabled", Request::fromQuery("enabled", true)],
        ]);
        $paginator = new Paginator($this->pdo, "users", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT
                u.id AS uid,
                u.email AS uemail,
                u.role AS urole,
                u.enabled AS uenabled,
                u.created_at AS ucreated_at,
                u.updated_at AS uupdated_at,
                p.id AS pid,
                p.school_id AS pschool_id,
                p.user_id AS puser_id,
                p.first_name AS pfirst_name,
                p.middle_name AS pmiddle_name,
                p.last_name AS plast_name,
                s.id AS sid,
                s.name AS sname
            FROM users u
            JOIN deans p ON p.user_id = u.id
            JOIN schools s ON s.id = p.school_id
            ",
            "WHERE
                u.role = 'dean'
                AND u.enabled = ?
                AND p.school_id = ?
                AND (
                    u.email LIKE ? OR
                    p.first_name LIKE ? OR
                    p.middle_name LIKE ? OR
                    p.last_name LIKE ? OR
                    s.name LIKE ?
                )
            ",
            [$validated["enabled"], $validated["school_id"], "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"],
            [User::class, "format"]
        );
        Response::json($result);
    }

    public function enableDean($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_DEANS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "Dean does not exist."], 404);
        }

        $user["enabled"] = true;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to enable dean."], 500);
        }    
        
        $this->mailingSvc->sendEnableMail($cUser, $user);
        Response::json([
            "message" => "Dean has been enabled.",
            "id" => $validatedId,
        ]);
    }

    public function disableDean($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_DEANS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "Dean does not exist."], 404);
        }

        $user["enabled"] = false;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to disable dean."], 500);
        }    
        
        $this->mailingSvc->sendDisableMail($cUser, $user);
        Response::json([
            "message" => "Dean has been disabled.",
            "id" => $validatedId,
        ]);
    }

    public function createPstaff()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::CREATE_PSTAFFS);
        $passwordHash = Password::generate();
        $validated = Validator::batchValidate([
            [Validator::EMAIL, "email", Request::fromBody("email")],
            [Validator::TEXT, "first name", Request::fromBody("first_name"), "1-50"],
            [Validator::TEXT, "middle name", Request::fromBody("middle_name"), "0-50"],
            [Validator::TEXT, "last name", Request::fromBody("last_name"), "1-50"],
        ]);

        if ($this->model->getByEmail($validated["email"])) {
            Response::json(["message" => "PESO Staff already exists."], 409);
        }

        $userId = $this->model->createPstaff([
            "email" => $validated["email"],
            "password_hash" => $passwordHash,
            "first_name" => $validated["first name"],
            "middle_name" => $validated["middle name"],
            "last_name" => $validated["last name"],
        ]);

        if ($userId === null) {
            Response::json(["message" => "Unable to create peso staff."], 500);
        }

        $user = $this->model->getPstaffById($userId);
        $this->mailingSvc->sendNewlyAssignedMail($cUser, $user);
        Response::json($user, 201);
    }

    public function searchPstaffs()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::READ_PSTAFFS);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "enabled", Request::fromQuery("enabled", true)],
        ]);
        $paginator = new Paginator($this->pdo, "users", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT
                u.id AS uid,
                u.email AS uemail,
                u.role AS urole,
                u.enabled AS uenabled,
                u.created_at AS ucreated_at,
                u.updated_at AS uupdated_at,
                p.id AS pid,
                p.user_id AS puser_id,
                p.first_name AS pfirst_name,
                p.middle_name AS pmiddle_name,
                p.last_name AS plast_name
            FROM users u
            JOIN pstaffs p ON p.user_id = u.id
            ",
            "WHERE
                u.role = 'pstaff'
                AND u.enabled = ?
                AND (
                    u.email LIKE ? OR
                    p.first_name LIKE ? OR
                    p.middle_name LIKE ? OR
                    p.last_name LIKE ?
                )
            ",
            [$validated["enabled"], "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"],
            [User::class, "format"]
        );
        Response::json($result);
    }

    public function enablePstaff($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_PSTAFFS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "PESO Staff does not exist."], 404);
        }

        $user["enabled"] = true;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to enable peso staff."], 500);
        }    
        
        $this->mailingSvc->sendEnableMail($cUser, $user);
        Response::json([
            "message" => "PESO Staff has been enabled.",
            "id" => $validatedId,
        ]);
    }

    public function disablePstaff($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_PSTAFFS);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "PESO Staff does not exist."], 404);
        }

        $user["enabled"] = false;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to disable peso staff."], 500);
        }    
        
        $this->mailingSvc->sendDisableMail($cUser, $user);
        Response::json([
            "message" => "PESO Staff has been disabled.",
            "id" => $validatedId,
        ]);
    }

    public function createCompany()
    {
        $validated = Validator::batchValidate([
            [Validator::EMAIL, "email", $_POST["email"] ?? "", "1-255"],
            [Validator::TEXT, "password", $_POST["password"] ?? "", "8-65"],
            [Validator::TEXT, "name", $_POST["name"] ?? "", "1-255"],
            [Validator::TEXT, "address", $_POST["address"] ?? "", "1-512"],
            [Validator::ENUM, "industry", $_POST["industry"] ?? "", [
                'Technology / IT','Finance / Banking / Insurance','Healthcare / Pharmaceuticals',
                'Education / Research','Manufacturing / Industrial','Retail / E-commerce',
                'Food & Beverage / Hospitality','Transportation / Logistics','Energy / Utilities',
                'Media / Entertainment / Advertising','Government / Public Sector',
                'Real Estate / Construction','Consulting / Professional Services','Nonprofit / NGO',
                'Telecommunications'
            ]],
        ]);
        $passwordHash = Password::hash($validated["password"]);
        
        $vacancies = json_decode($_POST["vacancies"], true);

        foreach ($vacancies as $vacancy) {
            $jobTitle = trim($vacancy["job_title"] ?? "");
            $slots = Validator::validateInteger("slots", $vacancy["slots"]);
            $qualifications = $vacancy["qualifications"] ?? [];
            
            if (empty($jobTitle)) {
                Response::json(["message" => "A vacancy should have a job title."], 400);
            }
            
            if ($slots <= 0) {
                Response::json(["message" => "A vacancy should have at least 1 slot open."], 400);
            }
                
            if (empty($qualifications)) {
                Response::json(["message" => "A vacancy should have at least 1 qualification."], 400);
            }
        }

        if ($this->model->getByEmail($validated["email"])) {
            Response::json(["message" => "Unable to register account."], 409);
        }

        $uploads = new Uploads([
            new Upload("Logo", Storage::dest("logo"), "logo", ["image/png", "image/jpeg"]),
            new Upload("Company Profile", Storage::dest("profile"), "profile", ["application/pdf"]),
            new Upload("Business Permit", Storage::dest("permit"), "permit", ["application/pdf"]),
            new Upload("SEC", Storage::dest("sec"), "sec", ["application/pdf"]),
            new Upload("DTI / CDA Reg.", Storage::dest("dti_cda"), "dti_cda", ["application/pdf"]),
            new Upload("Registry of Establishment fr. DOLE", Storage::dest("reg_of_est"), "reg_of_est", ["application/pdf"]),
            new Upload("Phil-JobNet Reg.", Storage::dest("reg_philjobnet"), "reg_philjobnet", ["application/pdf"]),
            new Upload("Certification from DOLE Provincial Office", Storage::dest("cert_from_dole"), "cert_from_dole", ["application/pdf"]),
            new Upload("Certification of no Pending Case", Storage::dest("cert_no_case"), "cert_no_case", ["application/pdf"]),
        ]);
        $uploads->stage();
        $errs = $uploads->getErrors();

        if (!empty($errs)) {
            $uploads->rollback();
            Response::json(["message" => $errs[0]], 422);
        }

        $userId = $this->model->createCompany([
            "email" => $validated["email"],
            "password_hash" => $passwordHash,
            "name" => $validated["name"],
            "address" => $validated["address"],
            "industry" => $validated["industry"],
            "req_logo" => $uploads->getFilename(0),
            "req_company_profile" => $uploads->getFilename(1),
            "req_business_permit" => $uploads->getFilename(2),
            "req_sec" => $uploads->getFilename(3),
            "req_dti_cda" => $uploads->getFilename(4),
            "req_reg_of_est" => $uploads->getFilename(5),
            "req_philjobnet_reg" => $uploads->getFilename(6),
            "req_cert_from_dole" => $uploads->getFilename(7),
            "req_cert_no_case" => $uploads->getFilename(8),
            "vacancies" => $vacancies,
        ]);

        if ($userId === null) {
            $uploads->rollback();
            Response::json(["message" => "Unable to register"], 500);
        }

        $uploads->commit();

        Response::json(["message" => "You are now registered! Welcome to E-trace."], 201);
    }

    public function searchCompanies()
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD, Role::PSTAFF], Action::READ_COMPANIES);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::BOOLEAN, "enabled", Request::fromQuery("enabled", true)],
            [Validator::ENUM, "ver_status", Request::fromQuery("ver_status"), ['Verified', 'Pending', 'Rejected']],
            [Validator::ENUM, "industry", Request::fromQuery("industry"), [
                'Technology / IT','Finance / Banking / Insurance','Healthcare / Pharmaceuticals',
                'Education / Research','Manufacturing / Industrial','Retail / E-commerce',
                'Food & Beverage / Hospitality','Transportation / Logistics','Energy / Utilities',
                'Media / Entertainment / Advertising','Government / Public Sector',
                'Real Estate / Construction','Consulting / Professional Services','Nonprofit / NGO',
                'Telecommunications'
            ]],
        ]);
        $paginator = new Paginator($this->pdo, "users", $validated["page"], $validated["per_page"]);
        $verStatusWhere = $cUser["role"] === Role::SYSAD
            ? "AND p.ver_stat_sysad = ?"
            : "
                AND p.ver_stat_pstaff = ?
                AND p.ver_stat_sysad = 'Verified'
            ";
        $result = $paginator->run(
            "SELECT
                u.id AS uid,
                u.email AS uemail,
                u.role AS urole,
                u.enabled AS uenabled,
                u.created_at AS ucreated_at,
                u.updated_at AS uupdated_at,
                p.id AS pid,
                p.user_id AS puser_id,
                p.name AS pname,
                p.address AS paddress,
                p.industry AS pindustry,
                p.req_logo AS preq_logo,
                p.req_company_profile AS preq_company_profile,
                p.req_business_permit AS preq_business_permit,
                p.req_sec AS preq_sec,
                p.req_dti_cda AS preq_dti_cda,
                p.req_reg_of_est AS preq_reg_of_est,
                p.req_cert_from_dole AS preq_cert_from_dole,
                p.req_cert_no_case AS preq_cert_no_case,
                p.req_philjobnet_reg AS preq_philjobnet_reg,
                p.stat_req_logo AS pstat_req_logo,
                p.stat_req_company_profile AS pstat_req_company_profile,
                p.stat_req_business_permit AS pstat_req_business_permit,
                p.stat_req_sec AS pstat_req_sec,
                p.stat_req_dti_cda AS pstat_req_dti_cda,
                p.stat_req_reg_of_est AS pstat_req_reg_of_est,
                p.stat_req_cert_from_dole AS pstat_req_cert_from_dole,
                p.stat_req_cert_no_case AS pstat_req_cert_no_case,
                p.stat_req_philjobnet_reg AS pstat_req_philjobnet_reg,
                p.stat_req_list_of_vacancies AS pstat_req_list_of_vacancies,
                p.ver_stat_sysad AS pver_stat_sysad,
                p.ver_stat_pstaff AS pver_stat_pstaff,
                v.id AS vid,
                v.job_title AS vjob_title,
                v.slots AS vslots,
                v.qualifications AS vqualifications
            FROM users u
            JOIN companies p ON p.user_id = u.id
            LEFT JOIN vacancies v ON v.company_id = p.id
            ",
            "WHERE
                u.role = 'company'
                AND u.enabled = ?
                {$verStatusWhere}
                AND p.industry = ?
                AND (
                    u.email LIKE ? OR
                    p.name LIKE ? OR
                    p.address LIKE ? OR
                    p.industry LIKE ?
                )
            ",
            [
                $validated["enabled"],
                $validated["ver_status"],
                $validated["industry"],
                "%{$q}%",
                "%{$q}%",
                "%{$q}%",
                "%{$q}%"
            ],
            [User::class, "format"]
        );
        Response::json($result);
    }

    public function enableCompany($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_COMPANIES);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "Company does not exist."], 404);
        }

        $user["enabled"] = true;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to enable company."], 500);
        }    
            
        $this->mailingSvc->sendEnableMail($cUser, $user);
        Response::json([
            "message" => "Company has been enabled.",
            "id" => $validatedId,
        ]);
    }

    public function disableCompany($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::SYSAD], Action::ENDIS_COMPANIES);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "Company does not exist."], 404);
        }

        $user["enabled"] = false;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to disable company."], 500);
        }    
            
        $this->mailingSvc->sendDisableMail($cUser, $user);
        Response::json([
            "message" => "Company has been disabled.",
            "id" => $validatedId,
        ]);
    }
    
    public function me()
    {
        $cUser = UserGuard::run($this->pdo, Role::all());
        Response::json($cUser);
    }

    public function changePassword()
    {
        $cUser = UserGuard::run($this->pdo, Role::all());

        if ($cUser["email"] === $_ENV["SYSAD_EMAIL"]) {
            Response::json(["message" => "User info should not be modified."], 403);
        }
        
        $validated = Validator::batchValidate([
            [Validator::TEXT, "current password", Request::fromBody("current_password"), "8-65"],
            [Validator::TEXT, "new password", Request::fromBody("new_password"), "8-65"],
        ]);
        
        $fullCUserr = $this->model->getById($cUser["id"]);
        
        if (!Password::correct($validated["current password"], $fullCUserr["password_hash"])) {
            Response::json(["error" => "Password incorrect."], 401);
        }

        $fullCUserr["password_hash"] = Password::hash($validated["new password"]);
        $tookEffect = $this->model->updateById($fullCUserr["id"], $fullCUserr);

        if (!$tookEffect) {
            Response::json(["message" => "Unable to change password."], 500);
        }

        Response::json([
            "message" => "Password has been updated.",
            "id" => $fullCUserr["id"],
        ]);
    }

    public function createAlumni()
    {
        $validated = Validator::batchValidate([
            [Validator::TEXT, "last name", $_POST["last_name"] ?? "", "1-50"],
            [Validator::TEXT, "middle name", $_POST["middle_name"] ?? "", "0-50"],
            [Validator::TEXT, "first name", $_POST["first_name"] ?? "", "1-50"],
            [Validator::TEXT, "name extension", $_POST["name_extension"] ?? "", "0-10"],
            [Validator::TEXT, "birth place", $_POST["birth_place"] ?? "", "1-512"],
            [Validator::ENUM, "gender", $_POST["gender"] ?? "", ["Male", "Female"]],
            [Validator::ENUM, "civil status", $_POST["civil_status"] ?? "", ["Single","Married","Widowed","Separated"]],
            [Validator::TEXT, "phone number", $_POST["phone_number"] ?? "", "1-25"],
            [Validator::TEXT, "address", $_POST["address"] ?? "", "1-512"],
            [Validator::INTEGER, "course id", $_POST["course_id"] ?? 0],
            [Validator::TEXT, "student number", $_POST["student_number"] ?? "", "0-255"],
            [Validator::INTEGER, "graduation year", $_POST["graduation_year"] ?? (int) date("Y")],
            [Validator::ENUM, "employment status", $_POST["employment_status"] ?? "", ["Unemployed","Employed","Self-employed"]],
            [Validator::TEXT, "password", $_POST["password"] ?? "", "8-65"],
            [Validator::EMAIL, "email", $_POST["email"] ?? "", "1-255"],
        ]);
        
        // validate birth date
        $birthDate = $_POST["birth_date"] ?? "";

        if (strlen($birthDate) === 0) {
            Response::json(["message" => "Birth date is required."], 400);
        }

        $birthDate = new DateTime($birthDate);
        $minDate = new DateTime();
        $minDate->modify("-21 years");

        if ($birthDate > $minDate) {
            Response::json("Alumni must be at least 21 years old.");
        }

        $graduationYear = $validated["graduation year"];

        if ($graduationYear > date("Y")) {
            Response::json("Graduation year is in the future.", 400);
        }

        if ($graduationYear < 2007) {
            Response::json("Graduation year must be 2007 or later.", 400);
        }
        
        // extract occupations
        $initialOccupations = json_decode($_POST["occupations"], true);
        $occupations = [];

        foreach($initialOccupations as $occu) {
            $occupation = Validator::validateText("occupation", $occu["occupation"], "1-255");
            $address = Validator::validateText("address", trim($occu["address"] ?? ""), "1-512");
            $isCurrent = Validator::validateBoolean("is current", $occu["is_current"]);
            $occupations[] = ["occupation" => $occupation, "address" => $address, "is_current" => $isCurrent];
        }
        
        // extract socials
        $initialSocials = json_decode($_POST["socials"], true);
        $socials = [];
        
        foreach($initialSocials as $social) {
            $platform = Validator::validateText("platform", trim($social["platform"] ?? ""), "1-50");
            $url = Validator::validateText("url", trim($social["url"] ?? ""), "1-0");
            $socials[] = ["platform" => $platform, "url" => $url];
        }

        // stage files
        $uploads = new Uploads([
            new Upload("Profile Picture", Storage::dest("profile_picture"), "file_profile_picture", ["image/png", "image/jpeg"]),
            new Upload("Curriculum Vitae", Storage::dest("cv"), "file_cv", ["application/pdf"]),
        ]);
        $uploads->stage();
        $errs = $uploads->getErrors();

        if (!empty($errs)) {
            $uploads->rollback();
            Response::json(["message" => $errs[0]], 422);
        }

        // create user
        $passwordHash = Password::hash($validated["password"]);
        
        $userId = $this->model->createAlumni([
            "email" => $validated["email"],
            "password_hash" => $passwordHash,
            "last_name" => $validated["last name"],
            "middle_name" => $validated["middle name"],
            "first_name" => $validated["first name"],
            "name_extension" => $validated["name extension"],
            "birth_date" => $birthDate,
            "birth_place" => $validated["birth place"],
            "gender" => $validated["gender"],
            "civil_status" => $validated["civil status"],
            "phone_number" => $validated["phone number"],
            "address" => $validated["address"],
            "course_id" => $validated["course id"],
            "student_number" => $validated["student number"],
            "graduation_year" => $graduationYear,
            "employment_status" => $validated["employment status"],
            "socials" => $socials,
            "occupations" => $occupations,
            "file_profile_picture" => $uploads->getFilename(0),
            "file_cv" => $uploads->getFilename(1),
        ]);

        if ($userId === null) {
            $uploads->rollback();
            Response::json(["message" => "Unable to register"], 500);
        }

        $uploads->commit();

        Response::json(["message" => "You are now registered! Welcome to E-trace."], 201);
    }

    public function searchAlumni()
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::READ_ALUMNI);
        $q = Request::fromQuery("q", "");
        $validated = Validator::batchValidate([
            [Validator::INTEGER, "page", Request::fromQuery("page", 1)],
            [Validator::INTEGER, "per_page", Request::fromQuery("per_page", 20)],
            [Validator::INTEGER, "course_id", Request::fromQuery("course_id", 0)],
            [Validator::BOOLEAN, "enabled", Request::fromQuery("enabled", true)],
            [Validator::ENUM, "ver_status", Request::fromQuery("ver_status"), ['Verified', 'Pending', 'Rejected']],
        ]);
        $paginator = new Paginator($this->pdo, "users", $validated["page"], $validated["per_page"]);
        $result = $paginator->run(
            "SELECT
                u.id AS uid,
                u.email AS uemail,
                u.role AS urole,
                u.enabled AS uenabled,
                u.created_at AS ucreated_at,
                u.updated_at AS uupdated_at,
                p.id AS pid,
                p.user_id AS puser_id,
                p.name_extension AS pname_extension,
                p.first_name AS pfirst_name,
                p.middle_name AS pmiddle_name,
                p.last_name AS plast_name,
                p.birth_date AS pbirth_date,
                p.birth_place AS pbirth_place,
                p.gender AS pgender,
                p.student_number AS pstudent_number,
                p.graduation_year AS pgraduation_year,
                p.phone_number AS pphone_number,
                p.course_id AS pcourse_id,
                p.civil_status AS pcivil_status,
                p.address AS paddress,
                p.employment_status AS pemployment_status,
                p.file_profile_picture AS pfile_profile_picture,
                p.file_cv AS pfile_cv,
                p.ver_stat_dean AS pver_stat_dean,
                p.created_at AS pcreated_at,
                p.updated_at AS pupdated_at,
                c.id AS cid,
                c.school_id AS cschool_id,
                c.name AS cname,
                c.code AS ccode,
                os.id AS osid,
                os.alumni_id AS osalumni_id,
                os.occupation_id AS osoccupation_id,
                os.address AS osaddress,
                os.is_current AS osis_current,
                o.occupation AS ooccupation,
                s.id AS sid,
                s.alumni_id AS salumni_id,
                s.platform_id AS splatform_id,
                s.url AS surl,
                pl.name AS plname
            FROM users u
            JOIN alumni p ON p.user_id = u.id
            JOIN courses c ON c.id = p.course_id
            LEFT JOIN occupation_statuses os ON os.alumni_id = p.id
            JOIN occupations o ON o.id = os.occupation_id
            LEFT JOIN socials s ON s.alumni_id = p.id
            JOIN platforms pl ON pl.id = s.platform_id
            ",
            "WHERE
                u.role = 'alumni'
                AND u.enabled = ?
                AND p.ver_stat_dean = ?
                AND p.course_id = ?
                AND (
                    u.email LIKE ? OR
                    p.name_extension LIKE ? OR
                    p.first_name LIKE ? OR
                    p.middle_name LIKE ? OR
                    p.last_name LIKE ? OR
                    p.birth_date LIKE ? OR
                    p.birth_place LIKE ? OR
                    p.gender LIKE ? OR
                    p.student_number LIKE ? OR
                    p.graduation_year LIKE ? OR
                    p.phone_number LIKE ? OR
                    p.civil_status LIKE ? OR
                    p.address LIKE ?
                )
            ",
            [
                $validated["enabled"],
                $validated["ver_status"],
                $validated["course_id"],
                "%{$q}%", "%{$q}%", "%{$q}%",
                "%{$q}%", "%{$q}%", "%{$q}%",
                "%{$q}%", "%{$q}%", "%{$q}%",
                "%{$q}%", "%{$q}%", "%{$q}%",
                "%{$q}%",
            ],
            [User::class, "format"],
            "SELECT COUNT(DISTINCT u.id) FROM users u
            JOIN alumni p ON p.user_id = u.id
            JOIN courses c ON c.id = p.course_id
            LEFT JOIN occupation_statuses os ON os.alumni_id = p.id
            JOIN occupations o ON o.id = os.occupation_id
            LEFT JOIN socials s ON s.alumni_id = p.id
            JOIN platforms pl ON pl.id = s.platform_id"
        );
        Response::json($result);
    }

    public function enableAlumni($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::ENDIS_ALUMNI);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "Alumni does not exist."], 404);
        }

        $user["enabled"] = true;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to enable alumni."], 500);
        }    
        
        $this->mailingSvc->sendEnableMail($cUser, $user);
        Response::json([
            "message" => "Alumni has been enabled.",
            "id" => $validatedId,
        ]);
    }

    public function disableAlumni($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::DEAN], Action::ENDIS_ALUMNI);
        $validatedId = Validator::validateInteger("id", $id);
        $user = $this->model->getById($validatedId);

        if (!$user) {
            Response::json(["message" => "Alumni does not exist."], 404);
        }

        $user["enabled"] = false;
        $tookEffect = $this->model->updateById($validatedId, $user);
        
        if (!$tookEffect) {
            Response::json(["message" => "Unable to disable alumni."], 500);
        }    
        
        $this->mailingSvc->sendDisableMail($cUser, $user);
        Response::json([
            "message" => "Alumni has been disabled.",
            "id" => $validatedId,
        ]);
    }

    public function viewAlumniProfile($id)
    {
        $cUser = UserGuard::run($this->pdo, [Role::COMPANY]);
        $validatedId = Validator::validateInteger("id", $id);
        $alumni = $this->model->getAlumniById($validatedId);
        
        if (!$alumni) {
            Response::json(["message" => "Alumni not found."], 400);
        }

        Response::json($alumni);
    }
}