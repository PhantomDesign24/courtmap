<?php
use App\Core\View;
use App\Core\Auth;
$e = static fn(?string $s): string => View::e($s);
$user = Auth::user();
$path = $_SERVER['REQUEST_URI'] ?? '/';
$nav = [
    ['/operator',           '대시보드'],
    ['/operator/deposits',  '입금 확인'],
    ['/operator/bookings',  '예약 관리'],
    ['/operator/venues',    '구장·코트'],
    ['/operator/slots',     '슬롯 규칙'],
    ['/operator/pricing',   '다이나믹 프라이싱'],
    ['/operator/coupons',   '쿠폰·멤버십'],
    ['/operator/equipment', '장비 대여'],
    ['/operator/coaches',   '강사 관리'],
    ['/operator/api',       'API·연동'],
];
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#1e50ff">
<title><?= $e($title ?? '운영자 — 코트맵') ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/operator.css">
</head>
<body class="op">
<div class="op-shell">
  <aside class="op-side">
    <div class="op-brand"><a href="/operator">코트맵 <span>운영자</span></a></div>
    <nav>
      <?php foreach ($nav as [$href, $label]):
        $active = ($href === '/operator') ? ($path === '/operator' || $path === '/operator/') : str_starts_with($path, $href);
      ?>
        <a href="<?= $e($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= $e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="op-side-foot">
      <div class="op-user">
        <div class="op-avatar"><?= $e(mb_substr($user['name'] ?? '운', 0, 1)) ?></div>
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
