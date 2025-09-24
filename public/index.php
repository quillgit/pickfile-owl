<?php
// Front controller — adapt into your ERP. Keep public/ as webroot.
require_once __DIR__ . '/../config.php'; $config = require __DIR__ . '/../config.php';
// Simple autoload for Faillink\Src\* classes
spl_autoload_register(function($c){ $base = __DIR__ . '/../src/'; $prefix = 'Faillink\\Src\\'; if (strpos($c,$prefix)===0){ $rel = substr($c,strlen($prefix)); $p = $base . str_replace('\\','/',$rel) . '.php'; if (file_exists($p)) require $p; }});
session_start();
$pdo = new PDO($config['db_dsn'],$config['db_user'],$config['db_pass']); $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$nc = new \Faillink\Src\NCClient($config['nextcloud']);
$tokenStore = new \Faillink\Src\TokenStore($pdo);
$faillinkModel = new \Faillink\Src\FaillinkModel($pdo);
$userModel = new \Faillink\Src\UserModel($pdo);
if (empty($_SESSION['erp_user_id'])) $_SESSION['erp_user_id'] = $userModel->createDemoIfNotExists();
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function json($d,$c=200){ http_response_code($c); header('Content-Type: application/json'); echo json_encode($d); exit; }

if ($path === '/nextcloud/connect'){
    $state = base64_encode(json_encode(['uid'=>$_SESSION['erp_user_id'],'nonce'=>bin2hex(random_bytes(8))])); $_SESSION['nc_oauth_state']=$state; header('Location: '.$nc->getAuthorizeUrl($state)); exit;
}
if ($path === '//nextcloud/callback'){
    $code = $_GET['code'] ?? null; $state = $_GET['state'] ?? null; if (!$code || $state !== ($_SESSION['nc_oauth_state'] ?? null)) { echo 'Invalid OAuth response'; exit; }
    $resp = $nc->fetchAccessToken($code); if ($resp['status']>=400 || empty($resp['json'])){ echo 'Token exchange failed'; exit; }
    $j = $resp['json']; $accessToken=$j['access_token']; $refreshToken=$j['refresh_token'] ?? null; $expiresIn=$j['expires_in'] ?? 3600; $ncUser = $j['user'] ?? 'unknown'; $tokenStore->save($_SESSION['erp_user_id'],$ncUser,$accessToken,$refreshToken,$expiresIn); echo '<h3>Connected</h3><p>Close this window and return to ERP.</p>'; exit;
}
if ($path === '/nextcloud/list'){
    $userId = $_SESSION['erp_user_id']; $qPath = $_GET['path'] ?? '/'; $tokens = $tokenStore->get($userId); if (!$tokens) json(['error'=>'no_tokens'],401);
    // refresh if expired
    if ($tokens['nc_token_expires_at'] <= time()+10){ $r = $nc->refreshToken($tokens['nc_refresh_token']); if ($r['status']<400){ $j=$r['json']; $tokenStore->save($userId,$tokens['nextcloud_username'],$j['access_token'],$j['refresh_token'] ?? $tokens['nc_refresh_token'],$j['expires_in'] ?? 3600); $tokens=$tokenStore->get($userId);} else { json(['error'=>'refresh_failed'],401); }}
    $itemsResp = $nc->propfind($tokens['nextcloud_username'],$qPath,$tokens['nc_access_token']); if ($itemsResp['status']>=400) json(['error'=>'propfind_failed'], $itemsResp['status']);
    $xml = @simplexml_load_string($itemsResp['body']); if (!$xml) json([]);
    $items=[]; foreach ($xml->response as $r){ $href=(string)$r->href; $prop=$r->propstat->prop ?? null; $name=basename(urldecode($href)); $isDir=false; if ($prop && isset($prop->resourcetype->collection)) $isDir=true; $size=isset($prop->getcontentlength)?(int)$prop->getcontentlength:null; $mime=isset($prop->getcontenttype)?(string)$prop->getcontenttype:null; $items[]=['href'=>$href,'name'=>$name,'is_dir'=>$isDir,'size'=>$size,'mime'=>$mime]; }
    json($items);
}
if ($path === '/nextcloud/pick' && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $body = json_decode(file_get_contents('php://input'), true); if (!$body || empty($body['href']) || empty($body['name'])) json(['error'=>'invalid_payload'],400);
    $userId = $_SESSION['erp_user_id']; $tokens = $tokenStore->get($userId); if (!$tokens) json(['error'=>'no_tokens'],401);
    $basePrefix = rtrim($config['nextcloud']['base_url'],'/') . '/remote.php/dav/files/' . rawurlencode($tokens['nextcloud_username']) . '/'; $relPath = $body['href']; if (strpos($relPath,$basePrefix)===0) $relPath = substr($relPath, strlen($basePrefix)); else{ $u=parse_url($relPath); $relPath = isset($u['path'])? ltrim(preg_replace('#/remote.php/dav/files/[^/]+/#','',$u['path']), '/'): $body['href']; }
    $id = $faillinkModel->create(['user_id'=>$userId,'nextcloud_path'=>$relPath,'nextcloud_fileid'=>null,'name'=>$body['name'],'mime'=>$body['mime'] ?? null,'size'=>$body['size'] ?? null,'raw_metadata'=>$body]); json(['id'=>$id,'status'=>'ok']);
}
if ($path === '/faillink/download'){
    $userId = $_SESSION['erp_user_id']; $id = $_GET['id'] ?? null; if (!$id) { http_response_code(400); echo 'missing id'; exit; }
    $record = $faillinkModel->find($id); if (!$record) { http_response_code(404); echo 'not found'; exit; }
    if ((int)$record['user_id'] !== (int)$userId) { http_response_code(403); echo 'forbidden'; exit; }
    $tokens = $tokenStore->get($userId); if (!$tokens) { http_response_code(401); echo 'no tokens'; exit; }
    if ($tokens['nc_token_expires_at'] <= time()+10){ $r = $nc->refreshToken($tokens['nc_refresh_token']); if ($r['status']<400){ $j=$r['json']; $tokenStore->save($userId,$tokens['nextcloud_username'],$j['access_token'],$j['refresh_token'] ?? $tokens['nc_refresh_token'],$j['expires_in'] ?? 3600); $tokens=$tokenStore->get($userId);} else { http_response_code(401); echo 'refresh failed'; exit; }}
    $dl = $nc->downloadFile($tokens['nextcloud_username'],$record['nextcloud_path'],$tokens['nc_access_token']); if ($dl['status']>=400){ http_response_code($dl['status']); echo 'download failed'; exit; }
    $contentType = $record['mime'] ?: ($dl['info']['content_type'] ?? 'application/octet-stream'); header('Content-Type: '.$contentType); header('Content-Disposition: inline; filename="'.basename($record['name']).'"'); echo $dl['body']; exit;
}
// default info page
header('Content-Type: text/html'); echo '<h1>Nextcloud Private Picker (Modular Demo)</h1><p>See /public/picker.html for embeddable Vue picker.</p>';