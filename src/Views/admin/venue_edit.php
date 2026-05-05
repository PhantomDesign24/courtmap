<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venue */
/** @var array $owner */
/** @var array $courts */
/** @var array $hours */
/** @var array $allTags */
/** @var array $venueTagIds */
$kdays = ['일','월','화','수','목','금','토'];
$hoursByDow = [];
foreach ($hours as $h) $hoursByDow[(int)$h['day_of_week']] = $h;
$flashOk  = $_SESSION['flash_ok']  ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);
$venueId = (int)$venue['id'];
$st = [
  'pending'   => '승인 대기',
  'active'    => '활성',
  'suspended' => '정지',
  'closed'    => '폐쇄',
];
?>
<header class="op-page-head" style="display:flex;align-items:center;justify-content:space-between">
  <div>
    <h1><?= $e($venue['name']) ?> <span style="font-size:14px;font-weight:500;color:var(--text-sub)">— 어드민 편집</span></h1>
    <p class="op-sub">관리자 권한으로 모든 항목을 직접 수정합니다.</p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="/admin/venues/<?= $venueId ?>" class="btn btn-line btn-md">상세</a>
    <a href="/admin/venues" class="btn btn-line btn-md">← 목록</a>
  </div>
</header>

<?php if ($flashOk): ?>
  <div class="op-card" style="padding:12px 16px;color:#0a7e4a;background:#dcf6e8;border-color:#bbe7d0">✓ <?= $e($flashOk) ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="op-card" style="padding:12px 16px;color:#c0392b;background:#fff5f4;border-color:#ffd2cc"><?= $e($flashErr) ?></div>
<?php endif; ?>

<form method="post" action="/admin/venues/<?= $venueId ?>/update" class="op-form">

<section class="op-card">
  <div class="op-card-head"><h2>기본 정보</h2></div>
  <div class="op-form-row">
    <label>이름<input type="text" name="name" required value="<?= $e($venue['name']) ?>"></label>
    <label>지역(검색용)<input type="text" name="area" required value="<?= $e($venue['area']) ?>"></label>
    <label>전화<input type="text" name="phone" required value="<?= $e($venue['phone']) ?>"></label>
    <label>시간당 가격<input type="number" name="price_per_hour" min="0" required value="<?= (int)$venue['price_per_hour'] ?>"></label>
    <label>상태
      <select name="status">
        <?php foreach ($st as $k => $v): ?>
          <option value="<?= $e($k) ?>" <?= $venue['status'] === $k ? 'selected' : '' ?>><?= $e($v) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div class="op-form-row">
    <label style="flex:2">주소<input type="text" name="address" required value="<?= $e($venue['address']) ?>"></label>
    <label>위도<input type="number" name="lat" step="0.0000001" required value="<?= $e($venue['lat']) ?>"></label>
    <label>경도<input type="number" name="lng" step="0.0000001" required value="<?= $e($venue['lng']) ?>"></label>
  </div>
  <div class="op-form-row">
    <label style="flex:1">소개<textarea name="description" rows="2"><?= $e($venue['description'] ?? '') ?></textarea></label>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>운영시간</h2></div>
  <div class="op-hours">
    <?php for ($d = 0; $d < 7; $d++):
      $h = $hoursByDow[$d] ?? ['open_time'=>'10:00:00','close_time'=>'23:59:59','is_closed'=>0];
    ?>
      <div class="op-hours-row">
        <span class="op-hours-day"><?= $e($kdays[$d]) ?></span>
        <input type="time" name="open_<?= $d ?>"  value="<?= $e(substr($h['open_time'], 0, 5)) ?>">
        <span>~</span>
        <input type="time" name="close_<?= $d ?>" value="<?= $e(substr($h['close_time'], 0, 5)) ?>">
        <label class="op-check"><input type="checkbox" name="closed_<?= $d ?>" value="1" <?= !empty($h['is_closed']) ? 'checked' : '' ?>> 휴무</label>
      </div>
    <?php endfor; ?>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>입금 계좌 / 환불 정책</h2></div>
  <div class="op-form-row">
    <label>은행<input type="text" name="bank_name" required value="<?= $e($venue['bank_name']) ?>"></label>
    <label style="flex:2">계좌번호<input type="text" name="bank_account" required value="<?= $e($venue['bank_account']) ?>"></label>
    <label>예금주<input type="text" name="bank_holder" required value="<?= $e($venue['bank_holder']) ?>"></label>
    <label>입금 기한(시간)<input type="number" name="deposit_due_hours" min="1" max="168" required value="<?= (int)$venue['deposit_due_hours'] ?>"></label>
  </div>
  <div class="op-form-row">
    <label>이용 24시간 전(%)<input type="number" name="refund_24h_pct" min="0" max="100" required value="<?= (int)$venue['refund_24h_pct'] ?>"></label>
    <label>이용 1시간 전(%)<input type="number" name="refund_1h_pct" min="0" max="100" required value="<?= (int)$venue['refund_1h_pct'] ?>"></label>
    <label>이용 1시간 이내(%)<input type="number" name="refund_lt1h_pct" min="0" max="100" required value="<?= (int)$venue['refund_lt1h_pct'] ?>"></label>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>시설 태그</h2></div>
  <fieldset class="op-checks">
    <?php foreach ($allTags as $t): ?>
      <label class="op-check">
        <input type="checkbox" name="tags[]" value="<?= (int)$t['id'] ?>" <?= in_array((int)$t['id'], $venueTagIds, true) ? 'checked' : '' ?>>
        <?= $e($t['name']) ?>
      </label>
    <?php endforeach; ?>
  </fieldset>
