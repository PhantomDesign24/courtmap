<?php
use App\Core\View;
$errors = $errors ?? [];
$old    = $old    ?? [];
$e = static fn(?string $s): string => View::e($s);
?>
<main class="auth">
  <h1>회원가입</h1>
  <p class="auth-sub">환불 처리를 위해 본인 계좌가 필수입니다</p>

  <?php if ($errors): ?>
    <div class="alert"><?php foreach ($errors as $msg): ?><div><?= $e($msg) ?></div><?php endforeach; ?></div>
  <?php endif; ?>

  <form method="post" action="/register" class="auth-form">
    <label>이메일<input type="email" name="email" required value="<?= $e($old['email'] ?? '') ?>"></label>
    <label>전화번호<input type="tel" name="phone" required placeholder="010-1234-5678" value="<?= $e($old['phone'] ?? '') ?>"></label>
    <label>이름<input type="text" name="name" required value="<?= $e($old['name'] ?? '') ?>"></label>
    <label>비밀번호 (8자 이상)<input type="password" name="password" required minlength="8"></label>

    <fieldset class="bank-block">
      <legend>환불 받을 본인 계좌</legend>
      <label>은행<input type="text" name="refund_bank_name" required placeholder="신한은행" value="<?= $e($old['bank'] ?? '') ?>"></label>
      <label>계좌번호<input type="text" name="refund_bank_account" required value="<?= $e($old['acct'] ?? '') ?>"></label>
      <label>예금주<input type="text" name="refund_bank_holder" required value="<?= $e($old['holder'] ?? '') ?>"></label>
    </fieldset>

    <p class="text-sub" style="font-size:12px;margin:8px 0 4px">
      구장 운영자 가입은 별도 절차가 필요합니다. <a href="/support">고객센터로 문의</a> 해주세요.
    </p>
    <button type="submit" class="btn btn-primary btn-block">가입하기</button>
  </form>
  <p class="auth-link"><a href="/login">이미 계정이 있어요</a></p>
</main>
