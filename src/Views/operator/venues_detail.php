<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $venue */
/** @var array $kpi */
/** @var array $courts */
/** @var array $hours */
/** @var array $rules */
/** @var array $deals */
/** @var array $equipment */
/** @var array $coaches */
/** @var array $coupons */
/** @var array $memberships */
/** @var array $recent */
/** @var array $photos */
/** @var ?string $flashErr */
$kdays = ['일','월','화','수','목','금','토'];
$type_label = ['default'=>'기본','dow'=>'요일','holiday'=>'공휴일','specific_date'=>'특정 날짜'];
$st_label = ['pending'=>'대기','confirmed'=>'확정','canceled'=>'취소','no_show'=>'노쇼','done'=>'완료'];
$hoursByDow = [];
foreach ($hours as $h) $hoursByDow[(int)$h['day_of_week']] = $h;
$venueId = (int)$venue['id'];
?>
<header class="op-page-head" style="display:flex;align-items:center;justify-content:space-between">
  <div>
    <h1><?= $e($venue['name']) ?></h1>
    <p class="op-sub"><?= $e($venue['area']) ?> · <?= $e($venue['address']) ?> · <?= $e($venue['phone']) ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="/operator/venues/<?= $venueId ?>/edit" class="btn btn-line btn-md">상세 편집</a>
    <a href="/venues/<?= $venueId ?>" target="_blank" class="btn btn-line btn-md">사용자 화면 보기 ↗</a>
  </div>
</header>

<div class="op-kpi-grid" style="margin-bottom:18px">
  <div class="op-kpi"><div class="op-kpi-label">오늘 예약</div><div class="op-kpi-value"><?= (int)$kpi['today_cnt'] ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">오늘 매출</div><div class="op-kpi-value num"><?= number_format((int)$kpi['today_rev']) ?><span>원</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">입금 대기</div><div class="op-kpi-value"><?= (int)$kpi['pending_cnt'] ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">활성 코트</div><div class="op-kpi-value"><?= (int)$kpi['court_cnt'] ?><span>면</span></div></div>
</div>

<div class="op-tabs" style="display:flex;gap:4px;border-bottom:1px solid var(--line);margin-bottom:18px;overflow-x:auto">
  <?php foreach ([
    'overview'    => '개요',
    'photos'      => '사진',
    'reservations'=> '최근 예약',
    'courts'      => '코트',
    'rules'       => '슬롯 규칙',
    'deals'       => '핫딜',
    'equipment'   => '장비',
    'coaches'     => '강사',
    'coupons'     => '쿠폰·멤버십',
  ] as $k => $label): ?>
    <button type="button" class="op-tab" data-tab="<?= $k ?>" style="padding:10px 14px;border:none;background:none;font-size:13px;font-weight:600;color:var(--text-sub);cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap"><?= $e($label) ?></button>
  <?php endforeach; ?>
</div>

