<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $stats */
/** @var array $recent */
$flashOk = $_SESSION['flash_ok'] ?? null;
unset($_SESSION['flash_ok']);
?>
<header class="op-page-head">
  <h1>공지·알림 발송</h1>
  <p class="op-sub">선택한 사용자 그룹에 푸시 알림(시스템 알림)을 보냅니다.</p>
</header>

<?php if ($flashOk): ?>
  <div class="op-card" style="padding:12px 16px;color:#0a7e4a;background:#dcf6e8;border-color:#bbe7d0">✓ <?= $e($flashOk) ?></div>
<?php endif; ?>

<div class="op-kpi-grid" style="margin-bottom:18px">
  <div class="op-kpi"><div class="op-kpi-label">활성 일반 회원</div><div class="op-kpi-value"><?= (int)$stats['users'] ?><span>명</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">활성 운영자</div><div class="op-kpi-value"><?= (int)$stats['operators'] ?><span>명</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">전체 활성</div><div class="op-kpi-value"><?= (int)$stats['all_active'] ?><span>명</span></div></div>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>발송</h2></div>
  <form method="post" action="/admin/broadcast" class="op-form" onsubmit="return confirm('정말 발송할까요?');">
    <div class="op-form-row">
      <label>대상
        <select name="audience">
          <option value="user">일반 회원만 (<?= (int)$stats['users'] ?>)</option>
          <option value="operator">운영자만 (<?= (int)$stats['operators'] ?>)</option>
          <option value="all">전체 활성 (<?= (int)$stats['all_active'] ?>)</option>
        </select>
      </label>
      <label style="flex:2">제목<input type="text" name="title" required maxlength="120" placeholder="이번 주말 시스템 점검 안내"></label>
    </div>
    <div class="op-form-row">
      <label style="flex:1">내용<textarea name="body" required rows="3" maxlength="500" placeholder="짧고 명확하게..."></textarea></label>
    </div>
    <div class="op-form-row">
      <label style="flex:1">링크 (선택)<input type="text" name="link_url" maxlength="200" placeholder="/notifications 또는 /support/terms"></label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">발송</button>
  </form>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>최근 공지 <span class="op-pill"><?= count($recent) ?></span></h2></div>
  <?php if (!$recent): ?>
    <div class="op-empty">발송 기록이 없습니다.</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>시각</th><th>제목</th><th>내용</th><th>수신자</th><th>링크</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td class="op-mute"><?= $e(substr((string)$r['sent_at'], 0, 16)) ?></td>
            <td class="fw-600"><?= $e($r['title']) ?></td>
            <td class="op-mute" style="max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $e($r['body']) ?></td>
            <td class="num"><?= (int)$r['recipients'] ?>명</td>
            <td class="op-mute"><?= $r['link_url'] ? $e($r['link_url']) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
