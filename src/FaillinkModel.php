<?php
namespace Faillink\Src;

class FaillinkModel {
    private $pdo; public function __construct(\PDO $pdo){ $this->pdo = $pdo; }
    public function create(array $data){
        $sql = 'INSERT INTO faillinks (user_id,nextcloud_path,nextcloud_fileid,name,mime,size,raw_metadata) VALUES (:user_id,:nextcloud_path,:nextcloud_fileid,:name,:mime,:size,:raw_metadata)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id'=>$data['user_id'],':nextcloud_path'=>$data['nextcloud_path'],':nextcloud_fileid'=>$data['nextcloud_fileid'] ?? null,':name'=>$data['name'],':mime'=>$data['mime'] ?? null,':size'=>$data['size'] ?? null,':raw_metadata'=>isset($data['raw_metadata'])?json_encode($data['raw_metadata']):null]);
        return $this->pdo->lastInsertId();
    }
    public function find($id){ $stmt = $this->pdo->prepare('SELECT * FROM faillinks WHERE id=:id'); $stmt->execute([':id'=>$id]); return $stmt->fetch(\PDO::FETCH_ASSOC); }
}