<?php

require_once __DIR__ . "/app/Controllers/AuthController.php";
require_once __DIR__ . "/app/Controllers/UserController.php";
require_once __DIR__ . "/app/Controllers/ProfileController.php";
require_once __DIR__ . "/app/Controllers/SchoolController.php";
require_once __DIR__ . "/app/Controllers/CourseController.php";
require_once __DIR__ . "/app/Controllers/JobPostController.php";
require_once __DIR__ . "/app/Controllers/PlatformController.php";
require_once __DIR__ . "/app/Controllers/OccupationController.php";
require_once __DIR__ . "/app/Controllers/AnalyticsController.php";
require_once __DIR__ . "/app/Controllers/GraduateRecordController.php";
require_once __DIR__ . "/app/Controllers/JobPostLikeController.php";
require_once __DIR__ . "/app/Controllers/JobPostCvSubmissionController.php";

$router->get("/api/auth/logout", [AuthController::class, "logout"]);
$router->post("/api/auth", [AuthController::class, "login"]);

$router->get("/api/analytics", [AnalyticsController::class, "get"]);
$router->get("/api/analytics/report", [AnalyticsController::class, "getReport"]);

$router->get("/api/occupations", [OccupationController::class, "getAll"]);

$router->get("/api/schools", [SchoolController::class, "getAll"]);
$router->get("/api/schools/active", [SchoolController::class, "getAllActive"]);
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
$router->get("/api/records/from-alumni", [GraduateRecordController::class, "findRecordForAlumni"]);
$router->post("/api/records", [GraduateRecordController::class, "create"]);
$router->patch("/api/records/{id}/archive", [GraduateRecordController::class, "archive"]);
$router->patch("/api/records/{id}/restore", [GraduateRecordController::class, "restore"]);

$router->get("/api/users/me", [UserController::class, "me"]);
$router->post("/api/users/forgot-password", [UserController::class, "sendResetEmail"]);
$router->patch("/api/users/reset-password", [UserController::class, "resetPassword"]);
$router->patch("/api/users/agree-to-consent", [UserController::class, "agreeToConsent"]);
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

$router->get("/api/users/company/{id}/revision-appeals", [ProfileController::class, "getCompanyNotesAndAppeals"]);
$router->get("/api/users/company/{id}/rejection-appeals", [ProfileController::class, "getCompanyRejectionAppeals"]);
$router->post("/api/users/company", [UserController::class, "createCompany"]);
$router->post("/api/users/company/{id}/update-profile", [ProfileController::class, "updateProfile"]);
$router->post("/api/users/company/posts", [JobPostController::class, "post"]);
$router->get("/api/users/company/search", [UserController::class, "searchCompanies"]);
$router->get("/api/users/company/posts/search", [JobPostController::class, "searchAsCompany"]);

// for testing
$router->delete("/api/users/company/posts/{id}/delete", [JobPostController::class, "delete"]);
// for testing

$router->patch("/api/users/company/posts/{id}/close", [JobPostController::class, "close"]);
$router->patch("/api/users/company/posts/{id}/repost", [JobPostController::class, "repost"]);
$router->get("/api/users/company/posts/{id}/cvs", [JobPostCvSubmissionController::class, "getSubmissionsAsCompany"]);
$router->patch("/api/users/company/posts/submissions/{id}/review", [JobPostCvSubmissionController::class, "review"]);

$router->get("/api/users/company/posts/my-cvs", [JobPostCvSubmissionController::class, "getSubmissionsAsAlumni"]);
$router->get("/api/users/company/posts/my-likes", [JobPostLikeController::class, "myLikes"]);
$router->post("/api/users/company/posts/{id}/like", [JobPostLikeController::class, "like"]);
$router->delete("/api/users/company/posts/{id}/dislike", [JobPostLikeController::class, "dislike"]);
$router->post("/api/users/company/posts/{id}/submit-cv", [JobPostCvSubmissionController::class, "submitCv"]);
$router->delete("/api/users/company/posts/{id}/delete-cv", [JobPostCvSubmissionController::class, "deleteSubmission"]);

$router->post("/api/users/company/vacancy", [ProfileController::class, "addVacancy"]);
$router->post("/api/users/company/revision-appeal", [ProfileController::class, "writeCompanyRevisionAppeal"]);
$router->post("/api/users/company/rejection-appeal", [ProfileController::class, "writeCompanyRejectionAppeal"]);
$router->post("/api/users/company/reupload-requirement", [ProfileController::class, "reviseRequirement"]);
$router->patch("/api/users/company/{id}/enable", [UserController::class, "enableCompany"]);
$router->patch("/api/users/company/{id}/disable", [UserController::class, "disableCompany"]);
$router->patch("/api/users/company/{id}/pend", [ProfileController::class, "pendCompany"]);
$router->patch("/api/users/company/{id}/verify", [ProfileController::class, "verifyCompany"]);
$router->patch("/api/users/company/{id}/reject", [ProfileController::class, "rejectCompany"]);
$router->patch("/api/users/company/approve-requirement", [ProfileController::class, "approveRequirement"]);
$router->patch("/api/users/company/revise-requirement", [ProfileController::class, "forReviseRequirement"]);
$router->patch("/api/users/company/vacancy/{id}/edit", [ProfileController::class, "editVacancy"]);
$router->patch("/api/users/company/vacancy/{id}/edit-qualification", [ProfileController::class, "editVacancyQualifications"]);
$router->delete("/api/users/company/vacancy/{id}/delete", [ProfileController::class, "deleteVacancy"]);

$router->get("/api/users/alumni/search", [UserController::class, "searchAlumni"]);
$router->get("/api/users/alumni/{id}/profile", [UserController::class, "viewAlumniProfile"]);
$router->get("/api/users/alumni/posts/search", [JobPostController::class, "searchAsAlumni"]);
$router->get("/api/users/alumni/{id}/rejection-appeals", [ProfileController::class, "getAlumniRejectionAppeals"]);
$router->patch("/api/users/alumni/{id}/enable", [UserController::class, "enableAlumni"]);
$router->patch("/api/users/alumni/{id}/disable", [UserController::class, "disableAlumni"]);
$router->patch("/api/users/alumni/{id}/pend", [ProfileController::class, "pendAlumni"]);
$router->patch("/api/users/alumni/{id}/verify", [ProfileController::class, "verifyAlumni"]);
$router->patch("/api/users/alumni/{id}/reject", [ProfileController::class, "rejectAlumni"]);
$router->post("/api/users/alumni", [UserController::class, "createAlumni"]);
$router->post("/api/users/alumni/social", [ProfileController::class, "createAlumniSocial"]);
$router->post("/api/users/alumni/rejection-appeal", [ProfileController::class, "writeAlumniRejectionAppeal"]);
$router->patch("/api/users/alumni/update-career", [ProfileController::class, "updateAlumniCareerInfo"]);
$router->patch("/api/users/alumni/update-contact", [ProfileController::class, "updateAlumniContactInfo"]);
$router->patch("/api/users/alumni/update-personal", [ProfileController::class, "updateAlumniPersonalInfo"]);
$router->delete("/api/users/alumni/{id}/social", [ProfileController::class, "deleteAlumniSocialById"]);

$router->patch("/api/users/profiles/sysad", [ProfileController::class, "updateSysad"]);
$router->patch("/api/users/profiles/dean", [ProfileController::class, "updateDean"]);
$router->patch("/api/users/profiles/pstaff", [ProfileController::class, "updatePstaff"]);

$router->get("/api/platforms", [PlatformController::class, "all"]);