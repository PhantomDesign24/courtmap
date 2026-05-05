// 코트맵 프론트 엔트리 — Vite 가 IIFE 단일 번들로 빌드.
import React from 'react';
import { createRoot } from 'react-dom/client';

// 각 스크린은 `Object.assign(window, {...})` 로 컴포넌트를 전역 노출 (side-effect import)
import './screens/shared.jsx';
import './screens/U1_home.jsx';
import './screens/U2_map.jsx';
import './screens/U3_U4_detail_reserve.jsx';
import './screens/U5_U6_payment.jsx';
import './screens/U7_my_reservations.jsx';
import './screens/extras.jsx';
import './screens/profile.jsx';

// 전역에서 가져오기 (JSX <Mobile/> 등으로 사용하기 위함)
const {
  Mobile, VENUES,
  HomeDealFirst, HomeListFirst,
  U3Detail, U4ReserveSheet,
  U5Deposit, U6Complete,
  U7MyReservations,
  LocationSheet, NotificationList, SearchScreen, EntryGuide, ReservationDetail,
  ProfileScreen,
} = window;

// PHP 가 보낸 venues 로 shared.jsx 의 VENUES 를 교체
if (window.__DATA__ && Array.isArray(window.__DATA__.venues) && window.__DATA__.venues.length > 0) {
  VENUES.splice(0, VENUES.length, ...window.__DATA__.venues);
}

function nav(path) { window.location.href = path; }

function App() {
  const screen = window.__SCREEN__ || 'home';

  if (screen === 'home') {
    return <HomeWithFilters />;
  }

  if (screen === 'venues_list') {
    return (
      <Mobile tab="search" onNav={(id) => {
        if (id === 'home')   nav('/');
        if (id === 'search') nav('/venues');
        if (id === 'ticket') nav('/me/reservations');
        if (id === 'user')   nav('/me');
      }}>
        <HomeListFirst
          location={(window.__DATA__ && window.__DATA__.location) || '강남구 역삼동'}
          onVenue={(id) => nav('/venues/' + id)}
        />
      </Mobile>
    );
  }

  if (screen === 'venue_detail') {
    return <VenueDetailWithSheet />;
  }

  if (screen === 'reservation_deposit') {
    const b = window.__DATA__.booking;
    return (
      <Mobile hideTab>
        <U5Deposit
          booking={b}
          onBack={() => nav('/venues/' + b.venue.id)}
          onPaid={() => {
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = '/reservations/' + b.code + '/mark-paid';
            document.body.appendChild(f);
            f.submit();
          }}
        />
      </Mobile>
    );
  }

  if (screen === 'notifications') {
    return (
      <Mobile hideTab>
        <NotificationList
          items={window.__DATA__.notifications || []}
          onBack={() => window.history.back()}
          onVenue={(id) => nav('/venues/' + id)}
        />
      </Mobile>
    );
  }

  if (screen === 'search') {
    return (
      <Mobile hideTab>
        <SearchScreen
          onBack={() => window.history.back()}
          onVenue={(id) => nav('/venues/' + id)}
        />
      </Mobile>
    );
  }

  if (screen === 'entry') {
    return (
      <Mobile hideTab>
        <EntryGuide
          reservation={window.__DATA__.reservation}
          onBack={() => window.history.back()}
        />
      </Mobile>
    );
  }

  if (screen === 'me') {
    return (
      <Mobile tab="user" onNav={(id) => {
        if (id === 'home')   nav('/');
        if (id === 'search') nav('/venues');
        if (id === 'ticket') nav('/me/reservations');
        if (id === 'user')   nav('/me');
      }}>
        <ProfileScreen />
      </Mobile>
    );
  }

  if (screen === 'my_reservations') {
    return (
      <Mobile tab="ticket" onNav={(id) => {
        if (id === 'home')   nav('/');
        if (id === 'search') nav('/venues');
        if (id === 'ticket') nav('/me/reservations');
        if (id === 'user')   nav('/me');
      }}>
        <U7MyReservations
          upcoming={window.__DATA__.upcoming || []}
          past={window.__DATA__.past || []}
          onVenue={(id) => nav('/venues/' + id)}
          onReservation={(r) => nav('/reservations/' + (r.code || r.id))}
          onEntry={(r) => nav('/reservations/' + (r.code || r.id) + '/entry')}
          onCreateRecurring={() => nav('/recurring/new')}
        />
      </Mobile>
    );
  }

  if (screen === 'reservation_complete') {
    const b = window.__DATA__.booking;
    return (
      <Mobile hideTab>
        <U6Complete
          booking={b}
          onHome={() => nav('/')}
          onMyReservations={() => nav('/me/reservations')}
          onEntry={() => nav('/reservations/' + b.code + '/entry')}
        />
      </Mobile>
    );
  }

  if (screen === 'reservation_detail') {
    const b = window.__DATA__.booking || {};
    const md = b.day && b.day.date
      ? `${parseInt(b.day.date.slice(5,7), 10)}월 ${parseInt(b.day.date.slice(8,10), 10)}일`
      : `${b.day?.day}일`;
    const r = {
      id:           b.code,
      code:         b.code,
      status:       b.status,
      venue:        b.venue,
      day:          `${md} (${b.day?.dow})`,
      time:         `${String(b.hour).padStart(2,'0')}:00 ~ ${String(b.hour + b.duration).padStart(2,'0')}:00`,
      court:        b.court_name,
      price:        b.total,
      basePrice:    b.basePrice,
      extrasPrice:  b.extrasPrice,
      discountPct:  b.discountPct,
      refundPolicy: b.refundPolicy,
      recurring:    false,
    };
    return (
      <Mobile hideTab>
        <ReservationDetail
          reservation={r}
          onBack={() => nav('/me/reservations')}
          onEntry={() => nav('/reservations/' + b.code + '/entry')}
          onVenue={(id) => nav('/venues/' + id)}
        />
      </Mobile>
    );
  }

  if (screen === 'recurring_new') {
    return <RecurringNewForm />;
  }

  if (screen === 'support') {
    return <SupportScreen page={(window.__DATA__ && window.__DATA__.page) || 'index'} />;
  }

  return <div style={{padding:24}}>알 수 없는 화면: {screen}</div>;
}

