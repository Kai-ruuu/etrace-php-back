<?php

require_once __DIR__ . "/../Core/Constants.php";

class User
{   
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getByEmail($email)
    {
        $statement = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $statement->execute([$email]);
        return $statement->fetch();
    }

    public function updateById($id, $user) {
        $statement = $this->pdo->prepare("UPDATE users SET enabled = ?, password_hash = ? WHERE id = ?");
        $statement->execute([$user["enabled"], $user["password_hash"], $id]);
        return $statement->rowCount() > 0;
    }

    public function createSysad($sysad)
    {
        $email = $sysad["email"];
        $passwordHash = $sysad["password_hash"];
        $firstName = $sysad["first_name"];
        $middleName = $sysad["middle_name"];
        $lastName = $sysad["last_name"];

        try {
            $this->pdo->beginTransaction();        
            $this->pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
                ->execute([$email, $passwordHash, 'sysad']);

            $userId = $this->pdo->lastInsertId();

            $this->pdo->prepare("INSERT INTO sysads (user_id, first_name, middle_name, last_name) VALUES (?, ?, ?, ?)")
                ->execute([$this->pdo->lastInsertId(), $firstName, $middleName, $lastName]);
            $this->pdo->commit();
            return $userId;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            return null;
        }
    }

    public function getSysadById($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
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
            WHERE u.id = ? AND u.role = 'sysad'
        ");
        $statement->execute([$id]);
        $user = $statement->fetch();

        if (!$user) {
            return null;
        }

        return self::format($user);
    }

    public function createDean($dean)
    {
        $email = $dean["email"];
        $passwordHash = $dean["password_hash"];
        $firstName = $dean["first_name"];
        $middleName = $dean["middle_name"];
        $lastName = $dean["last_name"];
        $schoolId = $dean["school_id"];
        
        try {
            $this->pdo->beginTransaction();        
            $this->pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
                ->execute([$email, $passwordHash, 'dean']);

            $userId = $this->pdo->lastInsertId();

            $this->pdo->prepare("INSERT INTO deans (user_id, school_id, first_name, middle_name, last_name) VALUES (?, ?, ?, ?, ?)")
                ->execute([$this->pdo->lastInsertId(), $schoolId, $firstName, $middleName, $lastName]);
            $this->pdo->commit();
            return $userId;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            return null;
        }
    }
    
    public function getDeanById($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
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
            WHERE u.id = ? AND u.role = 'dean'
        ");
        $statement->execute([$id]);
        $user = $statement->fetch();

        if (!$user) {
            return null;
        }

        return self::format($user);
    }

    public function createPstaff($pstaff)
    {
        $email = $pstaff["email"];
        $passwordHash = $pstaff["password_hash"];
        $firstName = $pstaff["first_name"];
        $middleName = $pstaff["middle_name"];
        $lastName = $pstaff["last_name"];

        try {
            $this->pdo->beginTransaction();        
            $this->pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
                ->execute([$email, $passwordHash, 'pstaff']);

            $userId = $this->pdo->lastInsertId();

            $this->pdo->prepare("INSERT INTO pstaffs (user_id, first_name, middle_name, last_name) VALUES (?, ?, ?, ?)")
                ->execute([$this->pdo->lastInsertId(), $firstName, $middleName, $lastName]);
            $this->pdo->commit();
            return $userId;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            return null;
        }
    }

    public function getPstaffById($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
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
            WHERE u.id = ? AND u.role = 'pstaff'
        ");
        $statement->execute([$id]);
        $user = $statement->fetch();

        if (!$user) {
            return null;
        }

        return self::format($user);
    }

