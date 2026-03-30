<?php

class PasswordForgot
{
    protected PDO $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($userId, $token)
    {
        $statement = $this->pdo->prepare("UPDATE password_forgots SET expired = TRUE WHERE user_id = ?");
        $statement->execute([$userId]);

        $statement = $this->pdo->prepare("INSERT INTO password_forgots (token, user_id) VALUES (?, ?)");
        $statement->execute([$token, $userId]);
        return $this->pdo->lastInsertId();
    }

    public function getByToken($token): array
    {
        $statement = $this->pdo->prepare("SELECT * FROM password_forgots WHERE token = ?");
        $statement->execute([$token]);
        return $statement->fetch();
    }

    public function updateById(int $id, array $passwordForgot): bool
    {
        $statement = $this->pdo->prepare("UPDATE password_forgots SET used = ?, expired = ? WHERE id = ?");
        $statement->execute([$passwordForgot["used"], $passwordForgot["expired"], $id]);
        return $statement->rowCount() > 0;
    }
}