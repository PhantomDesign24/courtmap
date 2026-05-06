<?php
use App\Core\View;
$errors = $errors ?? [];
$old    = $old    ?? [];
$e = static fn(?string $s): string => View::e($s);
?>
<div class="auth-page">
  <main class="auth" style="max-width:480px">
    <div class="auth-brand">
      <div class="auth-brand-mark">코</div>
      <div class="auth-brand-text">코트맵</div>
    </div>
    <h1>회원가입</h1>
    <p class="auth-sub">환불 처리를 위해 본인 계좌가 필수입니다.</p>

    <?php if ($errors): ?>
      <div class="alert"><?php foreach ($errors as $msg): ?><div><?= $e($msg) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="post" action="/register" class="auth-form">
      <label>이메일<input type="email" name="email" required placeholder="hello@example.com" value="<?= $e($old['email'] ?? '') ?>"></label>
      <label>전화번호<input type="tel" name="phone" required placeholder="010-1234-5678" value="<?= $e($old['phone'] ?? '') ?>"></label>
      <label>이름<input type="text" name="name" required placeholder="홍길동" value="<?= $e($old['name'] ?? '') ?>"></label>
      <label>비밀번호 (8자 이상)<input type="password" name="password" required minlength="8" placeholder="비밀번호"></label>

      <fieldset class="bank-block">
        <legend>환불 받을 본인 계좌</legend>
        <label>은행<input type="text" name="refund_bank_name" required placeholder="신한은행" value="<?= $e($old['bank'] ?? '') ?>"></label>
        <label>계좌번호<input type="text" name="refund_bank_account" required placeholder="000-0000-0000" value="<?= $e($old['acct'] ?? '') ?>"></label>
        <label>예금주<input type="text" name="refund_bank_holder" required placeholder="본인 명의" value="<?= $e($old['holder'] ?? '') ?>"></label>
      </fieldset>

      <p class="text-sub" style="font-size:12px;margin:4px 0 8px">
        구장 운영자 가입은 별도 절차가 필요합니다. <a href="/support" style="color:var(--brand-500);font-weight:600">고객센터로 문의</a> 해주세요.
      </p>
      <button type="submit" class="btn btn-primary btn-block">가입하기</button>
    </form>
    <p class="auth-link">이미 계정이 있나요? <a href="/login">로그인</a></p>
    <div class="auth-foot">© 코트맵 · 안전한 배드민턴 코트 예약</div>
  </main>
</div>
