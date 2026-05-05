<?php
use App\Core\View;
use App\Core\Auth;
use App\Core\Db;
$e = static fn(?string $s): string => View::e($s);
$user = Auth::user();
$path = $_SERVER['REQUEST_URI'] ?? '/';
$pendingCount = (int) (Db::fetch('SELECT COUNT(*) AS c FROM venues WHERE status = "pending"')['c'] ?? 0);
$failedHooks = (int) (Db::fetch('SELECT COUNT(*) AS c FROM webhooks WHERE status = "failed"')['c'] ?? 0);
$nav = [
    ['/admin',           '대시보드',     0],
    ['/admin/venues',    '구장 관리',    $pendingCount],
    ['/admin/users',     '사용자 관리',  0],
    ['/admin/finance',   '재무·환불',    0],
    ['/admin/reports',   '신고·이슈',    0],
    ['/admin/tags',      '시설 태그',    0],
    ['/admin/broadcast', '공지 발송',    0],
    ['/admin/logs',      '시스템 로그',  $failedHooks],
];
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#1e50ff">
<title><?= $e($title ?? '어드민 — 코트맵') ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/operator.css">
<style>
  /* admin 전용 강조 — 사이드바 진한 톤 */
  .op-side { background: #0f1320; color: #fff; border-right: 1px solid #1d2330; }
  .op-side .op-brand a { color: #fff; }
  .op-side .op-brand span { background: var(--brand-700); color: #fff; }
  .op-side nav a { color: rgba(255,255,255,.7); }
  .op-side nav a:hover { background: rgba(255,255,255,.06); color: #fff; }
  .op-side nav a.active { background: var(--brand-500); color: #fff; }
  .op-side-foot { border-top-color: #1d2330; }
  .op-uname { color: #fff; }
  .op-uemail { color: rgba(255,255,255,.5); }
  .op-side .badge-line { background: var(--hot-500); color: #fff; border: none; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
</style>
</head>
<body class="op">
<div class="op-shell">
  <aside class="op-side">
    <div class="op-brand"><a href="/admin">코트맵 <span>ADMIN</span></a></div>
    <nav>
      <?php foreach ($nav as [$href, $label, $count]):
        $active = ($href === '/admin') ? ($path === '/admin' || $path === '/admin/') : str_starts_with($path, $href);
      ?>
        <a href="<?= $e($href) ?>" class="<?= $active ? 'active' : '' ?>" style="display:flex;align-items:center;gap:8px">
          <span><?= $e($label) ?></span>
          <?php if ($count > 0): ?><span class="badge-line"><?= (int)$count ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="op-side-foot">
      <div class="op-user">
        <div class="op-avatar"><?= $e(mb_substr($user['name'] ?? '관', 0, 1)) ?></div>
        <div>
          <div class="op-uname"><?= $e($user['name']) ?></div>
          <div class="op-uemail"><?= $e($user['email']) ?></div>
        </div>
      </div>
      <form method="post" action="/logout">
        <button type="submit" class="btn btn-line btn-sm btn-block">로그아웃</button>
      </form>
    </div>
  </aside>
  <main class="op-main">
    <?= $content ?>
  </main>
</div>
<script>
(function() {
  const token = <?= json_encode(\App\Core\Csrf::token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(f => {
      if ((f.getAttribute('method') || '').toLowerCase() !== 'post') return;
      if (f.querySelector('input[name="_csrf"]')) return;
      const i = document.createElement('input');
      i.type = 'hidden'; i.name = '_csrf'; i.value = token;
      f.appendChild(i);
    });
  });
})();
</script>
</body>
</html>