// 고객센터 (FAQ + 공지 + 약관)
function SupportScreen({ page }) {
  const data = window.__DATA__ || {};
  const [openIdx, setOpenIdx] = React.useState(-1);

  return (
    <Mobile hideTab>
      <div style={{ padding: '10px 8px 8px', background: '#fff', display: 'flex', alignItems: 'center', borderBottom: '1px solid var(--line)' }}>
        <button type="button" onClick={() => window.history.back()} style={{ background: 'none', border: 'none', padding: 8 }}>{I.back(22)}</button>
        <div style={{ flex: 1, fontSize: 17, fontWeight: 700 }}>
          {page === 'terms' ? '이용약관' : page === 'privacy' ? '개인정보처리방침' : '고객센터'}
        </div>
      </div>

      <div style={{ background: 'var(--bg-soft)', padding: 12, minHeight: '100%' }}>
        {page === 'index' && (
          <>
            <div className="card" style={{ background: '#fff', padding: 16, marginBottom: 12 }}>
              <div className="fw-700" style={{ fontSize: 14, marginBottom: 6 }}>고객센터 운영시간</div>
              <div className="text-sub" style={{ fontSize: 13, lineHeight: 1.6 }}>
                평일 10:00 ~ 18:00 (주말·공휴일 휴무)<br/>
                전화 1588-0000 · 이메일 help@bad.mvc.kr
              </div>
            </div>

            <div className="card" style={{ background: '#fff', padding: 0, marginBottom: 12 }}>
              <div className="fw-700" style={{ fontSize: 14, padding: '14px 16px 6px' }}>자주 묻는 질문</div>
              {(data.faqs || []).map((f, i) => (
                <div key={i} style={{ borderTop: i ? '1px solid var(--line)' : 'none' }}>
                  <button type="button" onClick={() => setOpenIdx(openIdx === i ? -1 : i)}
                    style={{ width: '100%', display: 'flex', alignItems: 'center', padding: '14px 16px', background: 'none', border: 'none', textAlign: 'left', cursor: 'pointer' }}>
                    <span className="fw-600" style={{ flex: 1, fontSize: 13.5 }}>Q. {f.q}</span>
                    <span className="text-sub" style={{ fontSize: 18, fontWeight: 300 }}>{openIdx === i ? '−' : '+'}</span>
                  </button>
                  {openIdx === i && (
                    <div style={{ padding: '0 16px 14px', fontSize: 13, color: 'var(--text-sub)', lineHeight: 1.7 }}>
                      A. {f.a}
                    </div>
                  )}
                </div>
              ))}
            </div>

            <div className="card" style={{ background: '#fff', padding: 0, marginBottom: 12 }}>
              <div className="fw-700" style={{ fontSize: 14, padding: '14px 16px 6px' }}>공지사항</div>
              {(data.notices || []).map((n, i) => (
                <div key={i} style={{ padding: '12px 16px', borderTop: i ? '1px solid var(--line)' : 'none' }}>
                  <div className="row" style={{ marginBottom: 4 }}>
                    <div className="fw-600" style={{ fontSize: 13.5 }}>{n.title}</div>
                    <div className="spacer" />
                    <div className="text-sub" style={{ fontSize: 11 }}>{n.date}</div>
                  </div>
                  <div className="text-sub" style={{ fontSize: 12.5, lineHeight: 1.6 }}>{n.body}</div>
                </div>
              ))}
            </div>

            <div className="card" style={{ background: '#fff', padding: '4px 0', marginBottom: 12 }}>
              <a href="/support/terms" style={{ display: 'block', padding: '14px 16px', borderBottom: '1px solid var(--line)', textDecoration: 'none', color: 'var(--text)', fontSize: 13.5, fontWeight: 600 }}>이용약관 →</a>
              <a href="/support/privacy" style={{ display: 'block', padding: '14px 16px', textDecoration: 'none', color: 'var(--text)', fontSize: 13.5, fontWeight: 600 }}>개인정보처리방침 →</a>
            </div>
          </>
        )}

        {page === 'terms' && (
          <div className="card" style={{ background: '#fff', padding: 18, fontSize: 13.5, lineHeight: 1.7, color: 'var(--text)' }}>
            <h3 style={{ fontSize: 15, marginTop: 0 }}>제1조 (목적)</h3>
            <p>본 약관은 코트맵(이하 "회사")이 제공하는 배드민턴 사설 구장 통합 예약 서비스(이하 "서비스")의 이용 조건과 절차를 규정합니다.</p>
            <h3 style={{ fontSize: 15 }}>제2조 (이용계약)</h3>
            <p>이용자는 회원가입 시 본 약관에 동의함으로써 회사와 이용계약을 체결합니다.</p>
            <h3 style={{ fontSize: 15 }}>제3조 (예약 및 결제)</h3>
            <p>예약은 무통장입금으로 결제하며, 입금기한 초과 시 자동 취소됩니다. 환불 정책은 구장별 정책에 따르며, 가입 시 등록한 본인 계좌로 송금됩니다.</p>
            <h3 style={{ fontSize: 15 }}>제4조 (책임)</h3>
            <p>회사는 구장과 이용자 간 중개자 역할을 하며, 시설 이용 중 발생한 사고에 대한 책임은 구장에 있습니다.</p>
            <p className="text-sub" style={{ fontSize: 12 }}>최종 개정일: 2026-05-05</p>
          </div>
        )}

        {page === 'privacy' && (
          <div className="card" style={{ background: '#fff', padding: 18, fontSize: 13.5, lineHeight: 1.7, color: 'var(--text)' }}>
            <h3 style={{ fontSize: 15, marginTop: 0 }}>1. 수집하는 개인정보</h3>
            <p>회원가입 시: 이메일, 전화번호, 이름, 비밀번호(암호화), 환불 받을 본인 계좌(은행/계좌번호/예금주).</p>
            <h3 style={{ fontSize: 15 }}>2. 이용 목적</h3>
            <p>회원 식별, 예약 관리, 환불 처리, 알림 발송.</p>
            <h3 style={{ fontSize: 15 }}>3. 보유 기간</h3>
            <p>회원 탈퇴 시 즉시 파기. 단, 관계 법령에 따라 일정 기간 보관 (예: 전자상거래법 5년).</p>
            <h3 style={{ fontSize: 15 }}>4. 제3자 제공</h3>
            <p>예약 처리를 위해 해당 구장 운영자에게 예약자명·전화번호·결제정보를 제공합니다.</p>
            <p className="text-sub" style={{ fontSize: 12 }}>최종 개정일: 2026-05-05</p>
          </div>
        )}
      </div>
    </Mobile>
  );
}

