<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venues */
/** @var array $coupons */
/** @var array $memberships */
?>
<header class="op-page-head">
  <h1>쿠폰·멤버십</h1>
  <p class="op-sub">사용자에게 발행할 쿠폰과 멤버십(N시간 정액제) 관리.</p>
</header>

<section class="op-card">
  <div class="op-card-head"><h2>쿠폰 <span class="op-pill"><?= count($coupons) ?></span></h2></div>
  <?php if ($coupons): ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>할인</th><th>최소사용</th><th>유효기간</th><th>발행/사용</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($coupons as $c): ?>
          <tr>
            <td class="fw-600"><?= $e($c['name']) ?></td>
            <td><?= $c['discount_type'] === 'percent' ? (int)$c['discount_value'].'%' : number_format((int)$c['discount_value']).'원' ?></td>
            <td class="num"><?= $c['min_amount'] ? number_format((int)$c['min_amount']).'원' : '—' ?></td>
            <td class="op-mute"><?= $c['valid_until'] ? '~' . substr((string)$c['valid_until'], 0, 10) : '무기한' ?></td>
            <td class="num"><?= (int)$c['issued_count'] ?> / <?= $c['total_quota'] ?? '∞' ?></td>
            <td><span class="badge <?= $c['status']==='active'?'badge-success':'badge-gray' ?>"><?= $e($c['status']) ?></span></td>
            <td>
              <form method="post" action="/operator/coupons/<?= (int)$c['id'] ?>/suspend" style="display:inline">
                <button type="submit" class="btn btn-line btn-sm"><?= $c['status']==='suspended'?'재개':'중지' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="op-empty">발행한 쿠폰이 없습니다.</div>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>쿠폰 발행</h2></div>
  <form method="post" action="/operator/coupons" class="op-form">
    <div class="op-form-row">
      <label>구장
        <select name="venue_id" required>
          <?php foreach ($venues as $v): ?>
            <option value="<?= (int)$v['id'] ?>"><?= $e($v['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>이름<input type="text" name="name" required placeholder="신규 가입 5천원 할인"></label>
      <label>할인 종류
        <select name="discount_type">
          <option value="fixed">정액 (원)</option>
          <option value="percent">정률 (%)</option>
        </select>
      </label>
      <label>할인값<input type="number" name="discount_value" min="1" required value="5000"></label>
      <label>최소 사용 금액<input type="number" name="min_amount" min="0" value="20000"></label>
      <label>총 발행 수 (비우면 무제한)<input type="number" name="total_quota" min="1"></label>
      <label>유효 시작<input type="date" name="valid_from"></label>
      <label>유효 종료<input type="date" name="valid_until"></label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">발행</button>
  </form>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>멤버십 상품 <span class="op-pill"><?= count($memberships) ?></span></h2></div>
  <?php if ($memberships): ?>
    <table class="op-table">
      <thead><tr><th>이름</th><th>구장</th><th>가격</th><th>시간</th><th>유효월</th><th>가입자</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
      <tbody>
        <?php foreach ($memberships as $m): ?>
          <tr>
            <td class="fw-600"><?= $e($m['name']) ?></td>
            <td><?= $e($m['venue_name']) ?></td>
            <td class="num fw-600"><?= number_format((int)$m['price']) ?>원</td>
            <td class="num"><?= (int)$m['hours_total'] ?>h</td>
            <td class="num"><?= (int)$m['valid_months'] ?>개월</td>
            <td class="num"><?= (int)$m['active_count'] ?>명</td>
            <td><span class="badge <?= $m['status']==='active'?'badge-success':'badge-gray' ?>"><?= $e($m['status']) ?></span></td>
            <td>
              <form method="post" action="/operator/memberships/<?= (int)$m['id'] ?>/suspend" style="display:inline">
                <button type="submit" class="btn btn-line btn-sm"><?= $m['status']==='suspended'?'재개':'중지' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="op-empty">등록된 멤버십이 없습니다.</div>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>멤버십 상품 등록</h2></div>
  <form method="post" action="/operator/memberships" class="op-form">
    <div class="op-form-row">
      <label>구장
        <select name="venue_id" required>
          <?php foreach ($venues as $v): ?>
            <option value="<?= (int)$v['id'] ?>"><?= $e($v['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>이름<input type="text" name="name" required placeholder="월 8시간 이용권"></label>
      <label>가격<input type="number" name="price" min="0" required value="120000"></label>
      <label>제공 시간<input type="number" name="hours_total" min="1" required value="8"></label>
      <label>유효 (개월)<input type="number" name="valid_months" min="1" required value="1"></label>
    </div>
    <div class="op-form-row">
      <label style="flex:1">설명<textarea name="description" rows="2"></textarea></label>
    </div>
    <button type="submit" class="btn btn-primary btn-md">등록</button>
  </form>
</section>
