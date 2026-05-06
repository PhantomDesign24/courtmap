<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
$errors = $errors ?? [];
?>
<div class="auth-page">
  <div class="auth-card-side" style="flex:1">
    <main class="auth">
      <div class="auth-brand">
        <div class="auth-brand-mark">코</div>
        <div class="auth-brand-text">환불계좌</div>
      </div>
      <h1>환불 받을 계좌</h1>
      <p class="auth-sub">예약 취소 시 환불받을 본인 계좌입니다. 변경 시 비밀번호 재확인이 필요합니다.</p>

      <?php if ($errors): ?>
        <div class="alert"><?php foreach ($errors as $m): ?><div><?= $e($m) ?></div><?php endforeach; ?></div>
      <?php endif; ?>

      <div class="info-card" style="margin-bottom:14px">
        <div class="info-row"><span>예금주</span><b><?= $e($u['name']) ?></b></div>
        <div class="info-row"><span>현재 등록</span><b><?= $e(($u['refund_bank_name'] ?? '—') . ' ' . ($u['refund_bank_account'] ?? '')) ?></b></div>
      </div>

      <form method="post" action="/me/refund-account" class="auth-form">
        <label>은행<input type="text" name="refund_bank_name" required value="<?= $e($u['refund_bank_name'] ?? '') ?>"></label>
        <label>계좌번호<input type="text" name="refund_bank_account" required value="<?= $e($u['refund_bank_account'] ?? '') ?>"></label>
        <label>예금주<input type="text" name="refund_bank_holder" required value="<?= $e($u['refund_bank_holder'] ?? '') ?>"></label>
        <label>비밀번호 확인<input type="password" name="password" required autocomplete="current-password" placeholder="현재 비밀번호"></label>
        <button type="submit" class="btn btn-primary btn-block">변경</button>
      </form>
      <p class="auth-link"><a href="/me">← 마이로 돌아가기</a></p>
    </main>
  </div>
</div>
