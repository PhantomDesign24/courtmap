<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $stats */
function fmt_won(int $n): string { return number_format($n) . '원'; }
?>
<header class="op-page-head">
  <h1>어드민 대시보드</h1>
  <p class="op-sub">코트맵 전체 운영 현황.</p>
</header>

<div class="op-kpi-grid">
  <a href="/admin/venues?tab=pending" class="op-kpi">
    <div class="op-kpi-label">구장 승인 대기</div>
    <div class="op-kpi-value"><?= (int)$stats['pending_venues'] ?><span>건</span></div>
    <div class="op-kpi-sub">검토하러 가기 →</div>
  </a>
  <div class="op-kpi">
    <div class="op-kpi-label">활성 구장</div>
    <div class="op-kpi-value"><?= (int)$stats['active_venues'] ?><span>곳</span></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">활성 사용자</div>
    <div class="op-kpi-value"><?= (int)$stats['active_users'] ?><span>명</span></div>
    <div class="op-kpi-sub">최근 7일 신규 +<?= (int)$stats['new_users_week'] ?></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">활성 운영자</div>
    <div class="op-kpi-value"><?= (int)$stats['active_operators'] ?><span>명</span></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">오늘 예약</div>
    <div class="op-kpi-value"><?= (int)$stats['today_reservations'] ?><span>건</span></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">오늘 매출 (입금 확정 기준)</div>
    <div class="op-kpi-value num"><?= $e(fmt_won((int)$stats['today_revenue'])) ?></div>
  </div>
  <a href="/admin/reports" class="op-kpi">
    <div class="op-kpi-label">최근 7일 노쇼</div>
    <div class="op-kpi-value"><?= (int)$stats['noshow_week'] ?><span>건</span></div>
    <div class="op-kpi-sub">로그 보러 가기 →</div>
  </a>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>안내</h2></div>
  <div style="padding:24px;color:var(--text-sub);font-size:13px;line-height:1.7">
    좌측 메뉴에서 항목을 선택하세요. 신규 구장은 <a href="/admin/venues?tab=pending" class="text-brand fw-700">구장 승인</a>, 사용자 점수 조정은 <a href="/admin/users" class="text-brand fw-700">사용자 관리</a> 에서.
  </div>
</section>
