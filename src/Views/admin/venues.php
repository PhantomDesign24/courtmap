<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var string $tab */
/** @var string $q */
/** @var array $venues */
/** @var array $counts */
$st = [
  'pending'  => ['승인 대기', 'badge-warn'],
  'active'   => ['활성',     'badge-success'],
  'suspended'=> ['정지',     'badge-hot'],
  'closed'   => ['폐쇄',     'badge-gray'],
];
?>
<header class="op-page-head">
  <h1>구장 관리</h1>
  <p class="op-sub">전체 구장 검색·승인·편집·운영자 변경·정지·폐쇄.</p>
</header>

<div class="op-tabs" style="display:flex;gap:4px;border-bottom:1px solid var(--line);margin-bottom:14px;flex-wrap:wrap">
  <?php foreach ([
    'all'       => ['전체',     (int)$counts['c_all']],
    'pending'   => ['승인 대기', (int)$counts['c_pending']],
    'active'    => ['활성',     (int)$counts['c_active']],
    'suspended' => ['정지',     (int)$counts['c_suspended']],
    'closed'    => ['폐쇄',     (int)$counts['c_closed']],
  ] as $k => [$label, $cnt]):
    $active = $tab === $k;
    $href = '?tab=' . $k . ($q !== '' ? '&q=' . urlencode($q) : '');
  ?>
    <a href="<?= $e($href) ?>" class="op-tab" style="padding:10px 14px;font-size:13px;font-weight:600;color:<?= $active?'var(--brand-500)':'var(--text-sub)' ?>;border-bottom:2px solid <?= $active?'var(--brand-500)':'transparent' ?>;text-decoration:none">
      <?= $e($label) ?> <span class="op-pill" style="margin-left:4px;font-size:10.5px"><?= $cnt ?></span>
    </a>
  <?php endforeach; ?>
</div>

<form method="get" class="op-filter-row" style="margin-bottom:16px;display:flex;gap:8px;align-items:center">
  <input type="hidden" name="tab" value="<?= $e($tab) ?>">
  <input type="search" name="q" value="<?= $e($q) ?>" placeholder="구장명·지역·주소·운영자 이름·이메일" style="flex:1;height:34px;padding:0 12px;border:1px solid var(--line-strong);border-radius:8px">
  <button type="submit" class="btn btn-primary btn-sm">검색</button>
  <?php if ($q !== ''): ?>
    <a href="?tab=<?= $e($tab) ?>" class="btn btn-line btn-sm">초기화</a>
  <?php endif; ?>
</form>

<section class="op-card">
  <div class="op-card-head"><h2>구장 <span class="op-pill"><?= count($venues) ?></span></h2></div>
  <?php if (!$venues): ?>
    <div class="op-empty">조건에 맞는 구장이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>지역 / 주소</th><th>운영자</th><th>상태</th><th>코트</th><th>가격</th><th>등록</th><th class="op-th-actions">처리</th></tr></thead>
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
            <td><span class="badge <?= $e($st[$v['status']][1] ?? 'badge-gray') ?>"><?= $e($st[$v['status']][0] ?? $v['status']) ?></span></td>
            <td class="num"><?= (int)$v['court_count'] ?>면</td>
            <td class="num"><?= number_format((int)$v['price_per_hour']) ?>원</td>
            <td class="op-mute"><?= $e(substr((string)$v['created_at'], 0, 10)) ?></td>
            <td style="display:flex;gap:4px;justify-content:flex-end;flex-wrap:wrap">
              <a href="/admin/venues/<?= (int)$v['id'] ?>" class="btn btn-line btn-sm">상세</a>
              <a href="/admin/venues/<?= (int)$v['id'] ?>/edit" class="btn btn-primary btn-sm">편집</a>
              <?php if ($v['status'] === 'pending'): ?>
                <form method="post" action="/admin/venues/<?= (int)$v['id'] ?>/approve" style="display:inline">
                  <button type="submit" class="btn btn-line btn-sm" style="border-color:var(--brand-300);color:var(--brand-700)">승인</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
