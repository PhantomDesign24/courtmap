<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $tags */
?>
<header class="op-page-head">
  <h1>시설 태그</h1>
  <p class="op-sub">구장 등록 시 운영자가 선택할 수 있는 시설 옵션 (주차·라커·샤워실 등). 기존 구장에 적용된 수까지 표시.</p>
</header>

<section class="op-card">
  <div class="op-card-head"><h2>등록된 태그 <span class="op-pill"><?= count($tags) ?></span></h2></div>
  <?php if (!$tags): ?>
    <div class="op-empty">등록된 태그가 없습니다.</div>
  <?php else: ?>
    <?php foreach ($tags as $t): ?>
      <form method="post" action="/admin/tags/<?= (int)$t['id'] ?>/update" id="tag_f_<?= (int)$t['id'] ?>"></form>
    <?php endforeach; ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>정렬</th><th>적용 구장 수</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($tags as $t): $fid = 'tag_f_' . (int)$t['id']; ?>
          <tr>
            <td><input form="<?= $fid ?>" type="text" name="name" value="<?= $e($t['name']) ?>" required style="width:200px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num"><input form="<?= $fid ?>" type="number" name="sort_order" value="<?= (int)$t['sort_order'] ?>" min="0" style="width:80px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
            <td class="num <?= (int)$t['use_count'] > 0 ? 'fw-700' : 'op-mute' ?>"><?= (int)$t['use_count'] ?>개</td>
            <td style="display:flex;gap:4px">
              <button form="<?= $fid ?>" type="submit" class="btn btn-primary btn-sm">저장</button>
              <form method="post" action="/admin/tags/<?= (int)$t['id'] ?>/delete" onsubmit="return confirm('삭제할까요? 적용 구장에서도 제거됩니다.');" style="display:inline">
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
  <div class="op-card-head"><h2>새 태그 추가</h2></div>
  <form method="post" action="/admin/tags" class="op-form">
    <div class="op-form-row">
      <label style="flex:1">이름<input type="text" name="name" required maxlength="40" placeholder="주차장 / 라커룸 / 샤워실 / 24시간 / ..."></label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">추가</button>
  </form>
</section>
