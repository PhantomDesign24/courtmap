<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $cronStatus */
/** @var array $webhooks */
/** @var array $blockedLogins */
/** @var array $recentLogins */
/** @var int $dbMs */

function rel_time_log(?int $ts): string {
    if (!$ts) return '실행기록 없음';
    $diff = time() - $ts;
    if ($diff < 60) return '방금 전';
    if ($diff < 3600) return floor($diff / 60) . '분 전';
    if ($diff < 86400) return floor($diff / 3600) . '시간 전';
    return floor($diff / 86400) . '일 전';
}
?>
<header class="op-page-head">
  <h1>시스템 로그</h1>
  <p class="op-sub">크론 실행 / 웹훅 실패 / 로그인 차단.</p>
</header>

<div class="op-kpi-grid" style="margin-bottom:18px">
  <div class="op-kpi"><div class="op-kpi-label">DB 응답</div><div class="op-kpi-value num <?= $dbMs > 100 ? 'text-hot' : 'text-brand' ?>"><?= (int)$dbMs ?><span>ms</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">실패 웹훅</div><div class="op-kpi-value <?= count(array_filter($webhooks, fn($w) => $w['status']==='failed')) > 0 ? 'text-hot' : '' ?>"><?= count(array_filter($webhooks, fn($w) => $w['status']==='failed')) ?><span>개</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">차단된 로그인</div><div class="op-kpi-value"><?= count($blockedLogins) ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">PHP 버전</div><div class="op-kpi-value" style="font-size:18px"><?= PHP_VERSION ?></div></div>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>크론 마지막 실행</h2></div>
  <table class="op-table">
    <thead><tr><th>이름</th><th>경로</th><th>마지막 실행</th><th>상태</th></tr></thead>
    <tbody>
      <?php foreach ($cronStatus as $name => $ts):
        $stale = $ts && (time() - $ts) > 7200;
      ?>
        <tr>
          <td class="fw-600"><?= $e($name) ?></td>
          <td class="op-mute num"><?= $e($ts ? date('Y-m-d H:i:s', $ts) : '—') ?></td>
          <td><?= $e(rel_time_log($ts)) ?></td>
          <td><span class="badge <?= !$ts ? 'badge-warn' : ($stale ? 'badge-hot' : 'badge-success') ?>"><?= !$ts ? '미실행' : ($stale ? '지연' : '정상') ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>웹훅 상태 <span class="op-pill"><?= count($webhooks) ?></span></h2></div>
  <?php if (!$webhooks): ?>
    <div class="op-empty">등록된 웹훅이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>구장</th><th>이벤트</th><th>URL</th><th>실패횟수</th><th>마지막 실패</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($webhooks as $w): ?>
          <tr>
            <td class="fw-600"><?= $e($w['venue_name']) ?></td>
            <td><span class="badge badge-soft"><?= $e($w['event_type']) ?></span></td>
            <td class="op-mute" style="word-break:break-all;font-family:ui-monospace,monospace;font-size:11.5px"><?= $e($w['url']) ?></td>
            <td class="num <?= (int)$w['failure_count'] > 0 ? 'text-hot fw-700' : 'op-mute' ?>"><?= (int)$w['failure_count'] ?></td>
            <td class="op-mute"><?= $e($w['last_failure_at'] ? substr((string)$w['last_failure_at'], 5, 11) : '—') ?></td>
            <td><span class="badge <?= $w['status']==='active'?'badge-success':($w['status']==='failed'?'badge-hot':'badge-gray') ?>"><?= $e($w['status']) ?></span></td>
            <td>
              <?php if ($w['status'] !== 'active'): ?>
                <form method="post" action="/admin/logs/webhooks/<?= (int)$w['id'] ?>/reactivate" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm">재활성</button>
                </form>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>차단된 로그인 시도 <span class="op-pill"><?= count($blockedLogins) ?></span></h2></div>
  <?php if (!$blockedLogins): ?>
    <div class="op-empty">현재 차단된 로그인이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>키 (해시)</th><th>실패</th><th>차단 해제</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($blockedLogins as $l): ?>
          <tr>
            <td class="num" style="font-family:ui-monospace,monospace;font-size:11.5px"><?= $e(substr((string)$l['attempt_key'], 0, 16)) ?>…</td>
            <td class="num text-hot fw-700"><?= (int)$l['fail_count'] ?></td>
            <td class="op-mute"><?= $e(substr((string)$l['blocked_until'], 5, 14)) ?></td>
            <td>
              <form method="post" action="/admin/logs/login/<?= $e($l['attempt_key']) ?>/clear" style="display:inline" onsubmit="return confirm('차단을 해제할까요?');">
                <button type="submit" class="btn btn-line btn-sm">해제</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>최근 로그인 시도 50건</h2></div>
  <?php if (!$recentLogins): ?>
    <div class="op-empty">기록 없음</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>키</th><th>실패</th><th>마지막 시도</th><th>차단상태</th></tr></thead>
      <tbody>
        <?php foreach ($recentLogins as $l):
          $blocked = $l['blocked_until'] && strtotime((string)$l['blocked_until']) > time();
        ?>
          <tr>
            <td class="num" style="font-family:ui-monospace,monospace;font-size:11.5px"><?= $e(substr((string)$l['attempt_key'], 0, 16)) ?>…</td>
            <td class="num"><?= (int)$l['fail_count'] ?></td>
            <td class="op-mute"><?= $e(substr((string)$l['updated_at'], 5, 14)) ?></td>
            <td><span class="badge <?= $blocked ? 'badge-hot' : 'badge-gray' ?>"><?= $blocked ? '차단중' : '정상' ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
