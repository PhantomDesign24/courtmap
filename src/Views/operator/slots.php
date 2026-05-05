<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venues */
/** @var int $venueId */
/** @var array $rules */
$kdays = ['일','월','화','수','목','금','토'];
$type_label = ['default'=>'기본','dow'=>'요일','holiday'=>'공휴일','specific_date'=>'특정 날짜'];
?>
<header class="op-page-head">
  <h1>슬롯 규칙</h1>
  <p class="op-sub">날짜·요일별로 예약 단위(1/2/3시간) 를 다르게 설정합니다. 우선순위: 특정날짜 &gt; 공휴일 &gt; 요일 &gt; 기본.</p>
</header>

<form method="get" class="op-filter-row" style="margin-bottom:16px">
  <select name="venue_id" onchange="this.form.submit()">
    <?php foreach ($venues as $v): ?>
      <option value="<?= (int)$v['id'] ?>" <?= $venueId === (int)$v['id'] ? 'selected' : '' ?>><?= $e($v['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>현재 규칙 <span class="op-pill"><?= count($rules) ?></span></h2></div>
  <?php if (!$rules): ?>
    <div class="op-empty">규칙이 없습니다. 아래에서 추가하세요.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>유형</th><th>대상</th><th>슬롯 단위</th><th>비고</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($rules as $r): ?>
          <tr>
            <td><span class="badge badge-soft"><?= $e($type_label[$r['rule_type']] ?? $r['rule_type']) ?></span></td>
            <td>
              <?= match ($r['rule_type']) {
                'dow'           => $e($kdays[(int)$r['day_of_week']] . '요일'),
                'specific_date' => $e($r['specific_date']),
                'holiday'       => '한국 공휴일 자동',
                default         => '항상 적용',
              } ?>
            </td>
            <td class="fw-700 num"><?= (int)$r['slot_unit_hours'] ?>시간</td>
            <td class="op-mute"><?= $e($r['note'] ?? '') ?></td>
            <td>
              <form method="post" action="/operator/slots/<?= (int)$r['id'] ?>/delete" onsubmit="return confirm('이 규칙을 삭제할까요?');" style="display:inline">
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
  <div class="op-card-head"><h2>규칙 추가</h2></div>
  <form method="post" action="/operator/slots/add" class="op-form">
    <input type="hidden" name="venue_id" value="<?= (int)$venueId ?>">
    <div class="op-form-row">
      <label>유형
        <select name="rule_type" id="rule_type" onchange="updateRuleFields()">
          <option value="default">기본 (모든 날 적용)</option>
          <option value="dow">요일 (월~일 중 하나)</option>
          <option value="holiday">공휴일 (한국 공휴일 자동)</option>
          <option value="specific_date">특정 날짜</option>
        </select>
      </label>
      <label id="dow_field" style="display:none">요일
        <select name="day_of_week">
          <?php for ($i = 0; $i < 7; $i++): ?>
            <option value="<?= $i ?>"><?= $e($kdays[$i]) ?></option>
          <?php endfor; ?>
        </select>
      </label>
      <label id="date_field" style="display:none">날짜
        <input type="date" name="specific_date">
      </label>
      <label>슬롯 단위
        <select name="slot_unit_hours">
          <option value="1">1시간</option>
          <option value="2">2시간</option>
          <option value="3">3시간</option>
        </select>
      </label>
      <label>비고 (선택)
        <input type="text" name="note" maxlength="120">
      </label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">규칙 추가</button>
  </form>
</section>

<script>
function updateRuleFields(){
  const t = document.getElementById('rule_type').value;
  document.getElementById('dow_field').style.display  = (t === 'dow') ? '' : 'none';
  document.getElementById('date_field').style.display = (t === 'specific_date') ? '' : 'none';
}
updateRuleFields();
</script>