// 홈 — 위치 변경 + 시간 필터 칩 통합 wrapper
function HomeWithFilters() {
  const [location, setLocation] = React.useState((window.__DATA__ && window.__DATA__.location) || '강남구 역삼동');
  const [showLoc, setShowLoc]   = React.useState(false);
  const [hasUnread, setHasUnread] = React.useState(false);
  const [, force]               = React.useState(0);

  React.useEffect(() => {
    if (window.__USER__) {
      fetch('/api/me/unread', { credentials: 'same-origin' }).then(r => r.json()).then(d => setHasUnread((d.count || 0) > 0)).catch(() => {});
    }
  }, []);

  // 시간 칩 변경 또는 위치 변경 → API 재조회 (VENUES splice)
  async function refresh(range, area) {
    const params = new URLSearchParams();
    if (range && range !== 'now') params.set('range', range);
    if (area)                     params.set('area', area);
    try {
      const res = await fetch('/api/venues?' + params.toString(), { credentials: 'same-origin' });
      const data = await res.json();
      if (Array.isArray(data.venues)) {
        VENUES.splice(0, VENUES.length, ...data.venues);
        force(x => x + 1);
      }
    } catch (e) {}
  }

  async function pickLocation(area) {
    setLocation(area);
    setShowLoc(false);
    await fetch('/api/me/location', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ area }),
    });
    refresh(null, area);
  }

  // 시간칩만 매칭 (.chip-time, data-time-range)
  React.useEffect(() => {
    const handler = (e) => {
      const chip = e.target.closest('.chip-time');
      if (!chip) return;
      const map = { now: 'now', '1h': '1h', '2h': '2h', today: 'today', wk: 'weekend' };
      const range = map[chip.dataset.timeRange];
      if (range) refresh(range, location);
    };
    document.addEventListener('click', handler);
    return () => document.removeEventListener('click', handler);
  }, [location]);

  return (
    <Mobile tab="home" onNav={(id) => {
      if (id === 'home')   nav('/');
      if (id === 'search') nav('/venues');
      if (id === 'ticket') nav('/me/reservations');
      if (id === 'user')   nav('/me');
    }}>
      <HomeDealFirst
        location={location}
        hasUnread={hasUnread}
        onVenue={(id) => nav('/venues/' + id)}
        onSearch={() => nav('/search')}
        onLocation={() => setShowLoc(true)}
        onNotif={() => nav('/notifications')}
      />
      {showLoc && (
        <LocationSheet
          current={location}
          onClose={() => setShowLoc(false)}
          onPick={pickLocation}
        />
      )}
    </Mobile>
  );
}

