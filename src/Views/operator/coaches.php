<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venues */
/** @var int $venueId */
/** @var array $coaches */
?>
<header class="op-page-head">
  <h1>강사 관리</h1>
  <p class="op-sub">구장 상세의 "레슨" 탭에 노출됩니다.</p>
</header>

<form method="get" class="op-filter-row" style="margin-bottom:16px">
  <select name="venue_id" onchange="this.form.submit()">
    <?php foreach ($venues as $v): ?>
      <option value="<?= (int)$v['id'] ?>" <?= $venueId === (int)$v['id'] ? 'selected' : '' ?>><?= $e($v['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>강사 <span class="op-pill"><?= count(array_filter($coaches, fn($c) => $c['status'] === 'active')) ?></span></h2></div>
  <?php if ($coaches): ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>경력</th><th>회당 가격</th><th>회당 시간</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($coaches as $c): ?>
          <tr style="<?= $c['status']!=='active'?'opacity:0.5':'' ?>">
            <td class="fw-700"><?= $e($c['name']) ?></td>
            <td class="op-mute"><?= $e($c['career'] ?? '') ?></td>
            <td class="num fw-600"><?= number_format((int)$c['price_per_lesson']) ?>원</td>
            <td class="num"><?= (int)$c['duration_min'] ?>분</td>
            <td><span class="badge badge-gray"><?= $e($c['status']) ?></span></td>
            <td>
              <?php if ($c['status'] === 'active'): ?>
                <form method="post" action="/operator/coaches/<?= (int)$c['id'] ?>/delete" onsubmit="return confirm('비활성화할까요?');" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm">중지</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="op-empty">등록된 강사가 없습니다.</div>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>강사 등록</h2></div>
  <form method="post" action="/operator/coaches/add" class="op-form">
    <input type="hidden" name="venue_id" value="<?= (int)$venueId ?>">
    <div class="op-form-row">
      <label>이름<input type="text" name="name" required></label>
      <label>경력 (한 줄)<input type="text" name="career" placeholder="前 국가대표 상비군 · 12년"></label>
      <label>회당 가격<input type="number" name="price" min="0" required value="50000"></label>
      <label>회당 시간 (분)<input type="number" name="duration_min" min="15" value="60"></label>
      <label style="flex:1">사진 URL<input type="text" name="img_url"></label>
    </div>
    <div class="op-form-row">
      <label style="flex:1">소개<textarea name="bio" rows="2"></textarea></label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">등록</button>
  </form>
</section>
