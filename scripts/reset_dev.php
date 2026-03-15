<?php

require_once __DIR__ . "/../app/Core/Database.php";
require_once __DIR__ . "/../utils/Storage.php";

function runScript()
{
    $config = Database::loadConfig();
    $db = new Database($config);
    $pdo = $db->connect();

    $tables = [
        "alumni",
        "alumni_rejection_appeals",
        "alumni_rejection_messages",
        "vacancies",
        "companies",
        "company_revision_messages",
        "company_revision_appeals",
        "company_rejection_messages",
        "company_rejection_appeals",
        "courses",
        "course_aligned_occupations",
        "graduate_records",
        "deans",
        "job_posts",
        "job_post_courses",
        "job_post_cv_submissions",
        "job_post_likes",
        "occupations",
        "occupation_statuses",
        "platforms",
        "pstaffs",
        "schools",
        "socials",
        "sysads",
        "users",
    ];

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        Logger::info("Table (" . $table . ") has been dropped.");
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    Storage::reset();
}