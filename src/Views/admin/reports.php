<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $rows */
?>
<header class="op-page-head">
  <h1>신고·이슈</h1>
  <p class="op-sub">노쇼 로그 (자동·수동 신고). 신고 시스템 v2 도입 전 임시 화면.</p>
</header>

<section class="op-card">
  <div class="op-card-head"><h2>최근 노쇼 <span class="op-pill"><?= count($rows) ?></span></h2></div>
  <?php if (!$rows): ?>
    <div class="op-empty">기록이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>시각</th><th>사용자</th><th>구장 / 예약번호</th><th>탐지</th><th>점수</th><th>비고</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="op-mute"><?= $e(substr((string)$r['created_at'], 0, 16)) ?></td>
            <td>
              <div class="fw-600"><?= $e($r['user_name']) ?></div>
              <div class="op-mute"><?= $e($r['user_email']) ?> · 점수 <?= (int)$r['trust_score'] ?></div>
            </td>
            <td>
              <?= $e($r['venue_name']) ?>
              <div class="op-mute num">#<?= $e($r['code']) ?></div>
            </td>
            <td><span class="badge <?= $r['detected_by'] === 'auto' ? 'badge-soft' : 'badge-warn' ?>"><?= $e($r['detected_by']) ?></span></td>
            <td class="num fw-700 text-hot"><?= (int)$r['score_delta'] ?></td>
            <td class="op-mute"><?= $e($r['note'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
