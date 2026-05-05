<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $stats */
/** @var array $upcoming */
/** @var array $heat */
/** @var array $daily */
function fmt_won(int $n): string { return number_format($n) . '원'; }

// 다음 7일 그룹핑 (date → list)
$byDay = [];
foreach ($upcoming as $r) $byDay[$r['reservation_date']][] = $r;
$kdays = ['일','월','화','수','목','금','토'];

// 히트맵 매트릭스 (court x hour)
$courts = [];
$hours  = [];
$cells  = []; // [court][hour] = count
foreach ($heat as $r) {
    $c = $r['court'];
    if (!in_array($c, $courts, true)) $courts[] = $c;
    $h = (int) $r['h'];
    if (!in_array($h, $hours, true)) $hours[] = $h;
    $cells[$c][$h] = (int) $r['cnt'];
}
sort($hours);
$maxCnt = 0;
foreach ($cells as $row) foreach ($row as $v) $maxCnt = max($maxCnt, $v);
?>
<header class="op-page-head">
  <h1>대시보드</h1>
  <p class="op-sub">오늘의 운영 현황과 최근 추세.</p>
</header>

<div class="op-kpi-grid">
  <a href="/operator/deposits" class="op-kpi">
    <div class="op-kpi-label">입금 대기</div>
    <div class="op-kpi-value"><?= (int)$stats['pending'] ?><span>건</span></div>
    <div class="op-kpi-sub">처리하러 가기 →</div>
  </a>
  <div class="op-kpi">
    <div class="op-kpi-label">오늘 확정</div>
    <div class="op-kpi-value"><?= (int)$stats['today_confirmed'] ?><span>건</span></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">오늘 매출</div>
    <div class="op-kpi-value num"><?= $e(fmt_won((int)$stats['today_revenue'])) ?></div>
  </div>
  <div class="op-kpi">
    <div class="op-kpi-label">활성 구장</div>
    <div class="op-kpi-value"><?= (int)$stats['venue_count'] ?><span>곳</span></div>
  </div>
</div>

<section class="op-card">
  <div class="op-card-head"><h2>매출 추이 (최근 30일)</h2></div>
  <div style="padding: 16px 18px;">
    <canvas id="chartRevenue" height="100"></canvas>
  </div>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>다음 7일 예약 <span class="op-pill"><?= count($upcoming) ?></span></h2></div>
  <?php if (!$upcoming): ?>
    <div class="op-empty">예정된 예약이 없습니다.</div>
  <?php else: ?>
    <div class="op-week-grid" style="padding: 12px 18px 18px;">
      <?php for ($i = 0; $i < 7; $i++):
        $d = date('Y-m-d', strtotime("+$i days"));
        $list = $byDay[$d] ?? [];
        $dayObj = new DateTime($d);
      ?>
        <div style="background: var(--gray-25); border-radius: 10px; padding: 10px; min-height: 200px;">
          <div style="font-size:11px;color:var(--text-sub);font-weight:600"><?= $e($kdays[(int)$dayObj->format('w')]) ?></div>
          <div style="font-size:18px;font-weight:800;letter-spacing:-0.5px"><?= (int)$dayObj->format('j') ?></div>
          <div style="margin-top:8px;display:flex;flex-direction:column;gap:4px">
            <?php foreach ($list as $r):
              $bg = $r['status'] === 'confirmed' ? 'var(--brand-50)' : '#fff8e6';
              $fg = $r['status'] === 'confirmed' ? 'var(--brand-700)' : '#7a5b00';
            ?>
              <div style="background:<?= $bg ?>;color:<?= $fg ?>;padding:5px 7px;border-radius:6px;font-size:11px;line-height:1.4">
                <strong><?= sprintf('%02d:00', (int)$r['start_hour']) ?></strong> <?= $e($r['court_name']) ?>
                <div style="opacity:0.85"><?= $e($r['user_name']) ?></div>
              </div>
            <?php endforeach; ?>
            <?php if (!$list): ?><div class="op-mute" style="font-size:11px">—</div><?php endif; ?>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>

<section class="op-card">
  <div class="op-card-head"><h2>시간×코트 히트맵 (최근 30일 확정)</h2></div>
  <?php if (!$courts): ?>
    <div class="op-empty">데이터가 없습니다.</div>
  <?php else: ?>
    <div style="padding: 16px 18px; overflow-x: auto;">
      <table style="border-collapse: collapse; width: 100%; min-width: 600px; font-size: 12px;">
        <thead>
          <tr>
            <th style="text-align:left;padding:6px 8px;font-weight:600;color:var(--text-sub)">시간 \ 코트</th>
            <?php foreach ($courts as $c): ?>
              <th style="padding:6px 8px;font-weight:600;color:var(--text-sub);text-align:center"><?= $e($c) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php for ($h = 10; $h <= 23; $h++): ?>
            <tr>
              <td style="padding:6px 8px;font-weight:600;color:var(--text-sub)" class="num"><?= sprintf('%02d:00', $h) ?></td>
              <?php foreach ($courts as $c):
                $cnt = $cells[$c][$h] ?? 0;
                $alpha = $maxCnt ? min(0.9, 0.1 + ($cnt / $maxCnt) * 0.85) : 0.1;
              ?>
                <td style="padding:6px;text-align:center;background:rgba(30,80,255,<?= number_format($alpha, 2) ?>);color:<?= $cnt > $maxCnt * 0.5 ? '#fff' : 'var(--text)' ?>;border-radius:4px;font-weight:600">
                  <?= $cnt ?: '·' ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
      <div class="op-mute" style="margin-top: 8px; font-size: 11px;">진한 색일수록 예약 많음 (최댓값 <?= $maxCnt ?>건)</div>
    </div>
  <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
  const data = <?= json_encode(array_map(static fn($r) => ['d' => $r['d'], 'rev' => (int)$r['rev'], 'cnt' => (int)$r['cnt']], $daily), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  // 빈 날짜 채우기 — 30일 grid
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
