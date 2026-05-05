<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
$screen = $screen ?? 'home';
$data   = $data   ?? [];
$desc   = $description ?? '전국 사설 배드민턴 구장 빈 코트를 실시간으로 보고 1시간 단위로 예약하세요. 코트맵.';
$ogImage = $og_image ?? 'https://bad.mvc.kr/assets/img/og-default.png';
$canonical = $canonical ?? ('https://bad.mvc.kr' . ($_SERVER['REQUEST_URI'] ?? '/'));
$noindex = $noindex ?? false;
$jsonld = $jsonld ?? null;
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#1e50ff">
<title><?= $e($title ?? '코트맵') ?></title>
<meta name="description" content="<?= $e($desc) ?>">
<?php if ($noindex): ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<link rel="canonical" href="<?= $e($canonical) ?>">
<!-- Open Graph -->
<meta property="og:site_name" content="코트맵">
<meta property="og:type" content="<?= $e($og_type ?? 'website') ?>">
<meta property="og:title" content="<?= $e($title ?? '코트맵') ?>">
<meta property="og:description" content="<?= $e($desc) ?>">
<meta property="og:url" content="<?= $e($canonical) ?>">
<meta property="og:image" content="<?= $e($ogImage) ?>">
<meta property="og:locale" content="ko_KR">
<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $e($title ?? '코트맵') ?>">
<meta name="twitter:description" content="<?= $e($desc) ?>">
<meta name="twitter:image" content="<?= $e($ogImage) ?>">
<?php if ($jsonld): ?>
<script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>
<link rel="stylesheet" href="/assets/css/tokens.css">
<style>
  /* PC 배경 #f9f9f9 + 스크롤바 자리 고정 (가로 폭 변동 방지) */
  html, body {
    margin: 0;
    background: #f9f9f9;
    overflow-x: hidden;
    scrollbar-gutter: stable;
  }
  /* #root 자체가 모바일 셸 — 사이즈 고정 (mount 전후 동일) + PC 경계감(보더+그림자) */
  #root {
    width: 100%;
    max-width: 480px;
    height: 100dvh;
    margin: 0 auto;
    background: var(--bg);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 0 0 1px var(--line-strong), 0 20px 60px rgba(15,19,32,.10), 0 4px 12px rgba(15,19,32,.05);
  }
  /* 모바일 (≤480px) — 전체화면, 그림자/보더 제거 */
  @media (max-width: 480px) {
    html, body { background: var(--bg); }
    #root { box-shadow: none; }
  }
  .stage { min-height: 100dvh; }
  /* React 가 그리는 .mobile 은 #root 를 채움 (탭바는 자연스럽게 viewport 하단에 고정) */
  .mobile {
    width: 100% !important;
    max-width: none !important;
    height: 100% !important;
    min-height: 0 !important;
    flex: 1;
    border-radius: 0 !important;
    box-shadow: none !important;
    background: transparent !important;
  }
  /* 가짜 아이폰 상태바 / 홈 인디케이터 제거 */
  .status-bar { display: none !important; }
  .tabbar::after { display: none !important; }
  /* 탭바 — viewport 하단 고정 (#root 100dvh + flex column) */
  .tabbar {
    flex-shrink: 0 !important;
    padding: 8px 4px calc(8px + env(safe-area-inset-bottom)) !important;
    box-shadow: 0 -1px 6px rgba(15,19,32,.05);
    border-top: 1px solid var(--line-strong);
  }
  /* .app-body 가 스크롤 영역 — 콘텐츠 길어도 탭바는 그대로 */
  .app-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow-y: auto !important;
  }
</style>
</head>
<body>

<script>
window.__DATA__   = <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__SCREEN__ = <?= json_encode($screen, JSON_UNESCAPED_UNICODE) ?>;
window.__USER__   = <?= json_encode(\App\Core\Auth::user(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__CSRF__   = <?= json_encode(\App\Core\Csrf::token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<div class="stage">
  <div id="root">
    <noscript>
      <main style="padding:40px 24px;font-family:sans-serif;max-width:480px;margin:0 auto">
        <h1 style="font-size:28px;margin:0 0 12px;color:#1e50ff"><?= $e($title ?? '코트맵') ?></h1>
        <p style="color:#444;line-height:1.7"><?= $e($desc) ?></p>
        <p><a href="/venues">구장 목록 보기</a></p>
      </main>
    </noscript>
  </div>
</div>

<!-- Vite 빌드 산출물 (npm run build) -->
<script src="/build/app.js" defer></script>

</body>
</html>
