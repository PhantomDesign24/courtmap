<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venue */
/** @var array $courts */
/** @var array $hours */
/** @var array $tags */
/** @var array $stats */
/** @var array $recent */
$kdays = ['일','월','화','수','목','금','토'];
$st = [
  'pending'  => ['승인 대기', 'badge-warn'],
  'active'   => ['활성',     'badge-success'],
  'suspended'=> ['정지',     'badge-hot'],
  'closed'   => ['폐쇄',     'badge-gray'],
];
function won(int $n): string { return number_format($n) . '원'; }
?>
<header class="op-page-head" style="display:flex;align-items:center;justify-content:space-between">
  <div>
    <h1><?= $e($venue['name']) ?></h1>
    <p class="op-sub"><?= $e($venue['area']) ?> · <?= $e($venue['address']) ?></p>
  </div>
  <a href="/admin/venues" class="btn btn-line btn-md">← 목록</a>
</header>

<div class="op-kpi-grid">
  <div class="op-kpi"><div class="op-kpi-label">총 예약</div><div class="op-kpi-value"><?= (int)$stats['total_res'] ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">확정 예약</div><div class="op-kpi-value"><?= (int)$stats['confirmed_res'] ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">노쇼</div><div class="op-kpi-value"><?= (int)$stats['noshow_res'] ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">누적 매출</div><div class="op-kpi-value num"><?= $e(won((int)$stats['revenue'])) ?></div></div>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>기본 정보</h2></div>
  <div style="padding:16px 18px;display:grid;grid-template-columns:120px 1fr;row-gap:8px;font-size:13.5px">
    <span class="text-sub">상태</span>
    <span><span class="badge <?= $e($st[$venue['status']][1] ?? 'badge-gray') ?>"><?= $e($st[$venue['status']][0] ?? $venue['status']) ?></span></span>
    <span class="text-sub">운영자</span><span><?= $e($venue['owner_name']) ?> · <?= $e($venue['owner_email']) ?> · <?= $e($venue['owner_phone']) ?></span>
    <span class="text-sub">전화</span><span><?= $e($venue['phone']) ?></span>
    <span class="text-sub">시간당 가격</span><span class="num"><?= $e(won((int)$venue['price_per_hour'])) ?></span>
    <span class="text-sub">좌표</span><span class="num">(<?= $e($venue['lat']) ?>, <?= $e($venue['lng']) ?>)</span>
    <span class="text-sub">계좌</span><span><?= $e($venue['bank_name']) ?> <?= $e($venue['bank_account']) ?> · <?= $e($venue['bank_holder']) ?></span>
    <span class="text-sub">환불 정책</span><span>24h ≥ <?= (int)$venue['refund_24h_pct'] ?>% / 1h ≥ <?= (int)$venue['refund_1h_pct'] ?>% / 이내 <?= (int)$venue['refund_lt1h_pct'] ?>%</span>
    <span class="text-sub">입금 기한</span><span><?= (int)$venue['deposit_due_hours'] ?>시간</span>
    <span class="text-sub">평점</span><span><?= $e($venue['rating_avg']) ?> (<?= (int)$venue['review_count'] ?>건)</span>
    <span class="text-sub">생성</span><span class="op-mute"><?= $e($venue['created_at']) ?></span>
    <span class="text-sub">수정</span><span class="op-mute"><?= $e($venue['updated_at']) ?></span>
  </div>
  <div style="padding:0 18px 16px;display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($venue['status'] === 'pending'): ?>
      <form method="post" action="/admin/venues/<?= (int)$venue['id'] ?>/approve" style="display:inline">
        <button type="submit" class="btn btn-primary btn-sm">승인</button>
      </form>
      <form method="post" action="/admin/venues/<?= (int)$venue['id'] ?>/reject" style="display:inline" onsubmit="return confirm('반려할까요?');">
        <button type="submit" class="btn btn-line btn-sm">반려</button>
      </form>
    <?php elseif ($venue['status'] === 'suspended'): ?>
      <form method="post" action="/admin/venues/<?= (int)$venue['id'] ?>/reactivate" style="display:inline">
        <button type="submit" class="btn btn-line btn-sm">재활성화</button>
      </form>
    <?php endif; ?>
    <a href="/venues/<?= (int)$venue['id'] ?>" target="_blank" class="btn btn-line btn-sm">사용자 페이지 ↗</a>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>코트 <span class="op-pill"><?= count(array_filter($courts, fn($c) => $c['status']==='active')) ?></span></h2></div>
  <table class="op-table">
    <thead><tr><th>이름</th><th>가격 오버라이드</th><th>상태</th></tr></thead>
    <tbody>
      <?php foreach ($courts as $c): ?>
        <tr style="<?= $c['status']!=='active'?'opacity:0.5':'' ?>">
          <td class="fw-600"><?= $e($c['name']) ?></td>
          <td class="num"><?= $c['price_override'] ? won((int)$c['price_override']) : '<span class="op-mute">기본</span>' ?></td>
          <td><span class="badge badge-gray"><?= $e($c['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>운영시간 / 시설</h2></div>
  <div style="padding:16px 18px">
    <div class="row" style="gap:16px;margin-bottom:14px">
      <?php foreach ($hours as $h): ?>
        <div style="font-size:12.5px">
          <span class="text-sub" style="display:inline-block;width:30px"><?= $e($kdays[(int)$h['day_of_week']]) ?></span>
          <?= !empty($h['is_closed']) ? '<span class="text-sub">휴무</span>' : substr($h['open_time'],0,5) . ' ~ ' . (substr($h['close_time'],0,5) === '23:59' ? '24:00' : substr($h['close_time'],0,5)) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px">
      <?php foreach ($tags as $t): ?>
        <span class="badge badge-gray"><?= $e($t['name']) ?></span>
      <?php endforeach; ?>
      <?php if (!$tags): ?><span class="text-sub" style="font-size:12px">등록된 시설 정보 없음</span><?php endif; ?>
    </div>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>최근 예약 20건</h2></div>
  <?php if (!$recent): ?>
    <div class="op-empty">예약 기록 없음</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>예약번호</th><th>예약자</th><th>일시</th><th>코트</th><th>금액</th><th>상태</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td class="num"><?= $e($r['code']) ?></td>
            <td><?= $e($r['user_name']) ?></td>
            <td>
              <?= $e($r['reservation_date']) ?>
              <div class="op-mute"><?= sprintf('%02d:00 ~ %02d:00', (int)$r['start_hour'], (int)$r['start_hour'] + (int)$r['duration_hours']) ?></div>
            </td>
            <td><?= $e($r['court_name']) ?></td>
            <td class="num fw-600"><?= $e(won((int)$r['total_price'])) ?></td>
            <td><span class="badge badge-gray"><?= $e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
