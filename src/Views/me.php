<?php
use App\Core\View;
/** @var array $user */
$e = static fn(?string $s): string => View::e($s);
$roleLabel = ['user' => '일반회원', 'operator' => '운영자', 'admin' => '관리자'][$user['role']] ?? $user['role'];
?>
<main class="auth">
  <h1>내 정보</h1>
  <div class="info-card">
    <div class="info-row"><span>이름</span><b><?= $e($user['name']) ?></b></div>
    <div class="info-row"><span>이메일</span><b><?= $e($user['email']) ?></b></div>
    <div class="info-row"><span>전화</span><b><?= $e($user['phone']) ?></b></div>
    <div class="info-row"><span>역할</span><b><?= $e($roleLabel) ?></b></div>
    <div class="info-row"><span>신뢰점수</span><b><?= (int) $user['trust_score'] ?> / 100</b></div>
  </div>
  <form method="post" action="/logout" style="margin-top:16px">
    <button type="submit" class="btn btn-line btn-block">로그아웃</button>
  </form>
</main>
