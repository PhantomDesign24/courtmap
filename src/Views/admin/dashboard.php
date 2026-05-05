<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $stats */
/** @var array $daily */
/** @var array $topVenues */
/** @var array $byArea */
/** @var array $health */
function fmt_won(int $n): string { return number_format($n) . '원'; }
function rel_time(?int $ts): string {
    if (!$ts) return '없음';
    $diff = time() - $ts;
    if ($diff < 60) return '방금 전';
    if ($diff < 3600) return floor($diff / 60) . '분 전';
    if ($diff < 86400) return floor($diff / 3600) . '시간 전';
    return floor($diff / 86400) . '일 전';
}
?>
<header class="op-page-head">
  <h1>어드민 대시보드</h1>
  <p class="op-sub">코트맵 전체 운영 현황.</p>
</header>

<div class="op-kpi-grid">
  <a href="/admin/venues?tab=pending" class="op-kpi">
    <div class="op-kpi-label">구장 승인 대기</div>
    <div class="op-kpi-value"><?= (int)$stats['pending_venues'] ?><span>건</span></div>
    <div class="op-kpi-sub">검토하러 가기 →</div>
  </a>
  <a href="/admin/venues?tab=active" class="op-kpi">
    <div class="op-kpi-label">활성 구장</div>
    <div class="op-kpi-value"><?= (int)$stats['active_venues'] ?><span>곳</span></div>
  </a>
  <a href="/admin/users?role=user" class="op-kpi">
    <div class="op-kpi-label">활성 일반회원</div>
    <div class="op-kpi-value"><?= (int)$stats['active_users'] ?><span>명</span></div>
    <div class="op-kpi-sub">최근 7일 신규 +<?= (int)$stats['new_users_week'] ?></div>
  </a>
  <a href="/admin/users?role=operator" class="op-kpi">
    <div class="op-kpi-label">활성 운영자</div>
    <div class="op-kpi-value"><?= (int)$stats['active_operators'] ?><span>명</span></div>
  </a>
  <div class="op-kpi">
    <div class="op-kpi-label">오늘 예약</div>
    <div class="op-kpi-value"><?= (int)$stats['today_reservations'] ?><span>건</span></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">오늘 매출</div>
    <div class="op-kpi-value num"><?= $e(fmt_won((int)$stats['today_revenue'])) ?></div>
  </div>
  <a href="/admin/reports" class="op-kpi">
    <div class="op-kpi-label">최근 7일 노쇼</div>
    <div class="op-kpi-value"><?= (int)$stats['noshow_week'] ?><span>건</span></div>
  </a>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>매출/거래량 추이 (최근 30일)</h2></div>
  <div style="padding: 16px 18px;">
    <canvas id="chartRevenue" height="80"></canvas>
  </div>
</section>

