<?php /** @var string $content */ /** @var string $title */ ?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#1e50ff">
<title><?= \App\Core\View::e($title ?? '코트맵') ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?= $content ?>
<script>
// 모든 POST form 에 CSRF 토큰 자동 주입
(function() {
  const token = <?= json_encode(\App\Core\Csrf::token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(f => {
      const m = (f.getAttribute('method') || '').toLowerCase();
      if (m !== 'post') return;
      if (f.querySelector('input[name="_csrf"]')) return;
      const i = document.createElement('input');
      i.type = 'hidden'; i.name = '_csrf'; i.value = token;
      f.appendChild(i);
    });
  });
})();
</script>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
