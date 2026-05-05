<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $users */
/** @var string $q */
/** @var string $role */
$role_label = ['user' => '일반', 'operator' => '운영자', 'admin' => '관리자'];
?>
<header class="op-page-head">
  <h1>사용자 관리</h1>
  <p class="op-sub">사용자 검색·신뢰점수 조정·정지·해제.</p>
</header>

<form method="get" class="op-filter-row" style="margin-bottom:16px">
  <select name="role">
    <option value="">전체</option>
    <?php foreach ($role_label as $k => $v): ?>
      <option value="<?= $e($k) ?>" <?= $role === $k ? 'selected' : '' ?>><?= $e($v) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="search" name="q" value="<?= $e($q) ?>" placeholder="이름·이메일·전화">
  <button type="submit" class="btn btn-line btn-sm">검색</button>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>사용자 <span class="op-pill"><?= count($users) ?></span></h2></div>
  <?php if (!$users): ?>
    <div class="op-empty">조건에 맞는 사용자가 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>ID</th><th>이름·연락처</th><th>역할</th><th>신뢰점수</th><th>상태</th><th>제한</th><th>가입</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="num">#<?= (int)$u['id'] ?></td>
            <td>
              <div class="fw-600"><?= $e($u['name']) ?></div>
              <div class="op-mute"><?= $e($u['email']) ?> · <?= $e($u['phone']) ?></div>
            </td>
            <td><span class="badge badge-soft"><?= $e($role_label[$u['role']] ?? $u['role']) ?></span></td>
            <td>
              <form method="post" action="/admin/users/<?= (int)$u['id'] ?>/score" style="display:flex;gap:4px;align-items:center">
                <input type="number" name="trust_score" min="0" max="100" value="<?= (int)$u['trust_score'] ?>" style="width:60px;height:28px;padding:0 6px;border:1px solid var(--line-strong);border-radius:6px;font-size:12px">
                <button type="submit" class="btn btn-line btn-sm" style="height:28px;padding:0 8px;font-size:11.5px">저장</button>
              </form>
            </td>
            <td><span class="badge <?= $u['status']==='active'?'badge-success':'badge-hot' ?>"><?= $e($u['status']) ?></span></td>
            <td class="op-mute"><?= $u['restricted_until'] ? $e(substr((string)$u['restricted_until'], 0, 10)) : '—' ?></td>
            <td class="op-mute"><?= $e(substr((string)$u['created_at'], 0, 10)) ?></td>
            <td>
              <form method="post" action="/admin/users/<?= (int)$u['id'] ?>/suspend" style="display:inline" onsubmit="return confirm('상태를 토글할까요?');">
                <button type="submit" class="btn btn-line btn-sm">
                  <?= $u['status'] === 'suspended' ? '해제' : '정지' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
