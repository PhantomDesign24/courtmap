<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venue */
/** @var array $courts */
/** @var array $hours */
/** @var array $allTags */
/** @var array $venueTagIds */
$kdays = ['일','월','화','수','목','금','토'];
$hoursByDow = [];
foreach ($hours as $h) $hoursByDow[(int)$h['day_of_week']] = $h;
?>
<header class="op-page-head">
  <h1><?= $e($venue['name']) ?> 편집</h1>
  <p class="op-sub">기본 정보·운영시간·환불정책·계좌·시설태그·코트.</p>
</header>

<form method="post" action="/operator/venues/<?= (int)$venue['id'] ?>" class="op-form">

<section class="op-card">
  <div class="op-card-head"><h2>기본 정보</h2></div>
  <div class="op-form-row">
    <label>이름<input type="text" name="name" required value="<?= $e($venue['name']) ?>"></label>
    <label>지역(검색용)<input type="text" name="area" required value="<?= $e($venue['area']) ?>"></label>
    <label>전화<input type="text" name="phone" required value="<?= $e($venue['phone']) ?>"></label>
    <label>시간당 가격<input type="number" name="price_per_hour" min="0" required value="<?= (int)$venue['price_per_hour'] ?>"></label>
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
  <div class="op-card-head"><h2>입금 계좌</h2></div>
  <div class="op-form-row">
    <label>은행<input type="text" name="bank_name" required value="<?= $e($venue['bank_name']) ?>"></label>
    <label style="flex:2">계좌번호<input type="text" name="bank_account" required value="<?= $e($venue['bank_account']) ?>"></label>
    <label>예금주<input type="text" name="bank_holder" required value="<?= $e($venue['bank_holder']) ?>"></label>
    <label>입금 기한(시간)<input type="number" name="deposit_due_hours" min="1" max="168" required value="<?= (int)$venue['deposit_due_hours'] ?>"></label>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>환불 정책 (%)</h2></div>
  <div class="op-form-row">
    <label>이용 24시간 전<input type="number" name="refund_24h_pct" min="0" max="100" required value="<?= (int)$venue['refund_24h_pct'] ?>"></label>
    <label>이용 1시간 전<input type="number" name="refund_1h_pct"  min="0" max="100" required value="<?= (int)$venue['refund_1h_pct'] ?>"></label>
    <label>이용 1시간 이내<input type="number" name="refund_lt1h_pct" min="0" max="100" required value="<?= (int)$venue['refund_lt1h_pct'] ?>"></label>
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
  <a href="/operator/venues" class="btn btn-line btn-md">목록으로</a>
</div>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>코트 <span class="op-pill"><?= count(array_filter($courts, fn($c) => $c['status']==='active')) ?></span></h2></div>
  <?php if (!$courts): ?>
    <div class="op-empty">코트가 없습니다.</div>
  <?php else: ?>
    <?php foreach ($courts as $c): ?>
      <form method="post" action="/operator/venues/<?= (int)$venue['id'] ?>/courts/<?= (int)$c['id'] ?>/update" id="ec_f_<?= (int)$c['id'] ?>"></form>
    <?php endforeach; ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>가격(오버라이드)</th><th>정렬</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($courts as $c): $fid = 'ec_f_' . (int)$c['id']; ?>
          <tr style="<?= $c['status']!=='active' ? 'opacity:0.5' : '' ?>">
            <td><input form="<?= $fid ?>" type="text" name="name" value="<?= $e($c['name']) ?>" required style="width:120px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num"><input form="<?= $fid ?>" type="number" name="price_override" value="<?= $c['price_override']!==null?(int)$c['price_override']:'' ?>" placeholder="기본" min="0" style="width:100px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num"><input form="<?= $fid ?>" type="number" name="sort_order" value="<?= (int)$c['sort_order'] ?>" min="0" style="width:60px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td><span class="badge badge-gray"><?= $e($c['status']) ?></span></td>
            <td style="display:flex;gap:4px">
              <button form="<?= $fid ?>" type="submit" class="btn btn-primary btn-sm">저장</button>
              <?php if ($c['status'] === 'active'): ?>
                <form method="post" action="/operator/venues/<?= (int)$venue['id'] ?>/courts/<?= (int)$c['id'] ?>/delete" onsubmit="return confirm('이 코트를 닫을까요?');" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm">닫기</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  <form method="post" action="/operator/venues/<?= (int)$venue['id'] ?>/courts/add" style="padding:14px 18px;border-top:1px solid var(--line);display:flex;gap:8px;align-items:center">
    <input type="text" name="name" placeholder="E코트" required style="height:36px;padding:0 12px;border:1px solid var(--line-strong);border-radius:8px;font-size:13px;font-family:inherit">
    <button type="submit" class="btn btn-primary btn-sm">코트 추가</button>
  </form>
</section>