    public function createCompany($company)
    {
        $email = $company["email"];
        $passwordHash = $company["password_hash"];

        $name = $company["name"];
        $address = $company["address"];
        $industry = $company["industry"];
        $req_logo = $company["req_logo"];
        $req_company_profile = $company["req_company_profile"];
        $req_business_permit = $company["req_business_permit"];
        $req_sec = $company["req_sec"];
        $req_dti_cda = $company["req_dti_cda"];
        $req_reg_of_est = $company["req_reg_of_est"];
        $req_cert_from_dole = $company["req_cert_from_dole"];
        $req_cert_no_case = $company["req_cert_no_case"];
        $req_philjobnet_reg = $company["req_philjobnet_reg"];
        $vacancies = $company["vacancies"];

        try {
            $this->pdo->beginTransaction();        

            // create user
            $this->pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
            ->execute([$email, $passwordHash, 'company']);

            $userId = $this->pdo->lastInsertId();
            
            // create profile
            $this->pdo->prepare("INSERT INTO companies (
                user_id,
                name,
                address,
                industry,
                req_logo,
                req_company_profile,
                req_business_permit,
                req_sec,
                req_dti_cda,
                req_reg_of_est,
                req_cert_from_dole,
                req_cert_no_case,
                req_philjobnet_reg
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $userId,
                $name,
                $address,
                $industry,
                $req_logo,
                $req_company_profile,
                $req_business_permit,
                $req_sec,
                $req_dti_cda,
                $req_reg_of_est,
                $req_cert_from_dole,
                $req_cert_no_case,
                $req_philjobnet_reg,
            ]);

            $companyId = $this->pdo->lastInsertId();

            // create vacancies
            foreach ($vacancies as $vacancy) {
                $statement = $this->pdo->prepare(
                    "INSERT INTO vacancies (company_id, job_title, slots, qualifications) VALUES (?, ?, ?, ?)"
                );

                $statement->execute([
                    $companyId,
                    $vacancy["job_title"],
                    $vacancy["slots"],
                    json_encode($vacancy["qualifications"]),
                ]);
            }

            $this->pdo->commit();
            return $userId;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            return null;
        }
    }

    public function getCompanyById($id)
    {
        $statement = $this->pdo->prepare("
            SELECT
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
            WHERE u.id = ? AND u.role = 'company'
        ");
        $statement->execute([$id]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $user = $rows[0];
        $user["vacancies"] = [];

        foreach ($rows as $row) {
            if (!empty($row["vid"])) {
                $user["vacancies"][] = [
                    "id"             => $row["vid"],
                    "job_title"      => $row["vjob_title"],
                    "slots"          => $row["vslots"],
                    "qualifications" => $row["vqualifications"],
                ];
            }
        }

        return self::format($user);
    }
    
    public static function format($user)
    {
        switch ($user["urole"]) {
            case Role::SYSAD:
                return [
                    "id" => $user["uid"],
                    "email" => $user["uemail"],
                    "role" => $user["urole"],
                    "enabled" => $user["uenabled"],
                    "created_at" => $user["ucreated_at"],
                    "updated_at" => $user["uupdated_at"],
                    "default_sysad" => $user["uemail"] === $_ENV["SYSAD_EMAIL"],
                    "profile" => [
                        "id" => $user["pid"],
                        "first_name" => $user["pfirst_name"],
                        "middle_name" => $user["pmiddle_name"],
                        "last_name" => $user["plast_name"],
                    ]
                ];
            case Role::DEAN:
                return [
                    "id" => $user["uid"],
                    "email" => $user["uemail"],
                    "role" => $user["urole"],
                    "enabled" => $user["uenabled"],
                    "created_at" => $user["ucreated_at"],
                    "updated_at" => $user["uupdated_at"],
                    "profile" => [
                        "id" => $user["pid"],
                        "first_name" => $user["pfirst_name"],
                        "middle_name" => $user["pmiddle_name"],
                        "last_name" => $user["plast_name"],
                        "school" => [
                            "id" => $user["sid"],
                            "name" => $user["sname"],
                        ]
                    ]
                ];
            case Role::PSTAFF:
                return [
                    "id" => $user["uid"],
                    "email" => $user["uemail"],
                    "role" => $user["urole"],
                    "enabled" => $user["uenabled"],
                    "created_at" => $user["ucreated_at"],
                    "updated_at" => $user["uupdated_at"],
                    "profile" => [
                        "id" => $user["pid"],
                        "first_name" => $user["pfirst_name"],
                        "middle_name" => $user["pmiddle_name"],
                        "last_name" => $user["plast_name"],
                    ]
                ];
            case Role::COMPANY:
                return [
                    "id" => $user["uid"],
                    "email" => $user["uemail"],
                    "role" => $user["urole"],
                    "enabled" => $user["uenabled"],
                    "created_at" => $user["ucreated_at"],
                    "updated_at" => $user["uupdated_at"],
                    "profile" => [
                        "id" => $user["pid"],
                        "user_id" => $user["puser_id"],
                        "name" => $user["pname"],
                        "address" => $user["paddress"],
                        "industry" => $user["pindustry"],
                        "req_logo" => $user["preq_logo"],
                        "req_company_profile" => $user["preq_company_profile"],
                        "req_business_permit" => $user["preq_business_permit"],
                        "req_sec" => $user["preq_sec"],
                        "req_dti_cda" => $user["preq_dti_cda"],
                        "req_reg_of_est" => $user["preq_reg_of_est"],
                        "req_cert_from_dole" => $user["preq_cert_from_dole"],
                        "req_cert_no_case" => $user["preq_cert_no_case"],
                        "req_philjobnet_reg" => $user["preq_philjobnet_reg"],
                        "stat_req_logo" => $user["pstat_req_logo"],
                        "stat_req_company_profile" => $user["pstat_req_company_profile"],
                        "stat_req_business_permit" => $user["pstat_req_business_permit"],
                        "stat_req_sec" => $user["pstat_req_sec"],
                        "stat_req_dti_cda" => $user["pstat_req_dti_cda"],
                        "stat_req_reg_of_est" => $user["pstat_req_reg_of_est"],
                        "stat_req_cert_from_dole" => $user["pstat_req_cert_from_dole"],
                        "stat_req_cert_no_case" => $user["pstat_req_cert_no_case"],
                        "stat_req_philjobnet_reg" => $user["pstat_req_philjobnet_reg"],
                        "stat_req_list_of_vacancies" => $user["pstat_req_list_of_vacancies"],
                        "ver_stat_sysad" => $user["pver_stat_sysad"],
                        "ver_stat_pstaff" => $user["pver_stat_pstaff"],
                        "vacancies" => $user["vacancies"]
                    ]
                ];
        }
    }
}