function RecurringNewForm() {
  const venues = (window.__DATA__ && window.__DATA__.venues) || [];
  const [venueId, setVenueId] = React.useState(venues[0]?.id || '');
  const [courts, setCourts] = React.useState([]);

  React.useEffect(() => {
    if (!venueId) return;
    const today = new Date();
    const dateStr = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');
    fetch(`/api/venues/${venueId}/slots?date=${dateStr}`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(d => setCourts(d.courts || []))
      .catch(() => setCourts([]));
  }, [venueId]);

  return (
    <Mobile hideTab>
      <div style={{ padding: '16px 16px 8px', background: '#fff', borderBottom: '1px solid var(--line)' }}>
        <div style={{ fontSize: 20, fontWeight: 800, letterSpacing: '-0.5px' }}>정기 예약 만들기</div>
        <div className="text-sub" style={{ fontSize: 12, marginTop: 4 }}>매주 같은 시간 자동으로 예약 신청</div>
      </div>
      <form method="post" action="/recurring" style={{ padding: 16, display: 'flex', flexDirection: 'column', gap: 14 }}>
        <label style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 13, fontWeight: 600 }}>구장
          <select name="venue_id" value={venueId} onChange={e => setVenueId(parseInt(e.target.value, 10))} required style={{ height: 44, padding: '0 12px', borderRadius: 10, border: '1px solid var(--line-strong)' }}>
            {venues.map(v => <option key={v.id} value={v.id}>{v.name}</option>)}
          </select>
        </label>
        <label style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 13, fontWeight: 600 }}>코트
          <select name="court_id" required style={{ height: 44, padding: '0 12px', borderRadius: 10, border: '1px solid var(--line-strong)' }}>
            {courts.map(c => <option key={c.court_id} value={c.court_id}>{c.name}</option>)}
          </select>
        </label>
        <label style={{ display: 'flex', flexDirection: 'column', gap: 6, fontSize: 13, fontWeight: 600 }}>요일
          <select name="day_of_week" required style={{ height: 44, padding: '0 12px', borderRadius: 10, border: '1px solid var(--line-strong)' }}>
            {['일','월','화','수','목','금','토'].map((d, i) => <option key={i} value={i}>{d}요일</option>)}
          </select>
        </label>
        <div style={{ display: 'flex', gap: 8 }}>
          <label style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 6, fontSize: 13, fontWeight: 600 }}>시작 시각
            <input name="start_hour" type="number" min="0" max="23" defaultValue="19" required style={{ height: 44, padding: '0 12px', borderRadius: 10, border: '1px solid var(--line-strong)' }}/>
          </label>
          <label style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 6, fontSize: 13, fontWeight: 600 }}>이용 시간
            <select name="duration_hours" required style={{ height: 44, padding: '0 12px', borderRadius: 10, border: '1px solid var(--line-strong)' }}>
              <option value="1">1시간</option><option value="2">2시간</option><option value="3">3시간</option>
            </select>
          </label>
          <label style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 6, fontSize: 13, fontWeight: 600 }}>주 수
            <select name="week_count" required style={{ height: 44, padding: '0 12px', borderRadius: 10, border: '1px solid var(--line-strong)' }}>
              <option value="2">2주</option><option value="4">4주</option><option value="8">8주</option>
            </select>
          </label>
        </div>
        <button type="submit" className="btn btn-primary btn-lg btn-block" style={{ marginTop: 8 }}>정기 예약 신청</button>
        <button type="button" onClick={() => nav('/me/reservations')} className="btn btn-line btn-md btn-block">취소</button>
      </form>
    </Mobile>
  );
}

