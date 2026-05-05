<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $pending */
/** @var array $confirmed */

function fmt_won(int $n): string { return number_format($n) . '원'; }
function fmt_dt(?string $iso): string {
    if (!$iso) return '—';
    $t = strtotime($iso);
    return date('Y-m-d H:i', $t);
}
function fmt_remaining(string $iso): string {
    $diff = strtotime($iso) - time();
    if ($diff < 0)        return '<span class="text-hot fw-700">기한 초과</span>';
    if ($diff < 3600)     return '<span class="text-hot fw-700">' . floor($diff / 60) . '분 남음</span>';
    if ($diff < 86400)    return '<span class="fw-600">' . floor($diff / 3600) . '시간 남음</span>';
    return floor($diff / 86400) . '일 남음';
}
?>
<header class="op-page-head">
  <h1>입금 확인</h1>
  <p class="op-sub">사용자가 신청한 예약을 입금 확인 후 확정 처리하세요.</p>
</header>

<section class="op-card">
  <div class="op-card-head">
    <h2>입금 대기 <span class="op-pill"><?= count($pending) ?></span></h2>
  </div>
  <?php if (!$pending): ?>
    <div class="op-empty">입금 대기 중인 예약이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead>
        <tr>
          <th>예약번호</th>
          <th>예약자</th>
          <th>입금자명</th>
          <th>금액</th>
          <th>구장 / 코트</th>
          <th>이용 일시</th>
          <th>입금 기한</th>
          <th class="op-th-actions">처리</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pending as $r): ?>
          <tr>
            <td class="num"><?= $e($r['code']) ?></td>
            <td>
              <div><?= $e($r['user_name']) ?></div>
              <div class="op-mute"><?= $e($r['user_phone']) ?> · 점수 <?= (int) $r['trust_score'] ?></div>
            </td>
            <td class="fw-600"><?= $e($r['depositor_name']) ?></td>
            <td class="num fw-700"><?= $e(fmt_won((int) $r['total_price'])) ?></td>
            <td>
              <?= $e($r['venue_name']) ?>
              <div class="op-mute"><?= $e($r['court_name']) ?></div>
            </td>
            <td>
              <?= $e($r['reservation_date']) ?>
              <div class="op-mute"><?= sprintf('%02d:00 ~ %02d:00', (int) $r['start_hour'], (int) $r['start_hour'] + (int) $r['duration_hours']) ?></div>
            </td>
            <td>
              <?= $e(fmt_dt($r['deposit_due_at'])) ?>
              <div class="op-mute"><?= fmt_remaining($r['deposit_due_at']) /* HTML 허용 */ ?></div>
            </td>
            <td>
              <form method="post" action="/operator/deposits/<?= $e($r['code']) ?>/confirm" style="display:inline">
                <button type="submit" class="btn btn-primary btn-sm">확정</button>
              </form>
              <form method="post" action="/operator/deposits/<?= $e($r['code']) ?>/cancel" style="display:inline" onsubmit="return confirm('이 예약을 취소할까요?');">
                <button type="submit" class="btn btn-line btn-sm">취소</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head">
    <h2>최근 7일 확정</h2>
  </div>
  <?php if (!$confirmed): ?>
    <div class="op-empty">최근 확정된 예약이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead>
        <tr>
          <th>예약번호</th>
          <th>예약자</th>
          <th>금액</th>
          <th>구장 / 코트</th>
          <th>이용 일시</th>
          <th>확정 시각</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($confirmed as $r): ?>
          <tr>
            <td class="num"><?= $e($r['code']) ?></td>
            <td><?= $e($r['user_name']) ?></td>
            <td class="num fw-600"><?= $e(fmt_won((int) $r['total_price'])) ?></td>
            <td>
              <?= $e($r['venue_name']) ?>
              <div class="op-mute"><?= $e($r['court_name']) ?></div>
            </td>
            <td>
              <?= $e($r['reservation_date']) ?>
              <div class="op-mute"><?= sprintf('%02d:00 ~ %02d:00', (int) $r['start_hour'], (int) $r['start_hour'] + (int) $r['duration_hours']) ?></div>
            </td>
            <td><?= $e(fmt_dt($r['paid_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
