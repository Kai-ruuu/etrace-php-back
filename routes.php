<?php

require_once __DIR__ . "/app/Controllers/AuthController.php";
require_once __DIR__ . "/app/Controllers/UserController.php";
require_once __DIR__ . "/app/Controllers/ProfileController.php";
require_once __DIR__ . "/app/Controllers/SchoolController.php";
require_once __DIR__ . "/app/Controllers/CourseController.php";
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

$router->patch("/api/users/profiles/sysad", [ProfileController::class, "updateSysad"]);

$router->patch("/api/users/profiles/dean", [ProfileController::class, "updateDean"]);