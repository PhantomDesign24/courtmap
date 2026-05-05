<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venues */
?>
<header class="op-page-head" style="display:flex;align-items:center;justify-content:space-between">
  <div>
    <h1>구장 관리</h1>
    <p class="op-sub">소유 구장의 정보·코트·운영시간·환불정책·계좌·시설태그를 편집합니다.</p>
  </div>
  <a href="/operator/venues/new" class="btn btn-primary btn-md">+ 새 구장 신청</a>
</header>

<section class="op-card">
  <div class="op-card-head"><h2>내 구장 <span class="op-pill"><?= count($venues) ?></span></h2></div>
  <?php if (!$venues): ?>
    <div class="op-empty">등록된 구장이 없습니다. (가입 시 구장 등록 흐름은 /operator/register 또는 관리자 등록)</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>지역</th><th>코트 수</th><th>가격(시간당)</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($venues as $v): ?>
          <tr>
            <td class="fw-700"><?= $e($v['name']) ?></td>
            <td><?= $e($v['area']) ?></td>
            <td class="num"><?= (int) $v['court_count'] ?>면</td>
            <td class="num"><?= number_format((int) $v['price_per_hour']) ?>원</td>
            <td><span class="badge <?= $v['status']==='active'?'badge-success':'badge-gray' ?>"><?= $e($v['status']) ?></span></td>
            <td style="display:flex;gap:4px">
              <a href="/operator/venues/<?= (int)$v['id'] ?>" class="btn btn-primary btn-sm">상세</a>
              <a href="/operator/venues/<?= (int)$v['id'] ?>/edit" class="btn btn-line btn-sm">편집</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
