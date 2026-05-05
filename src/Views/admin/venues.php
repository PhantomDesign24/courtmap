<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var string $tab */
/** @var array $venues */
?>
<header class="op-page-head">
  <h1>구장 승인</h1>
  <p class="op-sub">운영자가 신청한 구장을 검토·승인합니다.</p>
</header>

<div class="op-tabs" style="margin-bottom:14px">
  <a href="?tab=pending"   class="<?= $tab === 'pending'   ? 'active' : '' ?>">대기</a>
  <a href="?tab=active"    class="<?= $tab === 'active'    ? 'active' : '' ?>">승인됨</a>
  <a href="?tab=suspended" class="<?= $tab === 'suspended' ? 'active' : '' ?>">반려·정지</a>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>구장 <span class="op-pill"><?= count($venues) ?></span></h2></div>
  <?php if (!$venues): ?>
    <div class="op-empty">해당 상태의 구장이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>지역 / 주소</th><th>운영자</th><th>코트</th><th>가격</th><th>신청일</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($venues as $v): ?>
          <tr>
            <td class="fw-700"><a href="/admin/venues/<?= (int)$v['id'] ?>" style="color:var(--text);text-decoration:none"><?= $e($v['name']) ?></a></td>
            <td>
              <?= $e($v['area']) ?>
              <div class="op-mute"><?= $e($v['address']) ?></div>
            </td>
            <td>
              <?= $e($v['owner_name']) ?>
              <div class="op-mute"><?= $e($v['owner_email']) ?></div>
            </td>
            <td class="num"><?= (int)$v['court_count'] ?>면</td>
            <td class="num"><?= number_format((int)$v['price_per_hour']) ?>원</td>
            <td class="op-mute"><?= $e(substr((string)$v['created_at'], 0, 16)) ?></td>
            <td>
              <?php if ($tab === 'pending'): ?>
                <form method="post" action="/admin/venues/<?= (int)$v['id'] ?>/approve" style="display:inline">
                  <button type="submit" class="btn btn-primary btn-sm">승인</button>
                </form>
                <form method="post" action="/admin/venues/<?= (int)$v['id'] ?>/reject" style="display:inline" onsubmit="return confirm('반려할까요?');">
                  <button type="submit" class="btn btn-line btn-sm">반려</button>
                </form>
              <?php elseif ($tab === 'suspended'): ?>
                <form method="post" action="/admin/venues/<?= (int)$v['id'] ?>/reactivate" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm">재활성화</button>
                </form>
              <?php else: ?>
                <span class="op-mute">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
