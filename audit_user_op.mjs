// 사용자 + 운영자 시나리오 종단간 자동 테스트
import { chromium } from 'playwright';
import fs from 'fs';
const SHOTS = '/tmp/cm_audit'; fs.mkdirSync(SHOTS, { recursive: true });
const BASE = 'https://bad.mvc.kr';

const issues = [];
function log(label, info) { console.log(`[${label}]`, info); }

async function login(page, email, password) {
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.click('button[type="submit"][class*="primary"]')]);
}

function attachWatchers(page, label) {
  page.on('pageerror', e => issues.push(`[${label}] PAGEERROR: ${e.message}`));
  page.on('console', m => { if (m.type() === 'error') issues.push(`[${label}] CONSOLE: ${m.text().slice(0, 200)}`); });
  page.on('response', r => { if (r.status() >= 400) issues.push(`[${label}] HTTP ${r.status()}: ${r.url()}`); });
}

(async () => {
  const browser = await chromium.launch();

  // ─── 사용자 시나리오 ───
  const ctx = await browser.newContext({ viewport: { width: 480, height: 900 }, ignoreHTTPSErrors: true });
  const page = await ctx.newPage();
  attachWatchers(page, 'USER');

  await login(page, 'test1@bad.mvc.kr', 'test12345');
  log('user', 'login ok');

  // 홈
  await page.goto(`${BASE}/`, { waitUntil: 'networkidle' }); await page.waitForTimeout(1000);
  await page.screenshot({ path: `${SHOTS}/u1_home.png` });

  // 시간 칩 클릭
  for (const range of ['1h', '2h', 'today', 'wk']) {
    const chip = page.locator(`.chip-time[data-time-range="${range}"]`);
    if (await chip.count() > 0) {
      await chip.first().click();
      await page.waitForTimeout(800);
      log('chip', `${range} 클릭 OK`);
    }
  }

  // 위치 변경 sheet
  await page.locator('button:has-text("강남구 역삼동")').first().click().catch(() => {});
  await page.waitForTimeout(500);
  await page.screenshot({ path: `${SHOTS}/u2_location_sheet.png` });
  await page.keyboard.press('Escape').catch(() => {});

  // 검색
  await page.goto(`${BASE}/search`, { waitUntil: 'networkidle' }); await page.waitForTimeout(500);
  await page.fill('input[placeholder*="구장"]', '강남');
  await page.waitForTimeout(800);
  await page.screenshot({ path: `${SHOTS}/u3_search.png` });

  // 구장 상세
  await page.goto(`${BASE}/venues/1`, { waitUntil: 'networkidle' }); await page.waitForTimeout(1500);
  await page.screenshot({ path: `${SHOTS}/u4_venue_detail.png` });

  // 레슨 탭
  const lessonTab = page.locator('button:has-text("레슨")');
  if (await lessonTab.count() > 0) {
    await lessonTab.first().click(); await page.waitForTimeout(500);
    await page.screenshot({ path: `${SHOTS}/u5_lesson_tab.png` });
  }

  // 시설정보
  const infoTab = page.locator('button:has-text("시설 정보")');
  if (await infoTab.count() > 0) {
    await infoTab.first().click(); await page.waitForTimeout(500);
    await page.screenshot({ path: `${SHOTS}/u6_info_tab.png` });
  }

  // 마이
  await page.goto(`${BASE}/me`, { waitUntil: 'networkidle' }); await page.waitForTimeout(800);
  await page.screenshot({ path: `${SHOTS}/u7_me.png` });

  // 단골 / 쿠폰 / 멤버십 메뉴
  for (const txt of ['단골 구장', '쿠폰함', '내 멤버십']) {
    const btn = page.locator(`button:has-text("${txt}")`);
    if (await btn.count() > 0) {
      await btn.first().click(); await page.waitForTimeout(500);
      await page.screenshot({ path: `${SHOTS}/u8_${txt.replace(/\s/g,'_')}.png` });
      const back = page.locator('button:has(svg)').first();
      if (await back.count() > 0) await back.click().catch(() => {});
      await page.waitForTimeout(300);
    }
  }

  // 알림함
  await page.goto(`${BASE}/notifications`, { waitUntil: 'networkidle' }); await page.waitForTimeout(500);
  await page.screenshot({ path: `${SHOTS}/u9_notifications.png` });

  // 정기예약 폼
  await page.goto(`${BASE}/recurring/new`, { waitUntil: 'networkidle' }); await page.waitForTimeout(500);
  await page.screenshot({ path: `${SHOTS}/u10_recurring.png` });

  // 고객센터
  await page.goto(`${BASE}/support`, { waitUntil: 'networkidle' }); await page.waitForTimeout(500);
  await page.screenshot({ path: `${SHOTS}/u11_support.png` });

  await ctx.close();

  // ─── 운영자 시나리오 ───
  const ctx2 = await browser.newContext({ viewport: { width: 1440, height: 900 }, ignoreHTTPSErrors: true });
  const op = await ctx2.newPage();
  attachWatchers(op, 'OP');

  await login(op, 'operator@bad.mvc.kr', 'operator1234');
  log('op', 'login ok');

  for (const path of ['/operator', '/operator/deposits', '/operator/bookings', '/operator/slots', '/operator/pricing', '/operator/coupons', '/operator/equipment', '/operator/coaches', '/operator/api', '/operator/venues', '/operator/venues/1/edit', '/operator/venues/new']) {
    await op.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    await op.waitForTimeout(500);
    const fname = path.replace(/\//g, '_').replace(/^_/, '') || 'home';
    await op.screenshot({ path: `${SHOTS}/op_${fname}.png` });
    log('op page', path);
  }

  await ctx2.close();
  await browser.close();

  console.log('\n=== ISSUES (' + issues.length + ') ===');
  issues.forEach(i => console.log(' - ' + i));
})();
