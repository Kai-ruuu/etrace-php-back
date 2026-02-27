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
}