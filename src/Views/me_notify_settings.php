<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
$flashOk = $flashOk ?? null;
?>
<div class="auth-page">
  <div class="auth-card-side" style="flex:1">
    <main class="auth">
      <div class="auth-brand">
        <div class="auth-brand-mark">코</div>
        <div class="auth-brand-text">알림 설정</div>
      </div>
      <h1>알림 설정</h1>
      <p class="auth-sub">받고 싶은 알림 종류를 선택하세요. 시스템 공지·예약 상태 알림은 항상 발송됩니다.</p>

      <?php if ($flashOk): ?>
        <div class="alert" style="background:#dcf6e8;color:#0a7e4a;border-color:#bbe7d0">✓ <?= $e($flashOk) ?></div>
      <?php endif; ?>

      <form method="post" action="/me/notify-settings" class="auth-form">
        <label class="setting-row">
          <div>
            <div class="setting-title">예약 리마인더</div>
            <div class="setting-sub">이용 24시간 전·1시간 전 알림</div>
          </div>
          <input type="checkbox" name="notify_reminder" value="1" <?= !empty($u['notify_reminder']) ? 'checked' : '' ?>>
        </label>
        <label class="setting-row">
          <div>
            <div class="setting-title">공지 알림</div>
            <div class="setting-sub">관리자가 발송하는 시스템 공지 (점검·업데이트)</div>
          </div>
          <input type="checkbox" name="notify_broadcast" value="1" <?= !empty($u['notify_broadcast']) ? 'checked' : '' ?>>
        </label>
        <label class="setting-row">
          <div>
            <div class="setting-title">마케팅·이벤트</div>
            <div class="setting-sub">핫딜·쿠폰·신규 구장 소식</div>
          </div>
          <input type="checkbox" name="notify_marketing" value="1" <?= !empty($u['notify_marketing']) ? 'checked' : '' ?>>
        </label>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:14px">저장</button>
      </form>
      <p class="auth-link"><a href="/me">← 마이로 돌아가기</a></p>
    </main>
  </div>
</div>

<style>
.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border: 1px solid var(--line);
  border-radius: 12px;
  cursor: pointer;
  transition: border-color .12s, background .12s;
}
.setting-row:hover { border-color: var(--line-strong); background: var(--gray-25); }
.setting-row:has(input:checked) { border-color: var(--brand-300); background: var(--brand-50); }
.setting-title { font-size: 14px; font-weight: 700; color: var(--text); }
.setting-sub { font-size: 12.5px; color: var(--text-sub); margin-top: 2px; }
.setting-row input[type="checkbox"] {
  width: 22px; height: 22px;
  accent-color: var(--brand-500);
  cursor: pointer;
}
</style>
