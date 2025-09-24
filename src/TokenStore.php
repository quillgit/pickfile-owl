<?php
namespace Faillink\Src;

class TokenStore {
    private $pdo;
    public function __construct(\PDO $pdo){ $this->pdo = $pdo; }
    public function get($userId){
        $stmt = $this->pdo->prepare('SELECT nextcloud_username, nc_access_token, nc_refresh_token, nc_token_expires_at FROM users WHERE id=:id');
        $stmt->execute([':id'=>$userId]); return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    public function save($userId,$ncUser,$accessToken,$refreshToken,$expiresIn){
        $expiresAt = time() + (int)$expiresIn - 30;
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE id=:id'); $stmt->execute([':id'=>$userId]);
        if ($stmt->fetch()){
            $upd = $this->pdo->prepare('UPDATE users SET nextcloud_username=:ncuser, nc_access_token=:at, nc_refresh_token=:rt, nc_token_expires_at=:exp WHERE id=:id');
            $upd->execute([':ncuser'=>$ncUser,':at'=>$accessToken,':rt'=>$refreshToken,':exp'=>$expiresAt,':id'=>$userId]);
        } else {
            $ins = $this->pdo->prepare('INSERT INTO users (id, username, nextcloud_username, nc_access_token, nc_refresh_token, nc_token_expires_at) VALUES (:id,:u,:ncuser,:at,:rt,:exp)');
            $ins->execute([':id'=>$userId,':u'=>'erp_user_'.$userId,':ncuser'=>$ncUser,':at'=>$accessToken,':rt'=>$refreshToken,':exp'=>$expiresAt]);
        }
    }
}