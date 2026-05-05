<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var string $from */
/** @var string $to */
/** @var array $kpi */
/** @var array $daily */
/** @var array $byVenue */
/** @var array $pendingDeposits */
/** @var array $refundQueue */
$st_label = ['user'=>'회원','operator'=>'운영자','system'=>'시스템'];
?>
<header class="op-page-head">
  <h1>재무·환불</h1>
  <p class="op-sub">기간별 매출·환불·입금 대기 모니터링.</p>
</header>

<form method="get" class="op-filter-row" style="margin-bottom:16px;display:flex;gap:8px;align-items:center">
  <label>기간 시작 <input type="date" name="from" value="<?= $e($from) ?>"></label>
  <label>종료 <input type="date" name="to" value="<?= $e($to) ?>"></label>
  <button type="submit" class="btn btn-primary btn-sm">조회</button>
  <span class="op-mute" style="font-size:12px">기본: 최근 30일</span>
</form>

<div class="op-kpi-grid" style="margin-bottom:18px">
  <div class="op-kpi"><div class="op-kpi-label">총 예약</div><div class="op-kpi-value"><?= (int)$kpi['cnt'] ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">총 거래액</div><div class="op-kpi-value num"><?= number_format((int)$kpi['gross']) ?><span>원</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">환불 합계</div><div class="op-kpi-value num"><?= number_format((int)$kpi['refunds']) ?><span>원</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">확정/완료</div><div class="op-kpi-value"><?= (int)$kpi['confirmed'] ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">취소</div><div class="op-kpi-value"><?= (int)$kpi['canceled'] ?><span>건</span></div></div>
  <div class="op-kpi"><div class="op-kpi-label">노쇼</div><div class="op-kpi-value"><?= (int)$kpi['noshow'] ?><span>건</span></div></div>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>일자별 추이</h2></div>
  <div style="padding:16px 18px">
    <canvas id="chartFinance" height="80"></canvas>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>구장별 매출 TOP 30</h2></div>
  <?php if (!$byVenue): ?>
    <div class="op-empty">데이터 없음</div>
  <?php else: ?>
    <table class="op-table">
      <thead><tr><th>구장</th><th>지역</th><th>예약</th><th>매출</th><th>환불</th><th>순매출</th></tr></thead>
      <tbody>
        <?php foreach ($byVenue as $v): ?>
          <tr>
            <td class="fw-600"><a href="/admin/venues/<?= (int)$v['id'] ?>" style="color:var(--text);text-decoration:none"><?= $e($v['name']) ?></a></td>
            <td class="op-mute"><?= $e($v['area']) ?></td>
            <td class="num"><?= (int)$v['cnt'] ?></td>
            <td class="num fw-600"><?= number_format((int)$v['rev']) ?></td>
            <td class="num text-hot"><?= (int)$v['refunds'] > 0 ? '-' . number_format((int)$v['refunds']) : '—' ?></td>
            <td class="num fw-700"><?= number_format((int)$v['rev'] - (int)$v['refunds']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
  <section class="op-card" style="margin-bottom:0">
    <div class="op-card-head"><h2>입금 대기 <span class="op-pill"><?= count($pendingDeposits) ?></span></h2></div>
    <?php if (!$pendingDeposits): ?>
      <div class="op-empty">대기 중인 입금이 없습니다.</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>코드</th><th>구장 / 회원</th><th>마감</th><th>금액</th></tr></thead>
        <tbody>
          <?php foreach ($pendingDeposits as $r):
            $due = strtotime((string)$r['deposit_due_at']);
            $overdue = $due && $due < time();
          ?>
            <tr>
              <td class="num fw-600"><a href="/reservations/<?= $e($r['code']) ?>" target="_blank"><?= $e($r['code']) ?></a></td>
              <td>
                <div class="fw-600"><?= $e($r['venue_name']) ?></div>
                <div class="op-mute"><?= $e($r['user_name']) ?> · <?= $e($r['user_phone']) ?></div>
              </td>
              <td class="<?= $overdue?'text-hot fw-600':'op-mute' ?>"><?= $e(substr((string)$r['deposit_due_at'], 5, 11)) ?></td>
              <td class="num fw-600"><?= number_format((int)$r['total_price']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <section class="op-card" style="margin-bottom:0">
    <div class="op-card-head"><h2>환불 큐 <span class="op-pill"><?= count($refundQueue) ?></span></h2></div>
    <?php if (!$refundQueue): ?>
      <div class="op-empty">최근 환불이 없습니다.</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>코드</th><th>구장 / 회원</th><th>취소시각</th><th>환불금</th><th>주체</th></tr></thead>
        <tbody>
          <?php foreach ($refundQueue as $r): ?>
            <tr>
              <td class="num fw-600"><a href="/reservations/<?= $e($r['code']) ?>" target="_blank"><?= $e($r['code']) ?></a></td>
              <td>
                <div class="fw-600"><?= $e($r['venue_name']) ?></div>
                <div class="op-mute"><?= $e($r['user_name']) ?> · <?= $e($r['user_phone']) ?></div>
              </td>
              <td class="op-mute"><?= $e(substr((string)$r['canceled_at'], 5, 11)) ?></td>
              <td class="num fw-700 text-hot"><?= number_format((int)$r['refund_amount']) ?></td>
              <td><span class="badge badge-soft"><?= $e($st_label[$r['canceled_by']] ?? $r['canceled_by']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
  const data = <?= json_encode(array_map(static fn($r) => ['d'=>$r['d'],'cnt'=>(int)$r['cnt'],'rev'=>(int)$r['rev'],'refunds'=>(int)$r['refunds']], $daily), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  new Chart(document.getElementById('chartFinance'), {
    type: 'bar',
    data: {
      labels: data.map(r => r.d.slice(5)),
      datasets: [
        { label:'매출', data: data.map(r=>r.rev), backgroundColor: 'rgba(30,80,255,.8)', yAxisID:'y' },
        { label:'환불', data: data.map(r=>-r.refunds), backgroundColor: 'rgba(255,59,48,.8)', yAxisID:'y' },
        { label:'예약수', data: data.map(r=>r.cnt), type:'line', borderColor:'#0a7e4a', yAxisID:'y1', tension:0.3 },
      ],
    },
    options: {
      responsive: true,
      interaction: { mode:'index', intersect:false },
      scales: {
        y:  { beginAtZero:true, position:'left',  ticks:{ callback: v => (v/10000).toFixed(0)+'만' } },
        y1: { beginAtZero:true, position:'right', grid:{ drawOnChartArea:false } },
      },
    },
  });
})();
</script>
