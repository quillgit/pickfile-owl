<?php
// Minimal Nextcloud OAuth + WebDAV client using cURL. No external deps.
namespace Faillink\Src;

class NCClient {
    private $baseUrl; private $clientId; private $clientSecret; private $redirectUri;
    public function __construct(array $conf) {
        $this->baseUrl = rtrim($conf['base_url'], '/');
        $this->clientId = $conf['client_id'];
        $this->clientSecret = $conf['client_secret'];
        $this->redirectUri = $conf['redirect_uri'] ?? null;
    }
    private function autoRedirectUri(){
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . '/nextcloud/callback';
    }
    public function getAuthorizeUrl(string $state=''){
        $redirect = $this->redirectUri ?: $this->autoRedirectUri();
        $url = $this->baseUrl . '/index.php/apps/oauth2/authorize';
        return $url . '?' . http_build_query(['response_type'=>'code','client_id'=>$this->clientId,'redirect_uri'=>$redirect,'state'=>$state]);
    }
    private function httpPostForm(string $url, array $data){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status'=>$code,'json'=>json_decode($resp,true),'raw'=>$resp];
    }
    public function fetchAccessToken(string $code){
        $url = $this->baseUrl . '/index.php/apps/oauth2/api/v1/token';
        $data = ['grant_type'=>'authorization_code','code'=>$code,'redirect_uri'=>$this->redirectUri ?: $this->autoRedirectUri(),'client_id'=>$this->clientId,'client_secret'=>$this->clientSecret];
        return $this->httpPostForm($url,$data);
    }
    public function refreshToken(string $refreshToken){
        $url = $this->baseUrl . '/index.php/apps/oauth2/api/v1/token';
        $data = ['grant_type'=>'refresh_token','refresh_token'=>$refreshToken,'client_id'=>$this->clientId,'client_secret'=>$this->clientSecret];
        return $this->httpPostForm($url,$data);
    }
    public function propfind(string $ncUser, string $path, string $accessToken){
        $prefix = $this->baseUrl . '/remote.php/dav/files/' . rawurlencode($ncUser) . '/';
        $url = rtrim($prefix . ltrim($path, '/'), '/');
        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<d:propfind xmlns:d=\"DAV:\">\n  <d:allprop/>\n</d:propfind>";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'PROPFIND');
        curl_setopt($ch, CURLOPT_POSTFIELDS,$xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,['Authorization: Bearer '.$accessToken,'Depth: 1','Content-Type: text/xml','Accept: application/xml']);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status'=>$code,'body'=>$resp];
    }
    public function downloadFile(string $ncUser,string $path,string $accessToken){
        $prefix = $this->baseUrl . '/remote.php/dav/files/' . rawurlencode($ncUser) . '/';
        $url = rtrim($prefix . ltrim($path, '/'), '/');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,['Authorization: Bearer '.$accessToken]);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return ['status'=>$info['http_code'] ?? 200,'body'=>$body,'info'=>$info];
    }
}