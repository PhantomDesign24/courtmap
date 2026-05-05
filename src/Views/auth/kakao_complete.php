<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $pending */
$errors = $errors ?? [];
?>
<main class="auth">
  <h1>추가 정보 입력</h1>
  <p class="auth-sub">카카오로 시작하셨습니다. 예약/환불 처리에 필요한 정보를 한 번만 채워주세요.</p>

  <?php if ($errors): ?>
    <div class="alert"><?php foreach ($errors as $msg): ?><div><?= $e($msg) ?></div><?php endforeach; ?></div>
  <?php endif; ?>

  <form method="post" action="/auth/kakao/complete" class="auth-form">
    <label>이메일<input type="email" name="email" required value="<?= $e($pending['email'] ?? '') ?>"></label>
    <label>전화번호<input type="tel" name="phone" required placeholder="010-1234-5678"></label>
    <label>이름<input type="text" name="name" required value="<?= $e($pending['nickname'] ?? '') ?>"></label>

    <fieldset class="bank-block">
      <legend>환불 받을 본인 계좌</legend>
      <label>은행<input type="text" name="refund_bank_name" required></label>
      <label>계좌번호<input type="text" name="refund_bank_account" required></label>
      <label>예금주<input type="text" name="refund_bank_holder" required></label>
    </fieldset>

    <button type="submit" class="btn btn-primary btn-block">완료</button>
  </form>
</main>
