<?php
declare(strict_types=1);

session_name('T3K_OWNER_SESSION');
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const OWNER_PASSWORD_HASH = '$2y$12$.9ki9IgGyEQusjw7dIc2Ae0djEhATBnd5OCIfU9Mlk8Yjwe9qA4TC';
const DATA_FILE = __DIR__ . '/data/applications.json';

function respond(bool $success, string $message = '', array $extra = [], int $status = 200): never {
    http_response_code($status);
    echo json_encode(array_merge(['success'=>$success, 'message'=>$message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
function input(): array {
    $raw=file_get_contents('php://input');
    if($raw===false || $raw==='') return [];
    $data=json_decode($raw,true);
    return is_array($data)?$data:[];
}
function owner(): bool { return !empty($_SESSION['t3k_owner']); }
function readApps(): array {
    if(!is_file(DATA_FILE)) return [];
    $raw=@file_get_contents(DATA_FILE);
    if($raw===false || $raw==='') return [];
    $data=json_decode($raw,true);
    return is_array($data)?$data:[];
}
function writeApps(array $apps): void {
    $dir=dirname(DATA_FILE);
    if(!is_dir($dir)) @mkdir($dir,0755,true);
    $tmp=DATA_FILE.'.tmp';
    $json=json_encode($apps,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    if(@file_put_contents($tmp,$json,LOCK_EX)===false || !@rename($tmp,DATA_FILE)){
        @unlink($tmp); respond(false,'Speichern auf dem Server fehlgeschlagen.',[],500);
    }
}

$action=$_GET['action']??'';

if($action==='login' && $_SERVER['REQUEST_METHOD']==='POST'){
    $p=(string)(input()['password']??'');
    if(password_verify($p,OWNER_PASSWORD_HASH)){
        session_regenerate_id(true); $_SESSION['t3k_owner']=true; respond(true,'Login erfolgreich.');
    }
    respond(false,'Falsches Passwort.',[],401);
}
if($action==='session' && $_SERVER['REQUEST_METHOD']==='GET'){ respond(true,'', ['authenticated'=>owner()]); }
if($action==='logout' && $_SERVER['REQUEST_METHOD']==='POST'){ $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),'','time()-42000',$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); respond(true,'Abgemeldet.'); }

if($action==='list' && $_SERVER['REQUEST_METHOD']==='GET'){
    if(!owner()) respond(false,'Nicht autorisiert.',[],401);
    $apps=readApps(); usort($apps,fn($a,$b)=>strcmp((string)($b['createdAt']??''),(string)($a['createdAt']??'')));
    foreach($apps as &$a){$a['createdAtDisplay']=isset($a['createdAt'])?date('d.m.Y H:i',strtotime((string)$a['createdAt'])):'-';} unset($a);
    respond(true,'',['applications'=>$apps]);
}
if($action==='delete' && $_SERVER['REQUEST_METHOD']==='POST'){
    if(!owner()) respond(false,'Nicht autorisiert.',[],401);
    $id=(string)(input()['id']??''); if($id==='') respond(false,'Ungültige Bewerbung.',[],400);
    $apps=readApps(); $before=count($apps); $apps=array_values(array_filter($apps,fn($a)=>(string)($a['id']??'')!==$id));
    if(count($apps)===$before) respond(false,'Bewerbung nicht gefunden.',[],404); writeApps($apps); respond(true,'Bewerbung gelöscht.');
}

if($_SERVER['REQUEST_METHOD']==='POST' && $action===''){
    $d=input(); $name=trim((string)($d['name']??'')); $age=(int)($d['age']??0); $reason=trim((string)($d['reason']??'')); $skills=is_array($d['skills']??null)?$d['skills']:[];
    if($name==='' || strlen($name)>240 || $age<13 || $age>99 || $reason==='' || strlen($reason)>8000) respond(false,'Bitte die Bewerbung vollständig und gültig ausfüllen.',[],400);
    $cleanSkills=[]; foreach(['blaze','l4l','speed','stamina'] as $k){$v=(int)($skills[$k]??1);$cleanSkills[$k]=max(1,min(5,$v));}
    $apps=readApps(); $apps[]=['id'=>bin2hex(random_bytes(12)),'name'=>$name,'age'=>$age,'reason'=>$reason,'skills'=>$cleanSkills,'createdAt'=>gmdate('c')]; writeApps($apps); respond(true,'Bewerbung gespeichert.');
}
respond(false,'Ungültige Anfrage.',[],400);