function VenueDetailWithSheet() {
  const [sel, setSel] = React.useState(null);
  const [equipmentList, setEquipmentList] = React.useState([]);
  const courtsList = (window.__DATA__ && window.__DATA__.courts) || [];

  React.useEffect(() => {
    const vid = window.__DATA__ && window.__DATA__.venueId;
    if (!vid) return;
    fetch(`/api/venues/${vid}/equipment`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(d => setEquipmentList(d.items || []))
      .catch(() => {});
  }, []);

  function fmtDate(d) {
    return d.getFullYear() + '-'
         + String(d.getMonth() + 1).padStart(2, '0') + '-'
         + String(d.getDate()).padStart(2, '0');
  }

  async function favToggle(next) {
    try {
      const res = await fetch('/api/favorites/' + window.__DATA__.venueId + '/toggle', {
        method: 'POST', credentials: 'same-origin',
      });
      if (res.status === 401) {
        window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname);
      }
    } catch (e) {}
  }

  async function submit(b) {
    // 멀티코트: b.courtIds (배열) 우선, 없으면 단일 sel.courtId
    const courtIds = (b.courtIds && b.courtIds.length) ? b.courtIds : [sel.courtId].filter(Boolean);
    if (!courtIds.length) { alert('코트를 선택해주세요'); return; }
    try {
      const res = await fetch('/api/reservations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          venue_id:       sel.venue.id,
          court_ids:      courtIds,
          date:           fmtDate(sel.day.date),
          start_hour:     sel.hour,
          duration_hours: b.duration,
          equipment:      b.equipment || [],
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.status === 401) { window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname); return; }
      if (!res.ok) { alert('예약 실패: ' + (data.error || res.status)); return; }
      window.location.href = data.redirect || ('/reservations/' + data.code);
    } catch (e) {
      alert('네트워크 오류: ' + e.message);
    }
  }

  return (
    <Mobile hideTab>
      <U3Detail
        venueId={window.__DATA__.venueId}
        isFav={!!window.__DATA__.isFav}
        onFavToggle={favToggle}
        onBack={() => nav('/venues')}
        onSelectSlot={(s) => setSel(s)}
      />
      {sel && (
        <U4ReserveSheet
          selection={sel}
          courtsList={courtsList}
          equipmentList={equipmentList}
          onClose={() => setSel(null)}
          onConfirm={submit}
        />
      )}
    </Mobile>
  );
}

createRoot(document.getElementById('root')).render(<App />);
