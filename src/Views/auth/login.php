<?php
use App\Core\View;
$errors = $errors ?? [];
$old    = $old    ?? [];
$e = static fn(?string $s): string => View::e($s);
?>
<main class="auth">
  <h1>로그인</h1>
  <p class="auth-sub">코트맵에 오신 것을 환영합니다</p>

  <?php if ($errors): ?>
    <div class="alert"><?php foreach ($errors as $msg): ?><div><?= $e($msg) ?></div><?php endforeach; ?></div>
  <?php endif; ?>

  <a href="/auth/kakao" class="btn btn-kakao btn-block">카카오로 시작하기</a>
  <div class="divider"><span>또는</span></div>

  <form method="post" action="/login" class="auth-form">
    <label>이메일<input type="email" name="email" required value="<?= $e($old['email'] ?? '') ?>"></label>
    <label>비밀번호<input type="password" name="password" required></label>
    <button type="submit" class="btn btn-primary btn-block">로그인</button>
  </form>
  <p class="auth-link"><a href="/register">회원가입</a></p>
</main>
