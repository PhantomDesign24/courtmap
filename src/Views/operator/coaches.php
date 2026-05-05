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
    <?php foreach ($coaches as $c): ?>
      <form method="post" action="/operator/coaches/<?= (int)$c['id'] ?>/update" id="co_f_<?= (int)$c['id'] ?>"></form>
    <?php endforeach; ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>경력</th><th>회당 가격</th><th>시간(분)</th><th>사진 URL</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($coaches as $c): $fid = 'co_f_' . (int)$c['id']; ?>
          <tr style="<?= $c['status']!=='active'?'opacity:0.5':'' ?>">
            <td><input form="<?= $fid ?>" type="text" name="name" value="<?= $e($c['name']) ?>" required style="width:120px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td><input form="<?= $fid ?>" type="text" name="career" value="<?= $e($c['career'] ?? '') ?>" style="width:200px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num"><input form="<?= $fid ?>" type="number" name="price" value="<?= (int)$c['price_per_lesson'] ?>" min="0" required style="width:90px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num"><input form="<?= $fid ?>" type="number" name="duration_min" value="<?= (int)$c['duration_min'] ?>" min="15" required style="width:70px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td><input form="<?= $fid ?>" type="text" name="img_url" value="<?= $e($c['img_url'] ?? '') ?>" style="width:160px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td><span class="badge badge-gray"><?= $e($c['status']) ?></span></td>
            <td style="display:flex;gap:4px">
              <button form="<?= $fid ?>" type="submit" class="btn btn-primary btn-sm">저장</button>
              <?php if ($c['status'] === 'active'): ?>
                <form method="post" action="/operator/coaches/<?= (int)$c['id'] ?>/delete" onsubmit="return confirm('비활성화할까요?');" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm">중지</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <tr style="<?= $c['status']!=='active'?'opacity:0.5':'' ?>"><td colspan="7" style="padding:0 14px 12px"><textarea form="<?= $fid ?>" name="bio" rows="2" placeholder="소개" style="width:100%;padding:6px 10px;border:1px solid var(--line);border-radius:6px;font-size:12.5px;font-family:inherit;resize:vertical"><?= $e($c['bio'] ?? '') ?></textarea></td></tr>
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
