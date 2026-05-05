<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venues */
/** @var int $venueId */
/** @var array $courts */
/** @var array $hotDeals */
/** @var array $autoRules */
$kdays = ['일','월','화','수','목','금','토'];

function dow_mask_label(int $mask, array $kdays): string {
    if ($mask === 127) return '매일';
    $names = [];
    for ($i = 0; $i < 7; $i++) if ($mask & (1 << $i)) $names[] = $kdays[$i];
    return implode('·', $names);
}
?>
<header class="op-page-head">
  <h1>다이나믹 프라이싱</h1>
  <p class="op-sub">임박한 빈 코트에 즉시 할인을 발행하거나, 자동 룰로 시스템이 발행하게 둡니다.</p>
</header>

<form method="get" class="op-filter-row" style="margin-bottom:16px">
  <select name="venue_id" onchange="this.form.submit()">
    <?php foreach ($venues as $v): ?>
      <option value="<?= (int)$v['id'] ?>" <?= $venueId === (int)$v['id'] ? 'selected' : '' ?>><?= $e($v['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>활성 핫딜 <span class="op-pill"><?= count($hotDeals) ?></span></h2></div>
  <?php if (!$hotDeals): ?>
    <div class="op-empty">활성 핫딜이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>날짜</th><th>시간</th><th>코트</th><th>할인</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($hotDeals as $d): ?>
          <tr>
            <td><?= $e($d['target_date']) ?></td>
            <td class="num"><?= sprintf('%02d:00 ~ %02d:00', (int)$d['target_start_hour'], (int)$d['target_end_hour']) ?></td>
            <td><?= $d['court_name'] ? $e($d['court_name']) : '<span class="op-mute">전체</span>' ?></td>
            <td><span class="badge badge-hot"><?= (int)$d['discount_pct'] ?>%</span></td>
            <td>
              <form method="post" action="/operator/pricing/<?= (int)$d['id'] ?>/cancel" onsubmit="return confirm('이 핫딜을 취소할까요?');" style="display:inline">
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
  <div class="op-card-head"><h2>핫딜 즉시 발행</h2></div>
  <form method="post" action="/operator/pricing/hot-deal" class="op-form">
    <input type="hidden" name="venue_id" value="<?= (int)$venueId ?>">
    <div class="op-form-row">
      <label>날짜<input type="date" name="target_date" required></label>
      <label>코트
        <select name="court_id">
          <option value="">전체 코트</option>
          <?php foreach ($courts as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>시작 시각<input type="number" name="target_start_hour" min="0" max="23" value="19" required></label>
      <label>종료 시각<input type="number" name="target_end_hour" min="1" max="24" value="22" required></label>
      <label>할인율 (%)<input type="number" name="discount_pct" min="1" max="99" value="30" required></label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">핫딜 발행</button>
  </form>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>자동 룰 <span class="op-pill"><?= count($autoRules) ?></span></h2></div>
  <?php if (!$autoRules): ?>
    <div class="op-empty">자동 룰이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>트리거</th><th>적용 시간대 / 요일</th><th>할인</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($autoRules as $r): ?>
          <tr>
            <td class="fw-600"><?= $e($r['name']) ?></td>
            <td><?= (int)$r['trigger_hours_before'] ?>시간 전 빈 코트</td>
            <td>
              <?= sprintf('%02d:00 ~ %02d:00', (int)$r['apply_from_hour'], (int)$r['apply_to_hour']) ?>
              <div class="op-mute"><?= $e(dow_mask_label((int)$r['dow_mask'], $kdays)) ?></div>
            </td>
            <td><span class="badge badge-hot"><?= (int)$r['discount_pct'] ?>%</span></td>
            <td><span class="badge <?= $r['status']==='active'?'badge-success':'badge-gray' ?>"><?= $e($r['status']==='active'?'동작 중':'일시정지') ?></span></td>
            <td>
              <form method="post" action="/operator/pricing/auto-rules/<?= (int)$r['id'] ?>/toggle" style="display:inline">
                <button type="submit" class="btn btn-line btn-sm"><?= $r['status']==='active'?'일시정지':'재개' ?></button>
              </form>
              <form method="post" action="/operator/pricing/auto-rules/<?= (int)$r['id'] ?>/delete" onsubmit="return confirm('삭제할까요?');" style="display:inline">
                <button type="submit" class="btn btn-line btn-sm">삭제</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>자동 룰 추가</h2></div>
  <form method="post" action="/operator/pricing/auto-rules" class="op-form">
    <input type="hidden" name="venue_id" value="<?= (int)$venueId ?>">
    <div class="op-form-row">
      <label>이름<input type="text" name="name" placeholder="평일 임박 자동할인" required></label>
      <label>몇 시간 전부터 적용 (1~6)<input type="number" name="trigger_hours_before" min="1" max="6" value="2" required></label>
      <label>할인율 (%)<input type="number" name="discount_pct" min="1" max="99" value="20" required></label>
      <label>적용 시작 시각<input type="number" name="apply_from_hour" min="0" max="23" value="18"></label>
      <label>적용 종료 시각<input type="number" name="apply_to_hour" min="1" max="24" value="22"></label>
    </div>
    <fieldset class="op-checks">
      <legend>적용 요일</legend>
      <?php for ($i = 0; $i < 7; $i++): ?>
        <label class="op-check"><input type="checkbox" name="day_of_week[]" value="<?= $i ?>" checked> <?= $e($kdays[$i]) ?></label>
      <?php endfor; ?>
    </fieldset>
    <button type="submit" class="btn btn-primary btn-md">자동 룰 추가</button>
  </form>
</section>
