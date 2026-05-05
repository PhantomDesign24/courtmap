<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venues */
/** @var int $venueId */
/** @var array $items */
$type_label = ['racket' => '라켓', 'shuttle' => '셔틀콕', 'other' => '기타'];
?>
<header class="op-page-head">
  <h1>장비 대여 옵션</h1>
  <p class="op-sub">사용자가 예약 시 선택할 수 있는 장비를 등록합니다.</p>
</header>

<form method="get" class="op-filter-row" style="margin-bottom:16px">
  <select name="venue_id" onchange="this.form.submit()">
    <?php foreach ($venues as $v): ?>
      <option value="<?= (int)$v['id'] ?>" <?= $venueId === (int)$v['id'] ? 'selected' : '' ?>><?= $e($v['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>옵션 <span class="op-pill"><?= count(array_filter($items, fn($i) => $i['status'] === 'active')) ?></span></h2></div>
  <?php if ($items): ?>
    <?php foreach ($items as $r): ?>
      <form method="post" action="/operator/equipment/<?= (int)$r['id'] ?>/update" id="eq_f_<?= (int)$r['id'] ?>"></form>
    <?php endforeach; ?>
    <table class="op-table">
      <thead><tr><th>종류</th><th>이름</th><th>설명</th><th>가격</th><th>최대수량</th><th>정렬</th><th>기본체크</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($items as $r): $fid = 'eq_f_' . (int)$r['id']; ?>
          <tr style="<?= $r['status']!=='active'?'opacity:0.5':'' ?>">
            <td><span class="badge badge-soft"><?= $e($type_label[$r['type']] ?? $r['type']) ?></span></td>
            <td><input form="<?= $fid ?>" type="text" name="name" value="<?= $e($r['name']) ?>" required style="width:140px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td><input form="<?= $fid ?>" type="text" name="description" value="<?= $e($r['description'] ?? '') ?>" style="width:140px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num"><input form="<?= $fid ?>" type="number" name="price" value="<?= (int)$r['price'] ?>" min="0" required style="width:90px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num"><input form="<?= $fid ?>" type="number" name="max_qty" value="<?= (int)$r['max_qty'] ?>" min="1" max="20" required style="width:60px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num"><input form="<?= $fid ?>" type="number" name="sort_order" value="<?= (int)($r['sort_order'] ?? 0) ?>" min="0" style="width:60px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td><input form="<?= $fid ?>" type="checkbox" name="default_check" value="1" <?= !empty($r['default_check'])?'checked':'' ?>></td>
            <td><span class="badge badge-gray"><?= $e($r['status']) ?></span></td>
            <td style="display:flex;gap:4px">
              <button form="<?= $fid ?>" type="submit" class="btn btn-primary btn-sm">저장</button>
              <?php if ($r['status'] === 'active'): ?>
                <form method="post" action="/operator/equipment/<?= (int)$r['id'] ?>/delete" onsubmit="return confirm('이 옵션을 비활성화할까요?');" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm">중지</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="op-empty">등록된 장비가 없습니다.</div>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>옵션 추가</h2></div>
  <form method="post" action="/operator/equipment/add" class="op-form">
    <input type="hidden" name="venue_id" value="<?= (int)$venueId ?>">
    <div class="op-form-row">
      <label>종류
        <select name="type">
          <option value="racket">라켓</option>
          <option value="shuttle">셔틀콕</option>
          <option value="other">기타</option>
        </select>
      </label>
      <label>이름<input type="text" name="name" required placeholder="라켓 1자루 (YONEX)"></label>
      <label>설명<input type="text" name="description" placeholder="12개입"></label>
      <label>가격<input type="number" name="price" min="0" required value="5000"></label>
      <label>최대 수량<input type="number" name="max_qty" min="1" max="20" value="1" required></label>
      <label class="op-check" style="align-self:flex-end;height:38px;padding:0 8px;border:1px solid var(--line);border-radius:8px">
        <input type="checkbox" name="default_check" value="1"> 기본 체크
      </label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">추가</button>
  </form>
</section>