<div class="op-grid-2-1" style="margin-bottom: 20px;">
  <section class="op-card" style="margin-bottom:0">
    <div class="op-card-head"><h2>인기 구장 TOP 10</h2></div>
    <?php if (!$topVenues): ?>
      <div class="op-empty">데이터 없음</div>
    <?php else: ?>
      <table class="op-table">
        <thead><tr><th>#</th><th>구장</th><th>지역</th><th>예약수</th><th>매출</th></tr></thead>
        <tbody>
          <?php foreach ($topVenues as $i => $v): ?>
            <tr>
              <td class="num fw-700"><?= $i + 1 ?></td>
              <td class="fw-600"><a href="/admin/venues/<?= (int)$v['id'] ?>" style="color:var(--text);text-decoration:none"><?= $e($v['name']) ?></a></td>
              <td class="op-mute"><?= $e($v['area']) ?></td>
              <td class="num"><?= (int)$v['cnt'] ?></td>
              <td class="num fw-600"><?= $e(fmt_won((int)$v['rev'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <section class="op-card" style="margin-bottom:0">
    <div class="op-card-head"><h2>지역별 분포</h2></div>
    <?php if (!$byArea): ?>
      <div class="op-empty">데이터 없음</div>
    <?php else: ?>
      <div style="padding:14px 18px">
        <?php $maxC = max(array_map(fn($r) => (int)$r['c'], $byArea)) ?: 1; ?>
        <?php foreach ($byArea as $r): ?>
          <div style="margin-bottom:8px">
            <div class="row" style="font-size:12.5px;margin-bottom:3px">
              <span class="fw-600"><?= $e($r['region']) ?></span>
              <div class="spacer"></div>
              <span class="num text-sub"><?= (int)$r['c'] ?>곳</span>
            </div>
            <div style="height:6px;background:var(--gray-100);border-radius:3px;overflow:hidden">
              <div style="height:100%;width:<?= round((int)$r['c'] / $maxC * 100) ?>%;background:var(--brand-500)"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>시스템 헬스</h2></div>
  <div class="op-grid-2" style="padding:16px 18px">
    <div>
      <div class="fw-700" style="font-size:13.5px;margin-bottom:10px">Cron 마지막 실행</div>
      <table style="width:100%;font-size:12.5px">
        <?php foreach ($health['cron'] as $name => $ts): ?>
          <tr>
            <td style="padding:4px 0;color:var(--text-sub)"><?= $e($name) ?></td>
            <td style="text-align:right" class="<?= $ts && (time() - $ts) < 7200 ? 'text-brand fw-600' : 'op-mute' ?>"><?= $e(rel_time($ts)) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <div>
      <div class="fw-700" style="font-size:13.5px;margin-bottom:10px">Webhook / DB</div>
      <table style="width:100%;font-size:12.5px">
        <tr><td style="padding:4px 0;color:var(--text-sub)">총 webhook</td><td style="text-align:right" class="num"><?= $health['webhook']['total'] ?></td></tr>
        <tr><td style="padding:4px 0;color:var(--text-sub)">실패 (status=failed)</td><td style="text-align:right" class="num <?= $health['webhook']['failed'] ? 'text-hot fw-700' : '' ?>"><?= $health['webhook']['failed'] ?></td></tr>
        <tr><td style="padding:4px 0;color:var(--text-sub)">실패 카운트 누적</td><td style="text-align:right" class="num"><?= $health['webhook']['recent_fail'] ?></td></tr>
        <tr><td style="padding:4px 0;color:var(--text-sub)">DB 응답</td><td style="text-align:right" class="num <?= $health['db_ms'] > 100 ? 'text-hot' : 'text-brand' ?>"><?= $health['db_ms'] ?>ms</td></tr>
      </table>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
  const data = <?= json_encode(array_map(static fn($r) => ['d' => $r['d'], 'rev' => (int)$r['rev'], 'cnt' => (int)$r['cnt']], $daily), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const map = {}; data.forEach(r => map[r.d] = r);
  const labels = []; const rev = []; const cnt = [];
  const today = new Date();
  for (let i = 29; i >= 0; i--) {
    const d = new Date(today); d.setDate(today.getDate() - i);
    const key = d.toISOString().slice(0, 10);
    labels.push((d.getMonth()+1) + '/' + d.getDate());
    rev.push(map[key] ? map[key].rev : 0);
    cnt.push(map[key] ? map[key].cnt : 0);
  }
  new Chart(document.getElementById('chartRevenue'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: '매출(원)', data: rev, backgroundColor: 'rgba(30,80,255,.8)', yAxisID: 'y' },
        { label: '예약수', data: cnt, type: 'line', borderColor: '#ff3b30', backgroundColor: 'rgba(255,59,48,.2)', yAxisID: 'y1', tension: 0.3 },
      ],
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      scales: {
        y:  { beginAtZero: true, position: 'left',  ticks: { callback: v => (v/10000).toFixed(0) + '만' } },
        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } },
      },
    },
  });
})();
</script>
