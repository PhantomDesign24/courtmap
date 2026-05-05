<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
?>
<header class="op-page-head">
  <h1>구장 등록 신청</h1>
  <p class="op-sub">신청 후 관리자 승인이 필요합니다. 승인 전엔 사용자 검색에 노출되지 않아요.</p>
</header>

<form method="post" action="/operator/venues" class="op-form">

<section class="op-card">
  <div class="op-card-head"><h2>기본 정보</h2></div>
  <div class="op-form-row">
    <label>구장 이름<input type="text" name="name" required placeholder="강남 스파이크 배드민턴"></label>
    <label>지역<input type="text" name="area" required placeholder="서울 강남구 역삼동"></label>
    <label>전화번호<input type="text" name="phone" required placeholder="02-555-3849"></label>
    <label>시간당 가격<input type="number" name="price_per_hour" min="1000" required placeholder="35000"></label>
  </div>
  <div class="op-form-row">
    <label style="flex:2">상세 주소<input type="text" name="address" required placeholder="서울 강남구 테헤란로 123"></label>
    <label>코트 수<input type="number" name="court_count" min="1" max="20" value="4" required></label>
  </div>
  <div class="op-form-row">
    <label>위도 (참고용)<input type="number" name="lat" step="0.0000001" value="37.5005"></label>
    <label>경도 (참고용)<input type="number" name="lng" step="0.0000001" value="127.0364"></label>
  </div>
  <div class="op-form-row">
    <label style="flex:1">소개<textarea name="description" rows="2" placeholder="시설 특징, 주차, 셔틀 정보 등"></textarea></label>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>입금 계좌 (사용자 입금용)</h2></div>
  <div class="op-form-row">
    <label>은행<input type="text" name="bank_name" required></label>
    <label style="flex:2">계좌번호<input type="text" name="bank_account" required></label>
    <label>예금주<input type="text" name="bank_holder" required></label>
  </div>
</section>

<div style="display:flex;gap:8px;margin-bottom:24px">
  <button type="submit" class="btn btn-primary btn-md">신청 제출</button>
  <a href="/operator/venues" class="btn btn-line btn-md">취소</a>
</div>

</form>