</section>

<div style="display:flex;gap:8px;margin-bottom:24px">
  <button type="submit" class="btn btn-primary btn-md">저장</button>
  <a href="/admin/venues" class="btn btn-line btn-md">취소</a>
</div>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>코트 <span class="op-pill"><?= count(array_filter($courts, fn($c) => $c['status']==='active')) ?></span></h2></div>
  <table class="op-table">
    <thead><tr><th>이름</th><th>가격(오버라이드)</th><th>정렬</th><th>상태</th></tr></thead>
    <tbody>
      <?php foreach ($courts as $c): ?>
        <tr style="<?= $c['status']!=='active'?'opacity:0.5':'' ?>">
          <td class="fw-600"><?= $e($c['name']) ?></td>
          <td class="num"><?= $c['price_override'] ? number_format((int)$c['price_override']) . '원' : '<span class="op-mute">기본</span>' ?></td>
          <td class="num"><?= (int)$c['sort_order'] ?></td>
          <td><span class="badge badge-gray"><?= $e($c['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div style="padding:14px 18px;border-top:1px solid var(--line)">
    <span class="op-mute" style="font-size:12px">코트 추가/편집은 운영자 페이지에서 가능합니다.</span>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>운영자 변경</h2></div>
  <div style="padding:14px 18px;font-size:13px">
    <div>현재 운영자: <span class="fw-600"><?= $e($owner['name'] ?? '-') ?></span> · <?= $e($owner['email'] ?? '-') ?> (#<?= (int)($owner['id'] ?? 0) ?>)</div>
  </div>
  <form method="post" action="/admin/venues/<?= $venueId ?>/owner" class="op-form" onsubmit="return confirm('운영자를 변경할까요? 일반 회원이면 자동으로 운영자 권한이 부여됩니다.');">
    <div class="op-form-row">
      <label style="flex:1">새 운영자 user ID<input type="number" name="owner_id" required min="1" placeholder="예: 12 — /admin/users 에서 확인"></label>
      <button type="submit" class="btn btn-line btn-md" style="align-self:flex-end">변경</button>
    </div>
  </form>
</section>

<section class="op-card" style="border-color:#ffd2cc">
  <div class="op-card-head" style="border-bottom-color:#ffd2cc"><h2 style="color:#c0392b">위험 영역</h2></div>
  <div style="padding:14px 18px">
    <form method="post" action="/admin/venues/<?= $venueId ?>/force-suspend" onsubmit="return confirm('이 구장을 정지할까요?');" style="display:flex;gap:8px;align-items:center;margin-bottom:14px;flex-wrap:wrap">
      <input type="text" name="reason" placeholder="정지 사유(운영자에게 알림 발송)" style="flex:1;min-width:240px;height:34px;padding:0 12px;border:1px solid var(--line-strong);border-radius:8px">
      <button type="submit" class="btn btn-line btn-sm" style="border-color:#ffd2cc;color:#c0392b">강제 정지</button>
    </form>
    <form method="post" action="/admin/venues/<?= $venueId ?>/soft-delete" onsubmit="return confirm('정말 폐쇄할까요? 모든 코트가 닫히고 신규 예약이 불가합니다.');" style="display:inline">
      <button type="submit" class="btn btn-line btn-sm" style="border-color:#ffd2cc;color:#c0392b">구장 폐쇄</button>
    </form>
  </div>
</section>
