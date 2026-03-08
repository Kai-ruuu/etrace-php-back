<?php

require_once __DIR__ . "/../app/Core/Logger.php";
require_once __DIR__ . "/../app/Core/Database.php";
require_once __DIR__ . "/../utils/Storage.php";
require_once __DIR__ . "/../utils/Password.php";

function createTables($pdo)
{
    $tables = [];
    
    $tables[] = "CREATE TABLE IF NOT EXISTS schools(
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL,
        archived BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS courses(
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT NOT NULL,
        name VARCHAR(65) NOT NULL,
        code VARCHAR(10) NOT NULL,
        archived BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
        UNIQUE(school_id, name),
        UNIQUE(school_id, code)
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS users(
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        enabled BOOLEAN NOT NULL DEFAULT TRUE,
        role ENUM('sysad','dean','pstaff','company','alumni') NOT NULL DEFAULT 'alumni',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS sysads(
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50) DEFAULT NULL,
        last_name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS deans(
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50) DEFAULT NULL,
        last_name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS pstaffs(
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50) DEFAULT NULL,
        last_name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS companies(
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        address VARCHAR(512) NOT NULL,
        industry ENUM(
            'Technology / IT','Finance / Banking / Insurance','Healthcare / Pharmaceuticals',
            'Education / Research','Manufacturing / Industrial','Retail / E-commerce',
            'Food & Beverage / Hospitality','Transportation / Logistics','Energy / Utilities',
            'Media / Entertainment / Advertising','Government / Public Sector',
            'Real Estate / Construction','Consulting / Professional Services','Nonprofit / NGO',
            'Telecommunications'
        ) NOT NULL DEFAULT 'Technology / IT',
        req_logo VARCHAR(255) NOT NULL,
        req_company_profile VARCHAR(255) NOT NULL,
        req_business_permit VARCHAR(255) NOT NULL,
        req_sec VARCHAR(255) NOT NULL,
        req_dti_cda VARCHAR(255) NOT NULL,
        req_reg_of_est VARCHAR(255) NOT NULL,
        req_cert_from_dole VARCHAR(255) NOT NULL,
        req_cert_no_case VARCHAR(255) NOT NULL,
        req_philjobnet_reg VARCHAR(255) NOT NULL,
        stat_req_logo ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_company_profile ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_business_permit ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_sec ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_dti_cda ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_reg_of_est ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_cert_from_dole ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_cert_no_case ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_philjobnet_reg ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        stat_req_list_of_vacancies ENUM('Pending', 'Approved','For Revision') DEFAULT 'Pending',
        ver_stat_sysad ENUM('Verified','Pending','Rejected') DEFAULT 'Pending',
        ver_stat_pstaff ENUM('Verified','Pending','Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS alumni(
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name_extension VARCHAR(10) DEFAULT NULL,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50) DEFAULT NULL,
        last_name VARCHAR(50) NOT NULL,
        birth_date DATE NOT NULL,
        birth_place VARCHAR(512) DEFAULT NULL,
        gender ENUM('Male', 'Female') DEFAULT 'Male',
        student_number VARCHAR(255) DEFAULT NULL,
        phone_number VARCHAR(25) NOT NULL,
        course_id INT NOT NULL,
        civil_status ENUM('Single','Married','Widowed','Separated') DEFAULT 'Single',
        address VARCHAR(512) NOT NULL,
        employment_status ENUM('Unemployed','Employed','Self-employed') DEFAULT 'Unemployed',
        ver_stat_dean ENUM('Verified','Pending','Reviewed','Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        file_profile_picture VARCHAR(255) NOT NULL,
        file_cv VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS vacancies(
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        job_title VARCHAR(255) NOT NULL,
        slots INT NOT NULL,
        qualifications JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        UNIQUE(company_id, job_title)
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS platforms(
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(25) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS socials(
        id INT AUTO_INCREMENT PRIMARY KEY,
        alumni_id INT NOT NULL,
        platform_id INT NOT NULL,
        url VARCHAR(512) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
        FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS occupations(
        id INT AUTO_INCREMENT PRIMARY KEY,
        occupation VARCHAR(255) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS occupation_statuses(
        id INT AUTO_INCREMENT PRIMARY KEY,
        alumni_id INT NOT NULL,
        occupation_id INT NOT NULL,
        address VARCHAR(512) NOT NULL,
        is_current BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
        FOREIGN KEY (occupation_id) REFERENCES occupations(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS course_aligned_occupations(
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        occupation_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (occupation_id) REFERENCES occupations(id) ON DELETE CASCADE,
        UNIQUE(course_id, occupation_id)
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS graduate_records(
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        archived BOOLEAN NOT NULL DEFAULT FALSE,
        dean_uploader_id INT NOT NULL,
        filename VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (dean_uploader_id) REFERENCES deans(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS company_revision_messages(
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        pstaff_id INT NOT NULL,
        message TEXT NOT NULL,
        requirement_name VARCHAR(65) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (pstaff_id) REFERENCES pstaffs(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS company_revision_appeals(
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        resubmit_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (resubmit_id) REFERENCES company_revision_messages(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS company_rejection_messages(
        id INT AUTO_INCREMENT PRIMARY KEY,
        sysad_id INT,
        pstaff_id INT,
        company_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sysad_id) REFERENCES sysads(id) ON DELETE CASCADE,
        FOREIGN KEY (pstaff_id) REFERENCES pstaffs(id) ON DELETE CASCADE,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        CHECK (
            (sysad_id IS NOT NULL AND pstaff_id IS NULL)
            OR (pstaff_id IS NOT NULL AND sysad_id IS NULL)
        )
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS company_rejection_appeals(
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        rejection_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (rejection_id) REFERENCES company_rejection_messages(id) ON DELETE CASCADE,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS alumni_review_messages(
        id INT AUTO_INCREMENT PRIMARY KEY,
        alumni_id INT NOT NULL,
        dean_id INT NOT NULL,
        result ENUM(
            'Verified',
            'Reviewed',
            'Rejected'
        ) DEFAULT 'Reviewed',
        message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
        FOREIGN KEY (dean_id) REFERENCES deans(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS alumni_appeals(
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_message_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_message_id) REFERENCES alumni_review_messages(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS job_posts(
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        position VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        qualifications JSON NOT NULL,
        address VARCHAR(512) NOT NULL,
        salary_min INT NOT NULL,
        salary_max INT NOT NULL,
        work_shift ENUM(
            'Day',
            'Evening / Swing',
            'Night / Graveyard',
            'Morning'
        ) DEFAULT 'Day',
        work_setup ENUM(
            'On-site',
            'Remote',
            'Hybrid'
        ) DEFAULT 'On-site',
        employment_type ENUM(
            'Full-time',
            'Part-time',
            'Contract',
            'Internship',
            'Freelance'
        ) DEFAULT 'Full-time',
        slots INT NOT NULL,
        additional_info TEXT DEFAULT NULL,
        open_until DATE NOT NULL,
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        CONSTRAINT salary_check CHECK(salary_min <= salary_max)
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS job_post_courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_post_id INT NOT NULL,
        course_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS job_post_likes(
        id INT AUTO_INCREMENT PRIMARY KEY,
        alumni_id INT NOT NULL,
        job_post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
        FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE CASCADE,
        UNIQUE(alumni_id, job_post_id)
    );
    ";

    $tables[] = "CREATE TABLE IF NOT EXISTS job_post_cv_submissions(
        id INT AUTO_INCREMENT PRIMARY KEY,
        alumni_id INT NOT NULL,
        job_post_id INT NOT NULL,
        status ENUM(
            'Pending',
            'Reviewed'
        ) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
        FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE CASCADE
    );
    ";

    foreach ($tables as $sql) {
        $statement = $pdo->prepare($sql);
        $statement->execute();
    }

    Logger::info("All tables created successfully.");
}

function seedDefaultSysad($pdo)
{
    $sysadFirstName = $_ENV["SYSAD_FIRST_NAME"] ?? "System";
    $sysadLastName  = $_ENV["SYSAD_LAST_NAME"] ?? "Administrator";
    $sysadEmail     = $_ENV["SYSAD_EMAIL"] ?? "sysad@email.com";
    $sysadPassword  = $_ENV["SYSAD_PASSWORD"] ?? "sysadpass";

    $statement = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $statement->execute([$sysadEmail]);
    $existingSysad = $statement->fetch();
    
    if ($existingSysad) {
        Logger::info("Default system administrator already exists.");
        return;
    }

    $passwordHash = Password::hash($sysadPassword);
    
    try {
        $pdo->beginTransaction();        
        $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
            ->execute([$sysadEmail, $passwordHash, 'sysad']);
        $pdo->prepare("INSERT INTO sysads (user_id, first_name, last_name) VALUES (?, ?, ?)")
            ->execute([$pdo->lastInsertId(), $sysadFirstName, $sysadLastName]);
        $pdo->commit();
        Logger::info("Default system administrator has been created.");
    } catch (PDOException $e) {
        $pdo->rollback();
        Logger::error(Logger::ERR_DATABASE, "Unable to create the default system administrator: " . $e->getMessage());
    }
}

function seedDefaultRegularSysad($pdo, $total)
{
    for ($i = 0; $i < $total; $i++) {
        $sysadNumber = $i + 1;
        $sysadFirstName = "System{$sysadNumber}";
        $sysadLastName  = "Administrator";
        $sysadEmail     = "sysad{$sysadNumber}@email.com";
        $sysadPassword  = "sysad{$sysadNumber}pass";
    
        $statement = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $statement->execute([$sysadEmail]);
        $existingSysad = $statement->fetch();
        
        if ($existingSysad) {
            Logger::info("Default regular system administrator already exists.");
            return;
        }
    
        $passwordHash = Password::hash($sysadPassword);
        
        try {
            $pdo->beginTransaction();        
            $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
                ->execute([$sysadEmail, $passwordHash, 'sysad']);
            $pdo->prepare("INSERT INTO sysads (user_id, first_name, last_name) VALUES (?, ?, ?)")
                ->execute([$pdo->lastInsertId(), $sysadFirstName, $sysadLastName]);
            $pdo->commit();
            Logger::info("Default regular system administrator (" . $sysadEmail . ") has been created.");
        } catch (PDOException $e) {
            $pdo->rollback();
            Logger::error(Logger::ERR_DATABASE, "Unable to create the default regular system administrator (" . $sysadEmail . "): " . $e->getMessage());
        }
    }
}

function seedDefaultDeans($pdo, $sId, $sName, $total)
{
    for ($i = 0; $i < $total; $i++) {
        $deanNumber = $i + 1;
        $deanFirstName = "Dean";
        $deanLastName  = $sName;
        $deanEmail     = "dean{$sId}{$deanNumber}@email.com";
        $deanPassword  = "dean{$sId}{$deanNumber}pass";
    
        $statement = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $statement->execute([$deanEmail]);
        $existingDeans = $statement->fetch();
        
        if ($existingDeans) {
            continue;
        }
    
        $passwordHash = Password::hash($deanPassword);
        
        try {
            $pdo->beginTransaction();        
            $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
                ->execute([$deanEmail, $passwordHash, 'dean']);
            $pdo->prepare("INSERT INTO deans (user_id, school_id, first_name, last_name) VALUES (?, ?, ?, ?)")
                ->execute([$pdo->lastInsertId(), $sId, $deanFirstName, $deanLastName]);
            $pdo->commit();
            Logger::info("Default dean (" . $deanEmail . ") has been created.");
        } catch (PDOException $e) {
            $pdo->rollback();
            error_log($e->getMessage());
        }
    }
}

function seedDefaultSchoolsAndDeans($pdo, $schools = [])
{
    $total = count($schools);
    $created = 0;
    $existing = 0;
    
    foreach ($schools as $schoolInfo => [$name, $deanCount]) {
        $statement = $pdo->prepare("SELECT * FROM schools WHERE name = ?");
        $statement->execute([$name]);
        $existingSchool = $statement->fetch();

        if ($existingSchool) {
            $existing++;
            continue;
        }

        $statement = $pdo->prepare("INSERT INTO schools (name) VALUES (?)");
        $statement->execute([$name]);
        $schoolId = $pdo->lastInsertId();
        $created++;

        Logger::info("Default school (" . $name . ") has been created.");
        
        seedDefaultDeans($pdo, $schoolId, $name, $deanCount);
    }

    if ($existing === $total) {
        Logger::info("All the default schools are already existing.");
    }
}

function seedDefaultPstaff($pdo, $total)
{
    $existing = 0;
    
    for ($i = 0; $i < $total; $i++) {
        $pstaffNumber = $i + 1;
        $pstaffFirstName = "PESO";
        $pstaffLastName  = "Staff";
        $pstaffEmail     = "peso{$pstaffNumber}@email.com";
        $pstaffPassword  = "peso{$pstaffNumber}pass";
    
        $statement = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $statement->execute([$pstaffEmail]);
        $existingSysad = $statement->fetch();
        
        if ($existingSysad) {
            $existing++;
            continue;
        }
    
        $passwordHash = Password::hash($pstaffPassword);
        
        try {
            $pdo->beginTransaction();        
            $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
                ->execute([$pstaffEmail, $passwordHash, 'pstaff']);
            $pdo->prepare("INSERT INTO pstaffs (user_id, first_name, last_name) VALUES (?, ?, ?)")
                ->execute([$pdo->lastInsertId(), $pstaffFirstName, $pstaffLastName]);
            $pdo->commit();
            Logger::info("Default peso staff (" . $pstaffEmail . ") has been created.");
        } catch (PDOException $e) {
            $pdo->rollback();
            Logger::error(Logger::ERR_DATABASE, "Unable to create the default peso staff (" . $pstaffEmail . "): " . $e->getMessage());
        }
    }

    if ($existing === $total) {
        Logger::info("All the default peso staffs are already existing.");
    }
}

function seedDefaultOccupations($pdo, $total)
{
    $existing = 0;
    
    for ($i = 0; $i < $total; $i++) {
        $occupation = "Occupation " . ($i + 1);

        $statement = $pdo->prepare("SELECT * FROM occupations WHERE occupation = ?");
        $statement->execute([$occupation]);
        
        if ($statement->fetch()) {
            $existing++;
            continue;
        }
        
        $statement = $pdo->prepare("INSERT INTO occupations (occupation) VALUES (?)");
        $statement->execute([$occupation]);
        Logger::info("Default occupation (" . $occupation . ") has been created.");
    }

    if ($existing === $total) {
        Logger::info("All the default occupations are already existing.");
    }
}

function seed($pdo)
{
    seedDefaultSysad($pdo);
    seedDefaultRegularSysad($pdo, 29);
    seedDefaultSchoolsAndDeans($pdo, [
        ["School of Computer Studies", 30],
        ["School of Education", 30],
    ]);
    seedDefaultPstaff($pdo, 30);
    seedDefaultOccupations($pdo, 30);
}

function runScript()
{
    $config = Database::loadConfig();
    $db = new Database($config);
    $pdo = $db->connect();

    createTables($pdo);
    seed($pdo);
    Storage::set();
}
