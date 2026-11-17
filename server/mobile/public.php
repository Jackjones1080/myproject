<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/migrate.php';
runMigrations($pdo);
$action = (string)($_GET['action'] ?? 'home');
try {
  if ($action === 'home') {
    $out = [
      'settings' => [
        'title' => (string)($settings['title'] ?? 'چایخانه امام مهدی (عج)'),
        'welcomeTitle' => (string)($settings['welcomeTitle'] ?? 'به چایخانه خوش آمدید'),
        'welcomeText' => (string)($settings['welcomeText'] ?? ''),
        'aboutText' => (string)($settings['aboutText'] ?? ''),
        'phone' => (string)($settings['phone'] ?? ''),
        'address' => (string)($settings['address'] ?? ''),
      ],
      'slides' => getHeaderSlides($pdo),
      'gallery' => getGallery($pdo, 30),
      'skills' => getSkillOptions($pdo),
    ];
    jsonResponse(['ok'=>true,'data'=>$out]);
  }
  if ($action === 'ceremonies') {
    $rows=$pdo->query("SELECT id,title,description,event_date FROM ceremonies WHERE is_published=1 ORDER BY id DESC LIMIT 100")->fetchAll();
    foreach($rows as &$r) {
      $q=$pdo->prepare('SELECT id,url,title FROM ceremony_images WHERE ceremony_id=? ORDER BY sort_order,id');
      $q->execute([(int)$r['id']]); $r['images']=$q->fetchAll();
    }
    jsonResponse(['ok'=>true,'data'=>$rows]);
  }
  if ($action === 'gallery') jsonResponse(['ok'=>true,'data'=>getGallery($pdo,100)]);
  if ($action === 'challenges') {
    $rows=$pdo->query("SELECT id,COALESCE(NULLIF(full_name,''),sender_name) AS name,body,image_url,created_at FROM challenges WHERE status='approved' ORDER BY id DESC LIMIT 50")->fetchAll();
    jsonResponse(['ok'=>true,'data'=>$rows]);
  }
  if ($action === 'register_options') jsonResponse(['ok'=>true,'data'=>['skills'=>getSkillOptions($pdo)]]);
  jsonResponse(['ok'=>false,'error'=>'درخواست شناخته نشد.'],404);
} catch(Throwable $e) {
  error_log('[chaikhaneh public api] '.$e->getMessage());
  jsonResponse(['ok'=>false,'error'=>'دریافت اطلاعات با مشکل روبه‌رو شد.'],500);
}
