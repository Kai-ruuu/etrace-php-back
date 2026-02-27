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
        }
    }
}