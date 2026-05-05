<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $bookings */
/** @var string $range */
/** @var string $status */
/** @var string $q */

$status_label = [
    'pending'   => ['입금 대기', 'badge-warn'],
    'confirmed' => ['확정',     'badge-success'],
    'done'      => ['완료',     'badge-gray'],
    'noshow'    => ['노쇼',     'badge-hot'],
    'canceled'  => ['취소',     'badge-gray'],
    'expired'   => ['기한초과',  'badge-gray'],
];
function fmt_won(int $n): string { return number_format($n) . '원'; }
?>
<header class="op-page-head">
  <h1>예약 관리</h1>
  <p class="op-sub">사용자가 도착하면 입장 체크, 안 오면 노쇼 신고. 시작 후 10분 자동 처리도 함께 동작합니다.</p>
</header>

<form method="get" class="op-filter">
  <div class="op-tabs">
    <?php foreach (['today'=>'오늘','week'=>'이번 주','past'=>'지난 예약','all'=>'전체'] as $k => $v): ?>
      <a href="?range=<?= $e($k) ?>" class="<?= $range === $k ? 'active' : '' ?>"><?= $e($v) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="op-filter-row">
    <input type="hidden" name="range" value="<?= $e($range) ?>">
    <select name="status" onchange="this.form.submit()">
      <option value="">전체 상태</option>
      <?php foreach ($status_label as $k => [$l, $_]): ?>
        <option value="<?= $e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= $e($l) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="search" name="q" value="<?= $e($q) ?>" placeholder="이름·전화·예약번호">
    <button type="submit" class="btn btn-line btn-sm">검색</button>
  </div>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>예약 목록 <span class="op-pill"><?= count($bookings) ?></span></h2></div>
  <?php if (!$bookings): ?>
    <div class="op-empty">조건에 맞는 예약이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead>
        <tr>
          <th>일시</th><th>예약자</th><th>구장 / 코트</th><th>금액</th><th>상태</th><th class="op-th-actions">처리</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $r):
          [$lbl, $cls] = $status_label[$r['status']] ?? ['—', 'badge-gray'];
        ?>
          <tr>
            <td>
              <?= $e($r['reservation_date']) ?>
              <div class="op-mute"><?= sprintf('%02d:00 ~ %02d:00', (int)$r['start_hour'], (int)$r['start_hour'] + (int)$r['duration_hours']) ?></div>
            </td>
            <td>
              <div class="fw-600"><?= $e($r['user_name']) ?></div>
              <div class="op-mute"><?= $e($r['user_phone']) ?> · 점수 <?= (int) $r['trust_score'] ?></div>
              <div class="op-mute num">#<?= $e($r['code']) ?></div>
            </td>
            <td>
              <?= $e($r['venue_name']) ?>
              <div class="op-mute"><?= $e($r['court_name']) ?></div>
            </td>
            <td class="num fw-600"><?= $e(fmt_won((int) $r['total_price'])) ?></td>
            <td><span class="badge <?= $e($cls) ?>"><?= $e($lbl) ?></span>
              <?php if ($r['entered_at']): ?>
                <div class="op-mute" style="margin-top:4px">입장 <?= $e(substr((string)$r['entered_at'], 11, 5)) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['status'] === 'confirmed' && !$r['entered_at']): ?>
                <form method="post" action="/operator/bookings/<?= $e($r['code']) ?>/check-in?from=<?= $e($range) ?>" style="display:inline">
                  <button type="submit" class="btn btn-primary btn-sm">입장</button>
                </form>
                <form method="post" action="/operator/bookings/<?= $e($r['code']) ?>/noshow?from=<?= $e($range) ?>" style="display:inline" onsubmit="return confirm('이 예약을 노쇼 처리할까요? 신뢰점수 -15');">
                  <button type="submit" class="btn btn-line btn-sm">노쇼</button>
                </form>
              <?php elseif (in_array($r['status'], ['pending','confirmed'], true)): ?>
                <form method="post" action="/operator/bookings/<?= $e($r['code']) ?>/cancel?from=<?= $e($range) ?>" style="display:inline" onsubmit="return confirm('취소할까요?');">
                  <button type="submit" class="btn btn-line btn-sm">취소</button>
                </form>
              <?php else: ?>
                <span class="op-mute">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
