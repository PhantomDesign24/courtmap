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
    // ReservationDetail 가 받는 shape 으로 매핑
    const r = {
      id: b.code,
      code: b.code,
      status: b.status,
      venue: b.venue,
      day: `${b.day?.day}월 ${b.day?.day}일 (${b.day?.dow})`,
      time: `${String(b.hour).padStart(2,'0')}:00 ~ ${String(b.hour + b.duration).padStart(2,'0')}:00`,
      court: b.court_name,
      price: b.total,
      recurring: false,
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

  return <div style={{padding:24}}>알 수 없는 화면: {screen}</div>;
}

// 홈 — 위치 변경 + 시간 필터 칩 통합 wrapper
function HomeWithFilters() {
  const [location, setLocation] = React.useState((window.__DATA__ && window.__DATA__.location) || '강남구 역삼동');
  const [showLoc, setShowLoc]   = React.useState(false);
  const [, force]               = React.useState(0);

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

  // HomeDealFirst 의 TimeChips 가 onSelect 안 받음 → DOM 이벤트 위임으로 처리
  React.useEffect(() => {
    const handler = (e) => {
      const chip = e.target.closest('.chip');
      if (!chip) return;
      const label = chip.textContent.trim();
      const range = label.startsWith('지금') ? 'now'
                  : label.includes('1시간')   ? '1h'
                  : label.includes('2시간')   ? '2h'
                  : label.includes('오늘')    ? 'today'
                  : label.includes('주말')    ? 'weekend'
                  : null;
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
