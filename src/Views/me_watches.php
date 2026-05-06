<?php
use App\Core\View;
$e = static fn(?string $s): string => View::e($s);
/** @var array $active */
/** @var array $past */
/** @var array $alerts */
/** @var array $venues */
/** @var array $recommended */
$flashOk  = $flashOk  ?? null;
$flashErr = $flashErr ?? null;
$kdays = ['일','월','화','수','목','금','토'];
?>
<div class="auth-page">
  <div class="auth-card-side" style="flex:1;align-items:flex-start;padding-top:32px">
    <main class="auth" style="max-width:640px">
      <div class="auth-brand">
        <div class="auth-brand-mark">코</div>
        <div class="auth-brand-text">빈자리 알림</div>
      </div>
      <h1>원하는 시간대 알림 받기</h1>
      <p class="auth-sub">매주 같은 시간대(정기) 또는 특정 날짜 빈자리 알림. 누군가 취소하면 즉시 알림이 가요.</p>

      <?php if ($flashOk): ?>
        <div class="alert" style="background:#dcf6e8;color:#0a7e4a;border-color:#bbe7d0">✓ <?= $e($flashOk) ?></div>
      <?php endif; ?>
      <?php if ($flashErr): ?>
        <div class="alert"><?= $e($flashErr) ?></div>
      <?php endif; ?>

      <?php if ($recommended): ?>
        <div class="info-card" style="margin-bottom:14px">
          <div class="fw-700" style="font-size:13px;margin-bottom:8px">자주 가는 시간대 — 한 번에 등록</div>
          <div style="display:flex;flex-wrap:wrap;gap:6px">
            <?php foreach ($recommended as $r): ?>
              <form method="post" action="/me/watches" style="display:inline">
                <input type="hidden" name="venue_id"   value="<?= (int)$r['venue_id'] ?>">
                <input type="hidden" name="type"       value="recurring">
                <input type="hidden" name="day_of_week" value="<?= (int)$r['day_of_week'] ?>">
                <input type="hidden" name="start_hour" value="<?= (int)$r['start_hour'] ?>">
                <input type="hidden" name="end_hour"   value="<?= (int)$r['start_hour'] + 2 ?>">
                <input type="hidden" name="slot_unit_hours" value="1">
                <button type="submit" class="chip" style="cursor:pointer">
                  <?= $e($r['venue_name']) ?> · <?= $kdays[(int)$r['day_of_week']] ?> <?= sprintf('%02d:00', (int)$r['start_hour']) ?>
                  <span style="color:var(--text-mute);margin-left:4px">+ 알림</span>
                </button>
              </form>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <form method="post" action="/me/watches" class="auth-form" id="watchForm">
        <label>구장
          <select name="venue_id" required>
            <option value="">선택…</option>
            <?php foreach ($venues as $v): ?>
              <option value="<?= (int)$v['id'] ?>"><?= $e($v['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div style="display:flex;gap:8px">
          <label style="flex:1">알림 종류
            <select name="type" id="typeSelect" onchange="updateType()">
              <option value="recurring">정기 (매주)</option>
              <option value="one_time">단일 날짜</option>
            </select>
          </label>
          <label style="flex:1" id="dowField">요일
            <select name="day_of_week">
              <?php for ($i = 0; $i < 7; $i++): ?>
                <option value="<?= $i ?>"><?= $kdays[$i] ?>요일</option>
              <?php endfor; ?>
            </select>
          </label>
          <label style="flex:1;display:none" id="dateField">날짜
            <input type="date" name="target_date" min="<?= date('Y-m-d') ?>">
          </label>
        </div>
        <div style="display:flex;gap:8px">
          <label style="flex:1">시작 시각
            <select name="start_hour">
              <?php for ($h = 6; $h < 24; $h++): ?>
                <option value="<?= $h ?>" <?= $h === 19 ? 'selected' : '' ?>><?= sprintf('%02d:00', $h) ?></option>
              <?php endfor; ?>
            </select>
          </label>
          <label style="flex:1">종료 시각
            <select name="end_hour">
              <?php for ($h = 7; $h <= 24; $h++): ?>
                <option value="<?= $h ?>" <?= $h === 22 ? 'selected' : '' ?>><?= sprintf('%02d:00', $h) ?></option>
              <?php endfor; ?>
            </select>
          </label>
          <label style="flex:1">슬롯 단위
            <select name="slot_unit_hours">
              <option value="1">1시간</option>
              <option value="2">2시간</option>
              <option value="3">3시간</option>
            </select>
          </label>
        </div>
        <label>메모 (선택)<input type="text" name="note" maxlength="120" placeholder="예: 친구랑 토요일 정기"></label>
        <button type="submit" class="btn btn-primary btn-block">알림 등록</button>
      </form>

      <?php if ($active): ?>
        <h2 style="font-size:15px;font-weight:700;margin:28px 0 10px">활성 알림 <span class="op-pill" style="background:var(--brand-50);color:var(--brand-700);padding:2px 8px;border-radius:999px;font-size:11.5px"><?= count($active) ?></span></h2>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($active as $w): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid var(--line);border-radius:10px;background:#fff">
              <div style="font-size:13px">
                <div class="fw-700"><?= $e($w['venue_name']) ?> <?= $w['court_name'] ? '· ' . $e($w['court_name']) : '<span style="color:var(--text-mute);font-weight:500;font-size:11.5px">전체 코트</span>' ?></div>
                <div style="color:var(--text-sub);font-size:12px;margin-top:2px">
                  <?php if ($w['type'] === 'recurring'): ?>
                    매주 <?= $kdays[(int)$w['day_of_week']] ?>요일
                  <?php else: ?>
                    <?= $e($w['target_date']) ?>
                  <?php endif; ?>
                  · <?= sprintf('%02d:00 ~ %02d:00', (int)$w['start_hour'], (int)$w['end_hour']) ?>
                  · <?= (int)$w['slot_unit_hours'] ?>시간 슬롯
                  <?php if (!empty($w['note'])): ?>· <?= $e($w['note']) ?><?php endif; ?>
                </div>
              </div>
              <form method="post" action="/me/watches/<?= (int)$w['id'] ?>/delete" onsubmit="return confirm('이 알림을 끌까요?');" style="display:inline">
                <button type="submit" class="btn btn-line btn-sm">취소</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($alerts): ?>
        <h2 style="font-size:15px;font-weight:700;margin:28px 0 10px">최근 알림 이력</h2>
        <div style="display:flex;flex-direction:column;gap:6px">
          <?php foreach ($alerts as $a): ?>
            <div style="font-size:12.5px;padding:8px 12px;background:var(--gray-25);border-radius:8px;color:var(--text-sub)">
              <span class="fw-600" style="color:var(--text)"><?= $e($a['venue_name']) ?> <?= $e($a['court_name'] ?? '') ?></span>
              · <?= $e($a['slot_date']) ?> <?= sprintf('%02d:00', (int)$a['slot_start_hour']) ?>
              · <?= $e(substr((string)$a['sent_at'], 5, 11)) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($past): ?>
        <h2 style="font-size:13.5px;font-weight:700;margin:24px 0 10px;color:var(--text-sub)">완료/만료된 알림</h2>
        <div style="display:flex;flex-direction:column;gap:4px;font-size:11.5px;color:var(--text-mute)">
          <?php foreach ($past as $w): ?>
            <div><?= $e($w['venue_name']) ?> · <?= $e($w['type']==='recurring' ? '매주 ' . $kdays[(int)$w['day_of_week']] : (string)$w['target_date']) ?> · <?= $e($w['status']) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <p class="auth-link"><a href="/me">← 마이로 돌아가기</a></p>
    </main>
  </div>
</div>

<script>
function updateType() {
  const t = document.getElementById('typeSelect').value;
  document.getElementById('dowField').style.display  = (t === 'recurring') ? '' : 'none';
  document.getElementById('dateField').style.display = (t === 'one_time')  ? '' : 'none';
}
updateType();
</script>
