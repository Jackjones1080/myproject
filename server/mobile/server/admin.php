<?php
declare(strict_types=1);
// This endpoint lives at /mobile/server/admin.php; load the site's shared backend.
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

function apiInput(): array {
    $raw = file_get_contents('php://input');
    $v = json_decode($raw ?: '[]', true);
    return is_array($v) ? $v : [];
}
function apiNeedCsrf(): void {
    $sent = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($sent === '' || !hash_equals(csrfToken(), $sent)) jsonResponse(['ok'=>false,'error'=>'درخواست نامعتبر است؛ دوباره وارد شوید.'], 403);
}
function apiNeedAdmin(): void {
    if (!isAdminLoggedIn()) {
        jsonResponse(['ok'=>false,'error'=>'نشست ورود معتبر نیست؛ دوباره وارد شوید.'], 401);
    }
}
$in = apiInput();
$action = (string)($_GET['action'] ?? $in['action'] ?? '');
if ($action === 'login') {
    $pass = (string)($in['password'] ?? '');
    if ((int)($_SESSION['admin_lock_until'] ?? 0) > time()) jsonResponse(['ok'=>false,'error'=>'به علت تلاش ناموفق، ۱۰ دقیقه دیگر تلاش کنید.'],429);
    if (empty($config['admin_password']) || !password_verify($pass, (string)$config['admin_password'])) {
        $fails = (int)($_SESSION['admin_fails'] ?? 0) + 1;
        $_SESSION['admin_fails'] = $fails;
        if ($fails >= 5) { $_SESSION['admin_lock_until'] = time()+600; $_SESSION['admin_fails']=0; }
        usleep(350000);
        jsonResponse(['ok'=>false,'error'=>'رمز مدیریت اشتباه است.'],401);
    }
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_last'] = time();
    unset($_SESSION['admin_fails'], $_SESSION['admin_lock_until']);
    jsonResponse(['ok'=>true,'csrf'=>csrfToken(),'message'=>'ورود موفق بود']);
}
if ($action === 'logout') { apiNeedAdmin(); apiNeedCsrf(); $_SESSION=[]; session_destroy(); jsonResponse(['ok'=>true]); }
apiNeedAdmin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') apiNeedCsrf();

switch ($action) {
case 'dashboard':
    $out = [];
    foreach ([
      'registrations_pending'=>"SELECT COUNT(*) FROM registrations WHERE status='pending'",
      'registrations_active'=>"SELECT COUNT(*) FROM registrations WHERE status='active'",
      'ceremonies'=>"SELECT COUNT(*) FROM ceremonies",
      'challenges_pending'=>"SELECT COUNT(*) FROM challenges WHERE status='pending'",
      'donations_new'=>"SELECT COUNT(*) FROM non_cash_donations WHERE status='new'"
    ] as $k=>$sql) { $out[$k]=(int)$pdo->query($sql)->fetchColumn(); }
    jsonResponse(['ok'=>true,'data'=>$out,'csrf'=>csrfToken()]);
case 'registrations':
    $rows=$pdo->query("SELECT id,first_name,last_name,phone,status,role,created_at FROM registrations ORDER BY id DESC LIMIT 200")->fetchAll();
    jsonResponse(['ok'=>true,'data'=>$rows]);
case 'registration_update':
    $id=(int)($in['id']??0); $status=(string)($in['status']??''); $role=(string)($in['role']??'member');
    if($id<1 || !in_array($status,['pending','active','suspended','rejected'],true) || !in_array($role,['member','volunteer','manager'],true)) jsonResponse(['ok'=>false,'error'=>'اطلاعات نامعتبر است.'],422);
    $pdo->prepare('UPDATE registrations SET status=?,role=? WHERE id=?')->execute([$status,$role,$id]);
    if($status==='active') { $q=$pdo->prepare('SELECT national_id,password_hash FROM registrations WHERE id=?');$q->execute([$id]);$m=$q->fetch(); if($m && empty($m['password_hash']) && !empty($m['national_id'])) $pdo->prepare('UPDATE registrations SET password_hash=?,invite_hash=NULL,invite_expires=NULL WHERE id=?')->execute([makePasswordHash($m['national_id']),$id]); }
    jsonResponse(['ok'=>true,'message'=>'وضعیت عضو ذخیره شد.']);
case 'ceremonies':
    $rows=$pdo->query('SELECT id,title,description,event_date,is_published FROM ceremonies ORDER BY id DESC LIMIT 200')->fetchAll(); jsonResponse(['ok'=>true,'data'=>$rows]);
case 'ceremony_save':
    $id=(int)($in['id']??0);$title=trim((string)($in['title']??''));$desc=trim((string)($in['description']??''));$date=trim((string)($in['event_date']??''));$pub=!empty($in['is_published'])?1:0;
    if($title==='') jsonResponse(['ok'=>false,'error'=>'عنوان مراسم الزامی است.'],422);
    if($id>0) $pdo->prepare('UPDATE ceremonies SET title=?,description=?,event_date=?,is_published=? WHERE id=?')->execute([$title,$desc,$date,$pub,$id]);
    else $pdo->prepare('INSERT INTO ceremonies(title,description,event_date,is_published) VALUES(?,?,?,?)')->execute([$title,$desc,$date,$pub]);
    jsonResponse(['ok'=>true,'message'=>'مراسم ذخیره شد.']);
case 'ceremony_delete':
    $id=(int)($in['id']??0);if($id<1)jsonResponse(['ok'=>false,'error'=>'شناسه نامعتبر است.'],422);$pdo->prepare('DELETE FROM ceremony_images WHERE ceremony_id=?')->execute([$id]);$pdo->prepare('DELETE FROM ceremonies WHERE id=?')->execute([$id]);jsonResponse(['ok'=>true,'message'=>'مراسم حذف شد.']);
case 'challenges':
    $rows=$pdo->query('SELECT id,sender_name,full_name,phone,body,status,created_at FROM challenges ORDER BY id DESC LIMIT 200')->fetchAll();jsonResponse(['ok'=>true,'data'=>$rows]);
case 'challenge_update':
    $id=(int)($in['id']??0);$status=(string)($in['status']??'');if($id<1||!in_array($status,['pending','approved','rejected'],true))jsonResponse(['ok'=>false,'error'=>'اطلاعات نامعتبر است.'],422);$pdo->prepare('UPDATE challenges SET status=? WHERE id=?')->execute([$status,$id]);jsonResponse(['ok'=>true,'message'=>'وضعیت ثبت شد.']);
case 'donations':
    $rows=$pdo->query('SELECT id,contact_name,contact_phone,description,status,created_at FROM non_cash_donations ORDER BY id DESC LIMIT 200')->fetchAll();jsonResponse(['ok'=>true,'data'=>$rows]);
case 'donation_update':
    $id=(int)($in['id']??0);$status=(string)($in['status']??'');if($id<1||!in_array($status,['new','contacted','done'],true))jsonResponse(['ok'=>false,'error'=>'اطلاعات نامعتبر است.'],422);$pdo->prepare('UPDATE non_cash_donations SET status=? WHERE id=?')->execute([$status,$id]);jsonResponse(['ok'=>true,'message'=>'وضعیت کمک ثبت شد.']);
default: jsonResponse(['ok'=>false,'error'=>'عملیات شناخته نشد.'],404);
}