<!-- 개요 -->
<div class="op-tab-pane" data-pane="overview">
  <section class="op-card">
    <div class="op-card-head"><h2>운영시간</h2></div>
    <div style="padding:14px 18px;display:grid;grid-template-columns:repeat(7,1fr);gap:8px">
      <?php for ($d = 0; $d < 7; $d++):
        $h = $hoursByDow[$d] ?? null;
        $closed = $h && !empty($h['is_closed']);
      ?>
        <div style="text-align:center;padding:10px 6px;border:1px solid var(--line);border-radius:8px;<?= $closed?'opacity:0.4':'' ?>">
          <div class="fw-700" style="font-size:13px;margin-bottom:4px"><?= $kdays[$d] ?></div>
          <div class="op-mute" style="font-size:11.5px">
            <?= $closed ? '휴무' : ($e(substr($h['open_time']??'10:00', 0, 5)) . '~' . $e(substr($h['close_time']??'23:59', 0, 5))) ?>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  </section>

  <section class="op-card">
    <div class="op-card-head"><h2>환불 정책</h2></div>
    <div style="padding:14px 18px;display:grid;grid-template-columns:repeat(3,1fr);gap:14px;font-size:13px">
      <div><div class="op-mute">24시간 전</div><div class="fw-700 num"><?= (int)$venue['refund_24h_pct'] ?>%</div></div>
      <div><div class="op-mute">1시간 전</div><div class="fw-700 num"><?= (int)$venue['refund_1h_pct'] ?>%</div></div>
      <div><div class="op-mute">1시간 이내</div><div class="fw-700 num"><?= (int)$venue['refund_lt1h_pct'] ?>%</div></div>
    </div>
  </section>

  <section class="op-card">
    <div class="op-card-head"><h2>입금 계좌</h2></div>
    <div style="padding:14px 18px;font-size:13px">
      <div><span class="op-mute">은행</span> <span class="fw-600"><?= $e($venue['bank_name']) ?></span></div>
      <div style="margin-top:6px"><span class="op-mute">계좌</span> <span class="fw-600 num"><?= $e($venue['bank_account']) ?></span></div>
      <div style="margin-top:6px"><span class="op-mute">예금주</span> <span class="fw-600"><?= $e($venue['bank_holder']) ?></span></div>
      <div style="margin-top:6px"><span class="op-mute">입금 기한</span> <span class="fw-600 num"><?= (int)$venue['deposit_due_hours'] ?>시간</span></div>
    </div>
  </section>
</div>

<!-- 사진 -->
<div class="op-tab-pane" data-pane="photos" style="display:none">
  <?php if (!empty($flashErr)): ?>
    <div class="op-card" style="padding:12px 16px;color:#c0392b;background:#fff5f4;border-color:#ffd2cc"><?= $e($flashErr) ?></div>
  <?php endif; ?>
  <section class="op-card">
    <div class="op-card-head"><h2>업로드</h2></div>
    <form method="post" enctype="multipart/form-data" action="/operator/venues/<?= $venueId ?>/photos" style="padding:14px 18px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required style="font-size:13px">
      <button type="submit" class="btn btn-primary btn-sm">업로드</button>
      <span class="op-mute" style="font-size:12px">JPG · PNG · WEBP, 최대 5MB · 첫 업로드는 자동으로 대표 사진</span>
    </form>
  </section>
  <section class="op-card">
    <div class="op-card-head"><h2>등록된 사진 <span class="op-pill"><?= count($photos) ?></span></h2></div>
    <?php if (!$photos): ?>
      <div class="op-empty">사진이 없습니다.</div>
    <?php else: ?>
      <div style="padding:14px 18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px">
        <?php foreach ($photos as $p): ?>
          <div style="border:1px solid var(--line);border-radius:10px;overflow:hidden;background:var(--gray-50)">
            <div style="aspect-ratio:4/3;background:#000;position:relative">
              <img src="<?= $e($p['url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block">
              <?php if ((int)$p['is_main'] === 1): ?>
                <span class="badge badge-success" style="position:absolute;top:8px;left:8px">대표</span>
              <?php endif; ?>
            </div>
            <div style="padding:8px;display:flex;gap:6px">
              <?php if ((int)$p['is_main'] !== 1): ?>
                <form method="post" action="/operator/venues/<?= $venueId ?>/photos/<?= (int)$p['id'] ?>/main" style="flex:1">
                  <button type="submit" class="btn btn-line btn-sm" style="width:100%">대표</button>
                </form>
              <?php endif; ?>
              <form method="post" action="/operator/venues/<?= $venueId ?>/photos/<?= (int)$p['id'] ?>/delete" onsubmit="return confirm('삭제할까요?');" style="flex:1">
                <button type="submit" class="btn btn-line btn-sm" style="width:100%">삭제</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<!-- 최근 예약 -->
