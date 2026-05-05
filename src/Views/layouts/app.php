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
<script src="/assets/js/app.js" defer></script>
</body>
</html>
