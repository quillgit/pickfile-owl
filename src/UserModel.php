<?php
namespace Faillink\Src;

class UserModel {
    private $pdo; public function __construct(\PDO $pdo){ $this->pdo = $pdo; }
    public function createDemoIfNotExists(){
        $stmt = $this->pdo->query('SELECT id FROM users LIMIT 1'); $row = $stmt->fetch(\PDO::FETCH_ASSOC); if ($row) return $row['id'];
        $ins = $this->pdo->prepare('INSERT INTO users (username) VALUES (:u)'); $ins->execute([':u'=>'demo']); return $this->pdo->lastInsertId();
    }
}