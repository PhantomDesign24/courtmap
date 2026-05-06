<?php
use App\Core\View;
$errors = $errors ?? [];
$old    = $old    ?? [];
$e = static fn(?string $s): string => View::e($s);
?>
<div class="auth-page">
  <main class="auth">
    <div class="auth-brand">
      <div class="auth-brand-mark">코</div>
      <div class="auth-brand-text">코트맵</div>
    </div>
    <h1>다시 만나서 반가워요</h1>
    <p class="auth-sub">로그인하고 가까운 빈 코트를 찾아보세요.</p>

    <?php if ($errors): ?>
      <div class="alert"><?php foreach ($errors as $msg): ?><div><?= $e($msg) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <a href="/auth/kakao" class="btn btn-kakao btn-block">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:6px"><path d="M12 3C6.48 3 2 6.55 2 10.91c0 2.79 1.85 5.24 4.66 6.65l-1.16 4.18 4.83-3.18c.55.07 1.1.11 1.67.11 5.52 0 10-3.55 10-7.91S17.52 3 12 3z"/></svg>
      카카오로 시작하기
    </a>
    <div class="divider"><span>또는</span></div>

    <form method="post" action="/login" class="auth-form">
      <label>이메일 또는 아이디
        <input type="text" name="email" required autocomplete="username" placeholder="이메일 또는 admin" value="<?= $e($old['email'] ?? '') ?>">
      </label>
      <label>비밀번호
        <input type="password" name="password" required placeholder="비밀번호" autocomplete="current-password">
      </label>
      <button type="submit" class="btn btn-primary btn-block">로그인</button>
    </form>
    <p class="auth-link">계정이 없으신가요? <a href="/register">회원가입</a></p>
    <div class="auth-foot">© 코트맵 · 안전한 배드민턴 코트 예약</div>
  </main>
</div>
