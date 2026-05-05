<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $stats */
function fmt_won(int $n): string { return number_format($n) . '원'; }
?>
<header class="op-page-head">
  <h1>대시보드</h1>
  <p class="op-sub">오늘 한눈에 — 입금 대기·확정·매출.</p>
</header>

<div class="op-kpi-grid">
  <a href="/operator/deposits" class="op-kpi">
    <div class="op-kpi-label">입금 대기</div>
    <div class="op-kpi-value"><?= (int) $stats['pending'] ?><span>건</span></div>
    <div class="op-kpi-sub">처리하러 가기 →</div>
  </a>
  <div class="op-kpi">
    <div class="op-kpi-label">오늘 확정</div>
    <div class="op-kpi-value"><?= (int) $stats['today_confirmed'] ?><span>건</span></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">오늘 매출</div>
    <div class="op-kpi-value num"><?= $e(fmt_won((int) $stats['today_revenue'])) ?></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">활성 구장</div>
    <div class="op-kpi-value"><?= (int) $stats['venue_count'] ?><span>곳</span></div>
  </div>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>운영 현황</h2></div>
  <div style="padding:24px;color:var(--text-sub);font-size:13px;line-height:1.7">
    좌측 메뉴에서 항목을 선택하세요. 입금 대기 처리는 <a href="/operator/deposits" class="text-brand fw-700">입금 확인</a> 페이지에서.
  </div>
</section>
