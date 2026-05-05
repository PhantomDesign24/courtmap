<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venues */
/** @var array $tokens */
/** @var array $webhooks */
/** @var ?string $newToken */
?>
<header class="op-page-head">
  <h1>API 연동 (S5)</h1>
  <p class="op-sub">외부 시스템 연동용 API 토큰 + 이벤트 Webhook + iCal 피드.</p>
</header>

<?php if ($newToken): ?>
  <div class="op-card" style="border-color: var(--brand-500); background: var(--brand-50);">
    <div style="padding: 16px 18px;">
      <div class="fw-700" style="margin-bottom: 8px;">새 토큰이 발급되었습니다</div>
      <div class="num" style="font-family: monospace; font-size: 13px; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid var(--line-strong); word-break: break-all;"><?= $e($newToken) ?></div>
      <div class="op-mute" style="margin-top: 8px;">⚠️ 이 토큰은 다시 표시되지 않습니다. 안전한 곳에 복사해두세요.</div>
    </div>
  </div>
<?php endif; ?>

<section class="op-card">
  <div class="op-card-head"><h2>API 토큰 <span class="op-pill"><?= count(array_filter($tokens, fn($t) => $t['status']==='active')) ?></span></h2></div>
  <?php if ($tokens): ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>구장</th><th>스코프</th><th>마지막 사용</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($tokens as $t): ?>
          <tr style="<?= $t['status']!=='active'?'opacity:0.5':'' ?>">
            <td class="fw-600"><?= $e($t['name']) ?></td>
            <td><?= $e($t['venue_name']) ?></td>
            <td class="op-mute"><?= $e($t['scopes']) ?></td>
            <td class="op-mute"><?= $t['last_used_at'] ? $e(substr((string)$t['last_used_at'], 0, 16)) : '—' ?></td>
            <td><span class="badge badge-gray"><?= $e($t['status']) ?></span></td>
            <td>
              <?php if ($t['status'] === 'active'): ?>
                <form method="post" action="/operator/api/tokens/<?= (int)$t['id'] ?>/revoke" onsubmit="return confirm('폐기할까요? 되돌릴 수 없습니다.');" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm">폐기</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="op-empty">발급된 토큰이 없습니다.</div>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>토큰 발급</h2></div>
  <form method="post" action="/operator/api/tokens" class="op-form">
    <div class="op-form-row">
      <label>구장
        <select name="venue_id" required>
          <?php foreach ($venues as $v): ?>
            <option value="<?= (int)$v['id'] ?>"><?= $e($v['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>이름<input type="text" name="name" required placeholder="POS 시스템 연동"></label>
      <label>스코프
        <select name="scopes">
          <option value="reservations:read">reservations:read</option>
          <option value="reservations:read,write">reservations:read,write</option>
        </select>
      </label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">발급</button>
  </form>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>Webhook <span class="op-pill"><?= count($webhooks) ?></span></h2></div>
  <?php if ($webhooks): ?>
    <table class="op-table">
      <thead><tr><th>이벤트</th><th>구장</th><th>URL</th><th>최근 성공/실패</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($webhooks as $w): ?>
          <tr>
            <td class="fw-600"><?= $e($w['event_type']) ?></td>
            <td><?= $e($w['venue_name']) ?></td>
            <td class="op-mute" style="font-family:monospace;font-size:11.5px"><?= $e($w['url']) ?></td>
            <td class="op-mute">
              <?= $w['last_success_at'] ? '✓ ' . substr((string)$w['last_success_at'], 0, 16) : '—' ?>
              <?php if ($w['failure_count'] > 0): ?>
                · <span class="text-hot">실패 <?= (int)$w['failure_count'] ?></span>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-gray"><?= $e($w['status']) ?></span></td>
            <td>
              <?php if ($w['status'] === 'failed'): ?>
                <form method="post" action="/operator/api/webhooks/<?= (int)$w['id'] ?>/reactivate" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm">재시작</button>
                </form>
              <?php endif; ?>
              <form method="post" action="/operator/api/webhooks/<?= (int)$w['id'] ?>/delete" onsubmit="return confirm('삭제할까요?');" style="display:inline">
                <button type="submit" class="btn btn-line btn-sm">삭제</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="op-empty">등록된 Webhook 이 없습니다.</div>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>Webhook 등록</h2></div>
  <form method="post" action="/operator/api/webhooks" class="op-form">
    <div class="op-form-row">
      <label>구장
        <select name="venue_id" required>
          <?php foreach ($venues as $v): ?>
            <option value="<?= (int)$v['id'] ?>"><?= $e($v['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>이벤트
        <select name="event_type">
          <option value="reservation.confirmed">reservation.confirmed</option>
          <option value="reservation.canceled">reservation.canceled</option>
          <option value="reservation.created">reservation.created</option>
        </select>
      </label>
      <label style="flex:2">호출할 URL<input type="url" name="url" required placeholder="https://yoursite.com/webhook"></label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">등록</button>
  </form>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>iCal 피드</h2></div>
  <div style="padding: 16px 18px; font-size: 13px; line-height: 1.7; color: var(--text-sub);">
    <?php
    // 토큰 표시는 운영자가 자기 구장에 한정
    $myVenuesWithTokens = \App\Core\Db::fetchAll('SELECT id, name, calendar_token FROM venues WHERE owner_id = ? AND status = "active"', [(int) $user['id']]);
    foreach ($myVenuesWithTokens as $v): ?>
      <div style="margin-bottom: 6px;">
        <strong style="color: var(--text);"><?= $e($v['name']) ?></strong>:
        <span style="font-family: monospace; font-size: 12px;">https://bad.mvc.kr/api/venues/<?= (int)$v['id'] ?>/calendar.ics?token=<?= $e($v['calendar_token']) ?></span>
      </div>
    <?php endforeach; ?>
    <div class="op-mute" style="margin-top: 8px;">Google Calendar / iPhone 캘린더에 URL 추가하면 확정 예약이 자동 동기화됩니다.</div>
  </div>
</section>
