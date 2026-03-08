<?php

require_once __DIR__ . "/app/Controllers/AuthController.php";
require_once __DIR__ . "/app/Controllers/UserController.php";
require_once __DIR__ . "/app/Controllers/ProfileController.php";
require_once __DIR__ . "/app/Controllers/SchoolController.php";
require_once __DIR__ . "/app/Controllers/CourseController.php";
require_once __DIR__ . "/app/Controllers/JobPostController.php";
require_once __DIR__ . "/app/Controllers/PlatformController.php";
require_once __DIR__ . "/app/Controllers/GraduateRecordController.php";

$router->get("/api/auth/logout", [AuthController::class, "logout"]);
$router->post("/api/auth", [AuthController::class, "login"]);

$router->get("/api/schools", [SchoolController::class, "getAll"]);
$router->get("/api/schools/search", [SchoolController::class, "search"]);
$router->post("/api/schools", [SchoolController::class, "create"]);
$router->patch("/api/schools/{id}/rename", [SchoolController::class, "rename"]);
$router->patch("/api/schools/{id}/archive", [SchoolController::class, "archive"]);
$router->patch("/api/schools/{id}/restore", [SchoolController::class, "restore"]);

$router->get("/api/courses", [CourseController::class, "getAll"]);
$router->get("/api/courses/active", [CourseController::class, "getAllActive"]);
$router->get("/api/courses/search", [CourseController::class, "search"]);
$router->get("/api/courses/search-occupations", [CourseController::class, "searchOccupations"]);
$router->get("/api/courses/from-school", [CourseController::class, "getAllUnderDeanSchool"]);
$router->post("/api/courses", [CourseController::class, "create"]);
$router->patch("/api/courses/{id}/edit", [CourseController::class, "edit"]);
$router->patch("/api/courses/{id}/archive", [CourseController::class, "archive"]);
$router->patch("/api/courses/{id}/restore", [CourseController::class, "restore"]);
$router->patch("/api/courses/align-occupation", [CourseController::class, "alignOccupation"]);
$router->delete("/api/courses/unalign-occupation", [CourseController::class, "unalignOccupation"]);

$router->get("/api/records/search", [GraduateRecordController::class, "search"]);
$router->get("/api/records/{id}/open", [GraduateRecordController::class, "open"]);
$router->post("/api/records", [GraduateRecordController::class, "create"]);
$router->patch("/api/records/{id}/archive", [GraduateRecordController::class, "archive"]);
$router->patch("/api/records/{id}/restore", [GraduateRecordController::class, "restore"]);

$router->get("/api/users/me", [UserController::class, "me"]);
$router->patch("/api/users/change-password", [UserController::class, "changePassword"]);

$router->get("/api/users/sysad/search", [UserController::class, "searchSysads"]);
$router->post("/api/users/sysad", [UserController::class, "createSysad"]);
$router->patch("/api/users/sysad/{id}/enable", [UserController::class, "enableSysad"]);
$router->patch("/api/users/sysad/{id}/disable", [UserController::class, "disableSysad"]);

$router->get("/api/users/dean/search", [UserController::class, "searchDeans"]);
$router->post("/api/users/dean", [UserController::class, "createDean"]);
$router->patch("/api/users/dean/{id}/enable", [UserController::class, "enableDean"]);
$router->patch("/api/users/dean/{id}/disable", [UserController::class, "disableDean"]);

$router->get("/api/users/pstaff/search", [UserController::class, "searchPstaffs"]);
$router->post("/api/users/pstaff", [UserController::class, "createPstaff"]);
$router->patch("/api/users/pstaff/{id}/enable", [UserController::class, "enablePstaff"]);
$router->patch("/api/users/pstaff/{id}/disable", [UserController::class, "disablePstaff"]);

$router->get("/api/users/company/posts/search", [JobPostController::class, "searchAsCompany"]);
$router->get("/api/users/company/search", [UserController::class, "searchCompanies"]);
$router->get("/api/users/company/{id}/revision-appeals", [ProfileController::class, "getCompanyNotesAndAppeals"]);
$router->get("/api/users/company/{id}/rejection-appeals", [ProfileController::class, "getCompanyRejectionAppeals"]);
$router->post("/api/users/company", [UserController::class, "createCompany"]);
$router->post("/api/users/company/posts", [JobPostController::class, "post"]);
$router->post("/api/users/company/vacancy", [ProfileController::class, "addVacancy"]);
$router->post("/api/users/company/revision-appeal", [ProfileController::class, "writeRevisionAppeal"]);
$router->post("/api/users/company/rejection-appeal", [ProfileController::class, "writeRejectionAppeal"]);
$router->post("/api/users/company/reupload-requirement", [ProfileController::class, "reviseRequirement"]);
$router->patch("/api/users/company/{id}/enable", [UserController::class, "enableCompany"]);
$router->patch("/api/users/company/{id}/disable", [UserController::class, "disableCompany"]);
$router->patch("/api/users/company/{id}/pend", [ProfileController::class, "pendCompany"]);
$router->patch("/api/users/company/{id}/verify", [ProfileController::class, "verifyCompany"]);
$router->patch("/api/users/company/{id}/reject", [ProfileController::class, "rejectCompany"]);
$router->patch("/api/users/company/approve-requirement", [ProfileController::class, "approveRequrement"]);
$router->patch("/api/users/company/revise-requirement", [ProfileController::class, "forReviseRequrement"]);
$router->patch("/api/users/company/vacancy/{id}/edit", [ProfileController::class, "editVacancy"]);
$router->patch("/api/users/company/vacancy/{id}/edit-qualification", [ProfileController::class, "editVacancyQualifications"]);

$router->patch("/api/users/profiles/sysad", [ProfileController::class, "updateSysad"]);

$router->patch("/api/users/profiles/dean", [ProfileController::class, "updateDean"]);

$router->patch("/api/users/profiles/pstaff", [ProfileController::class, "updatePstaff"]);

$router->get("/api/platforms", [PlatformController::class, "all"]);