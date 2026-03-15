<?php

class Profile
{   
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getSysadById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM sysads WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getDeanById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM deans WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getPstaffById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM pstaffs WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getCompanyById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getAlumniById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM alumni WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function getDeanByUserId($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM deans WHERE user_id = ?");
        $statement->execute([$id]);
        return $statement->fetch();
    }
    
    public function updateSysadById($id, $sysad)
    {
        $statement = $this->pdo->prepare("
            UPDATE sysads
            SET
                first_name = ?,
                middle_name = ?,
                last_name = ?
            WHERE id = ?
        ");
        $statement->execute([
            $sysad["first_name"],
            $sysad["middle_name"],
            $sysad["last_name"],
            $id
        ]);
        return $statement->rowCount() > 0;
    }
    
    public function updateDeanById($id, $dean)
    {
        $statement = $this->pdo->prepare("
            UPDATE deans
            SET
                first_name = ?,
                middle_name = ?,
                last_name = ?
            WHERE id = ?
        ");
        $statement->execute([
            $dean["first_name"],
            $dean["middle_name"],
            $dean["last_name"],
            $id
        ]);
        return $statement->rowCount() > 0;
    }
    
    public function updatePstaffById($id, $pstaff)
    {
        $statement = $this->pdo->prepare("
            UPDATE pstaffs
            SET
                first_name = ?,
                middle_name = ?,
                last_name = ?
            WHERE id = ?
        ");
        $statement->execute([
            $pstaff["first_name"],
            $pstaff["middle_name"],
            $pstaff["last_name"],
            $id
        ]);
        return $statement->rowCount() > 0;
    }
    
    public function updateCompanyById($id, $company)
    {
        $statement = $this->pdo->prepare("
            UPDATE companies
            SET
                name = ?,
                address = ?,
                req_logo = ?,
                req_company_profile = ?,
                req_business_permit = ?,
                req_sec = ?,
                req_dti_cda = ?,
                req_reg_of_est = ?,
                req_cert_from_dole = ?,
                req_cert_no_case = ?,
                req_philjobnet_reg = ?,
                stat_req_logo = ?,
                stat_req_company_profile = ?,
                stat_req_business_permit = ?,
                stat_req_sec = ?,
                stat_req_dti_cda = ?,
                stat_req_reg_of_est = ?,
                stat_req_cert_from_dole = ?,
                stat_req_cert_no_case = ?,
                stat_req_philjobnet_reg = ?,
                stat_req_list_of_vacancies = ?,
                ver_stat_sysad = ?,
                ver_stat_pstaff = ?
            WHERE id = ?
        ");
        $statement->execute([
            $company["name"],
            $company["address"],
            $company["req_logo"],
            $company["req_company_profile"],
            $company["req_business_permit"],
            $company["req_sec"],
            $company["req_dti_cda"],
            $company["req_reg_of_est"],
            $company["req_cert_from_dole"],
            $company["req_cert_no_case"],
            $company["req_philjobnet_reg"],
            $company["stat_req_logo"],
            $company["stat_req_company_profile"],
            $company["stat_req_business_permit"],
            $company["stat_req_sec"],
            $company["stat_req_dti_cda"],
            $company["stat_req_reg_of_est"],
            $company["stat_req_cert_from_dole"],
            $company["stat_req_cert_no_case"],
            $company["stat_req_philjobnet_reg"],
            $company["stat_req_list_of_vacancies"],
            $company["ver_stat_sysad"],
            $company["ver_stat_pstaff"],
            $id
        ]);
        return $statement->rowCount() > 0;
    }

    public function rejectCompany($rejection)
    {   
        try {
            $this->pdo->beginTransaction();

            $sysadId = $rejection["sysad_id"] ?? null;
            $pstaffId = $rejection["pstaff_id"] ?? null;
            $companyId = $rejection["company_id"];
            $message = $rejection["message"];

            $updateSetter = $sysadId !== null
                ? "ver_stat_sysad = 'Rejected'"
                : "ver_stat_pstaff = 'Rejected'";
            
            // update company ver status
            $statement = $this->pdo->prepare("
                UPDATE companies SET {$updateSetter}
                WHERE id = ?
            ");
            $statement->execute([$companyId]);

            // create the rejection message
            $statement = $this->pdo->prepare("
                INSERT INTO company_rejection_messages
                    (sysad_id, pstaff_id, company_id, message)
                VALUES
                    (?, ?, ?, ?)
            ");
            $statement->execute([$sysadId, $pstaffId, $companyId, $message]);

            // commit
            $this->pdo->commit();

            return $companyId;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            return null;
        }
    }

    public function forReviseComanyRequirement($revision)
    {
        try {
            $this->pdo->beginTransaction();

            $pstaffId = $revision["pstaff_id"];
            $companyId = $revision["company_id"];
            $message = $revision["message"];
            $requirementStat = $revision["requirement_stat"];
            
            // update company requirement's status
            $statement = $this->pdo->prepare("
                UPDATE companies SET {$requirementStat} = ?
                WHERE id = ?
            ");
            $statement->execute(["For Revision", $companyId]);

            // create the revision message
            $statement = $this->pdo->prepare("
                INSERT INTO company_revision_messages
                    (company_id, pstaff_id, message, requirement_name)
                VALUES
                    (?, ?, ?, ?)
            ");
            $statement->execute([$companyId, $pstaffId, $message, str_replace("stat_", "", $requirementStat)]);

            // commit
            $this->pdo->commit();

            return $companyId;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->pdo->rollback();
            return null;
        }
    }

    public function updateAlumniById($id, $alumni)
    {
        $statement = $this->pdo->prepare("
            UPDATE alumni
            SET
                name_extension = ?,
                first_name = ?,
                middle_name = ?,
                last_name = ?,
                birth_date = ?,
                birth_place = ?,
                gender = ?,
                student_number = ?,
                phone_number = ?,
                civil_status = ?,
                employment_status = ?,
                file_profile_picture = ?,
                file_cv = ?,
                ver_stat_dean = ?
            WHERE id = ?
        ");
        $statement->execute([
            $alumni["name_extension"],
            $alumni["first_name"],
            $alumni["middle_name"],
            $alumni["last_name"],
            $alumni["birth_date"],
            $alumni["birth_place"],
            $alumni["gender"],
            $alumni["student_number"],
            $alumni["phone_number"],
            $alumni["civil_status"],
            $alumni["employment_status"],
            $alumni["file_profile_picture"],
            $alumni["file_cv"],
            $alumni["ver_stat_dean"],
            $id
        ]);
        return $statement->rowCount() > 0;
    }

    public function rejectAlumni($rejection)
    {   
        try {
            $this->pdo->beginTransaction();

            $deanId = $rejection["dean_id"];
            $alumniId = $rejection["alumni_id"];
            $message = $rejection["message"];

            // update company ver status
            $statement = $this->pdo->prepare("
                UPDATE alumni
                SET ver_stat_dean = 'Rejected'
                WHERE id = ?
            ");
            $statement->execute([$alumniId]);

            // create the rejection message
            $statement = $this->pdo->prepare("
                INSERT INTO alumni_rejection_messages
                    (dean_id, alumni_id, message)
                VALUES
                    (?, ?, ?)
            ");
            $statement->execute([$deanId, $alumniId, $message]);

            // commit
            $this->pdo->commit();

            return $alumniId;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->pdo->rollback();
            return null;
        }
    }
}