<div class="op-tab-pane" data-pane="reservations" style="display:none">
  <section class="op-card">
    <div class="op-card-head"><h2>최근 예약 20건</h2></div>
    <?php if (!$recent): ?>
      <div class="op-empty">예약이 없습니다.</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>코드</th><th>날짜/시간</th><th>코트</th><th>회원</th><th>금액</th><th>상태</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $r): ?>
            <tr>
              <td class="num fw-600"><a href="/reservations/<?= $e($r['code']) ?>" target="_blank"><?= $e($r['code']) ?></a></td>
              <td class="num"><?= $e($r['reservation_date']) ?> <?= sprintf('%02d:00', (int)$r['start_hour']) ?>~<?= sprintf('%02d:00', (int)$r['start_hour']+(int)$r['duration_hours']) ?></td>
              <td><?= $e($r['court_name']) ?></td>
              <td><?= $e($r['user_name']) ?></td>
              <td class="num fw-600"><?= number_format((int)$r['total_price']) ?>원</td>
              <td><span class="badge badge-soft"><?= $e($st_label[$r['status']] ?? $r['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>

<!-- 코트 -->
<div class="op-tab-pane" data-pane="courts" style="display:none">
  <section class="op-card">
    <div class="op-card-head"><h2>코트 <span class="op-pill"><?= count(array_filter($courts, fn($c)=>$c['status']==='active')) ?></span></h2></div>
    <?php if (!$courts): ?>
      <div class="op-empty">코트가 없습니다.</div>
    <?php else: ?>
      <?php foreach ($courts as $i => $c): ?>
        <form method="post" action="/operator/venues/<?= $venueId ?>/courts/<?= (int)$c['id'] ?>/update" id="court_f_<?= (int)$c['id'] ?>"></form>
      <?php endforeach; ?>
      <table class="op-table">
        <thead><tr><th>이름</th><th>가격(오버라이드)</th><th>정렬</th><th>상태</th><th class="op-th-actions">처리</th></tr></thead>
        <tbody>
          <?php foreach ($courts as $c): $fid = 'court_f_' . (int)$c['id']; ?>
            <tr style="<?= $c['status']!=='active'?'opacity:0.5':'' ?>">
              <td><input form="<?= $fid ?>" type="text" name="name" value="<?= $e($c['name']) ?>" required style="width:100px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
              <td class="num"><input form="<?= $fid ?>" type="number" name="price_override" value="<?= $c['price_override']!==null?(int)$c['price_override']:'' ?>" placeholder="기본" min="0" style="width:90px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
              <td class="num"><input form="<?= $fid ?>" type="number" name="sort_order" value="<?= (int)$c['sort_order'] ?>" min="0" style="width:60px;height:30px;padding:0 8px;border:1px solid var(--line);border-radius:6px;font-size:13px"></td>
              <td><span class="badge badge-gray"><?= $e($c['status']) ?></span></td>
              <td style="display:flex;gap:4px">
                <button form="<?= $fid ?>" type="submit" class="btn btn-primary btn-sm">저장</button>
                <?php if ($c['status'] === 'active'): ?>
                  <form method="post" action="/operator/venues/<?= $venueId ?>/courts/<?= (int)$c['id'] ?>/delete" onsubmit="return confirm('이 코트를 닫을까요?');" style="display:inline">
                    <button type="submit" class="btn btn-line btn-sm">닫기</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <form method="post" action="/operator/venues/<?= $venueId ?>/courts/add" style="padding:14px 18px;border-top:1px solid var(--line);display:flex;gap:8px;align-items:center">
      <input type="text" name="name" placeholder="새 코트 이름" required style="height:34px;padding:0 12px;border:1px solid var(--line-strong);border-radius:8px;font-size:13px;font-family:inherit">
      <button type="submit" class="btn btn-primary btn-sm">코트 추가</button>
    </form>
  </section>
</div>

<!-- 슬롯 규칙 -->
<div class="op-tab-pane" data-pane="rules" style="display:none">
  <section class="op-card">
    <div class="op-card-head"><h2>현재 규칙 <span class="op-pill"><?= count($rules) ?></span></h2></div>
    <?php if (!$rules): ?>
      <div class="op-empty">규칙이 없습니다. <a href="/operator/slots?venue_id=<?= $venueId ?>">슬롯 페이지에서 추가</a></div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>유형</th><th>대상</th><th>슬롯 단위</th><th>비고</th></tr></thead>
        <tbody>
          <?php foreach ($rules as $r): ?>
            <tr>
              <td><span class="badge badge-soft"><?= $e($type_label[$r['rule_type']] ?? $r['rule_type']) ?></span></td>
              <td>
                <?= match ($r['rule_type']) {
                  'dow'           => $e($kdays[(int)$r['day_of_week']] . '요일'),
                  'specific_date' => $e($r['specific_date']),
                  'holiday'       => '한국 공휴일 자동',
                  default         => '항상 적용',
                } ?>
              </td>
              <td class="fw-700 num"><?= (int)$r['slot_unit_hours'] ?>시간</td>
              <td class="op-mute"><?= $e($r['note'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div style="padding:14px 18px;border-top:1px solid var(--line)">
      <a href="/operator/slots?venue_id=<?= $venueId ?>" class="btn btn-line btn-sm">슬롯 규칙 페이지로 →</a>
    </div>
  </section>
</div>

<!-- 핫딜 -->
<div class="op-tab-pane" data-pane="deals" style="display:none">
  <section class="op-card">
    <div class="op-card-head"><h2>핫딜 <span class="op-pill"><?= count($deals) ?></span></h2></div>
    <?php if (!$deals): ?>
      <div class="op-empty">발행한 핫딜이 없습니다.</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>날짜</th><th>시간</th><th>코트</th><th>할인율</th><th>상태</th></tr></thead>
        <tbody>
          <?php foreach ($deals as $d): ?>
            <tr>
              <td class="num"><?= $e($d['target_date']) ?></td>
              <td class="num"><?= sprintf('%02d:00~%02d:00', (int)$d['target_start_hour'], (int)$d['target_end_hour']) ?></td>
              <td><?= $d['court_name'] ? $e($d['court_name']) : '<span class="op-mute">전체</span>' ?></td>
              <td class="fw-700 num"><?= (int)$d['discount_pct'] ?>%</td>
              <td><span class="badge <?= $d['status']==='active'?'badge-success':'badge-gray' ?>"><?= $e($d['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div style="padding:14px 18px;border-top:1px solid var(--line)">
      <a href="/operator/pricing?venue_id=<?= $venueId ?>" class="btn btn-line btn-sm">핫딜·자동가격 페이지로 →</a>
    </div>
  </section>
</div>

<!-- 장비 -->
<div class="op-tab-pane" data-pane="equipment" style="display:none">
  <section class="op-card">
    <div class="op-card-head"><h2>장비 옵션 <span class="op-pill"><?= count(array_filter($equipment, fn($i)=>$i['status']==='active')) ?></span></h2></div>
    <?php if (!$equipment): ?>
      <div class="op-empty">등록된 장비가 없습니다.</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>이름</th><th>가격</th><th>최대수량</th><th>기본체크</th><th>상태</th></tr></thead>
        <tbody>
          <?php foreach ($equipment as $eq): ?>
            <tr style="<?= $eq['status']!=='active'?'opacity:0.5':'' ?>">
              <td class="fw-600"><?= $e($eq['name']) ?></td>
              <td class="num"><?= number_format((int)$eq['price']) ?>원</td>
              <td class="num"><?= (int)$eq['max_qty'] ?></td>
              <td><?= !empty($eq['default_check']) ? '✓' : '—' ?></td>
              <td><span class="badge badge-gray"><?= $e($eq['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div style="padding:14px 18px;border-top:1px solid var(--line)">
      <a href="/operator/equipment?venue_id=<?= $venueId ?>" class="btn btn-line btn-sm">장비 페이지로 →</a>
    </div>
  </section>
</div>

<!-- 강사 -->
<div class="op-tab-pane" data-pane="coaches" style="display:none">
  <section class="op-card">
    <div class="op-card-head"><h2>강사 <span class="op-pill"><?= count(array_filter($coaches, fn($c)=>$c['status']==='active')) ?></span></h2></div>
    <?php if (!$coaches): ?>
      <div class="op-empty">등록된 강사가 없습니다.</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>이름</th><th>경력</th><th>회당 가격</th><th>회당 시간</th><th>상태</th></tr></thead>
        <tbody>
          <?php foreach ($coaches as $cc): ?>
            <tr style="<?= $cc['status']!=='active'?'opacity:0.5':'' ?>">
              <td class="fw-700"><?= $e($cc['name']) ?></td>
              <td class="op-mute"><?= $e($cc['career'] ?? '') ?></td>
              <td class="num fw-600"><?= number_format((int)$cc['price_per_lesson']) ?>원</td>
              <td class="num"><?= (int)$cc['duration_min'] ?>분</td>
              <td><span class="badge badge-gray"><?= $e($cc['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div style="padding:14px 18px;border-top:1px solid var(--line)">
      <a href="/operator/coaches?venue_id=<?= $venueId ?>" class="btn btn-line btn-sm">강사 페이지로 →</a>
    </div>
  </section>
</div>

<!-- 쿠폰·멤버십 -->
<div class="op-tab-pane" data-pane="coupons" style="display:none">
  <section class="op-card">
    <div class="op-card-head"><h2>쿠폰 <span class="op-pill"><?= count($coupons) ?></span></h2></div>
    <?php if (!$coupons): ?>
      <div class="op-empty">발행한 쿠폰이 없습니다.</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>이름</th><th>할인</th><th>유효기간</th><th>상태</th></tr></thead>
        <tbody>
          <?php foreach ($coupons as $c): ?>
            <tr>
              <td class="fw-600"><?= $e($c['name']) ?></td>
              <td><?= $c['discount_type']==='percent' ? (int)$c['discount_value'].'%' : number_format((int)$c['discount_value']).'원' ?></td>
              <td class="op-mute"><?= $c['valid_until'] ? '~'.substr((string)$c['valid_until'],0,10) : '무기한' ?></td>
              <td><span class="badge <?= $c['status']==='active'?'badge-success':'badge-gray' ?>"><?= $e($c['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
  <section class="op-card">
    <div class="op-card-head"><h2>멤버십 <span class="op-pill"><?= count($memberships) ?></span></h2></div>
    <?php if (!$memberships): ?>
      <div class="op-empty">등록된 멤버십이 없습니다.</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>이름</th><th>가격</th><th>시간</th><th>유효월</th><th>가입자</th><th>상태</th></tr></thead>
        <tbody>
          <?php foreach ($memberships as $m): ?>
            <tr>
              <td class="fw-600"><?= $e($m['name']) ?></td>
              <td class="num fw-600"><?= number_format((int)$m['price']) ?>원</td>
              <td class="num"><?= (int)$m['hours_total'] ?>h</td>
              <td class="num"><?= (int)$m['valid_months'] ?>개월</td>
              <td class="num"><?= (int)$m['active_count'] ?>명</td>
              <td><span class="badge <?= $m['status']==='active'?'badge-success':'badge-gray' ?>"><?= $e($m['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div style="padding:14px 18px;border-top:1px solid var(--line)">
      <a href="/operator/coupons" class="btn btn-line btn-sm">쿠폰·멤버십 페이지로 →</a>
    </div>
  </section>
</div>

<style>
.op-tab.is-active { color: var(--brand-500) !important; border-bottom-color: var(--brand-500) !important; }
</style>
<script>
(function(){
  const tabs = document.querySelectorAll('.op-tab');
  const panes = document.querySelectorAll('.op-tab-pane');
  function activate(name) {
    tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
    panes.forEach(p => p.style.display = (p.dataset.pane === name ? '' : 'none'));
    history.replaceState(null, '', '#' + name);
  }
  tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.tab)));
  const initial = (location.hash || '#overview').slice(1);
  activate(document.querySelector('.op-tab[data-tab="' + initial + '"]') ? initial : 'overview');
})();
</script>
