import React from "react";

// U1 — 홈 (3가지 레이아웃 변형)
// A. 지도 우선 (Map-first)  — 화면 상단에 지도 미니뷰 + 빈 코트 핀
// B. 리스트 우선 (List-first) — 빠른 필터칩 + 빈 코트 카드 리스트
// C. 핫딜 우선 (Deal-first)   — 핫딜 캐러셀이 메인 히어로

// ─────────────────────────────────────────────
// 공통: 헤더 + 검색바
function HomeHeader({ location = "강남구 역삼동", onNotif, onSearch, onLocation, hasUnread = false }) {
  return (
    <div style={{ padding: "8px 16px 12px", background: "#fff" }}>
      <div className="row gap-6" style={{ marginBottom: 14 }}>
        <button type="button" onClick={onLocation} style={{ background: "none", border: "none", padding: 0, display: "flex", alignItems: "center", gap: 4, cursor: "pointer" }}>
          <span style={{ fontSize: 18, fontWeight: 800, color: "var(--text)", letterSpacing: "-0.5px" }}>{location}</span>
          {I.chevDown(18, "var(--text)")}
        </button>
        <div className="spacer" />
        <button type="button" style={{ background: "none", border: "none", padding: 6, position: "relative", cursor: "pointer" }} onClick={onNotif}>
          {I.bell(22, "var(--text)")}
          {hasUnread && <span style={{ position: "absolute", top: 4, right: 4, width: 8, height: 8, background: "var(--hot-500)", borderRadius: "50%", border: "1.5px solid #fff" }}></span>}
        </button>
      </div>
      <button type="button" onClick={onSearch} className="search-bar" style={{ background: "var(--gray-50)", border: "none", width: "100%", textAlign: "left", cursor: "pointer", display: "flex", alignItems: "center", gap: 8 }}>
        {I.search(18, "var(--gray-500)")}
        <span style={{ flex: 1, color: "var(--text-sub)" }}>구장명·지역·역 검색</span>
        <span className="badge badge-soft" onClick={(e)=>{ e.stopPropagation(); onLocation && onLocation(); }}>위치 ON</span>
      </button>
    </div>
  );
}

// 빠른 시간 필터칩 — "지금부터 N시간 안에"
function TimeChips({ selected = "now", onSelect }) {
  const opts = [
    { id: "now",  label: "지금 ⚡", brand: true },
    { id: "1h",   label: "1시간 내" },
    { id: "2h",   label: "2시간 내" },
    { id: "today",label: "오늘 저녁" },
    { id: "wk",   label: "이번 주말" },
  ];
  return (
    <div style={{ display: "flex", gap: 6, padding: "0 16px 14px", overflowX: "auto", scrollbarWidth: "none" }}>
      {opts.map(o => (
        <button
          key={o.id}
          type="button"
          className={"chip" + (selected === o.id ? (o.brand ? " brand" : " active") : "")}
          onClick={() => onSelect && onSelect(o.id)}
          style={{ flexShrink: 0 }}
        >
          {o.label}
        </button>
      ))}
    </div>
  );
}

// 지금 가능한 코트 카드 (가로 슬라이드)
// "임박 슬롯" 강조 — 할인이 있으면 부수적으로 표시
function HotDealCard({ v, dense = false, onClick }) {
  const w = dense ? 200 : 240;
  const h = dense ? 110 : 130;
  const final = v.price * (1 - (v.discount || 0) / 100);
  const startsIn = v.startsIn || "곧 시작"; // 컴포넌트 호출부에서 주입 가능
  return (
    <button type="button" onClick={onClick} style={{
      flexShrink: 0, width: w, background: "#fff", borderRadius: 14, border: "1px solid var(--line)",
      padding: 0, textAlign: "left", overflow: "hidden", cursor: "pointer",
    }}>
      <div style={{ position: "relative", height: h }}>
        <Photo src={v.img} radius={0} style={{ width: "100%", height: "100%" }} />
        {/* 임박 배지 — 시간 강조 */}
        <div style={{ position: "absolute", top: 8, left: 8, display: "flex", gap: 4 }}>
          <span className="badge" style={{ background: "var(--brand-500)", color: "#fff" }}>
            {I.bolt(11,"#fff")} {startsIn}
          </span>
        </div>
        {/* 할인이 있는 경우만 우상단 배지 */}
        {v.discount && (
          <div style={{ position: "absolute", top: 8, right: 8 }}>
            <span className="badge" style={{ background: "var(--hot-500)", color: "#fff" }}>{v.discount}%</span>
          </div>
        )}
        <div style={{ position: "absolute", bottom: 8, right: 8 }}>
          <span className="badge badge-dark" style={{ background: "rgba(0,0,0,.7)" }}>{v.nextSlot}</span>
        </div>
      </div>
      <div style={{ padding: "10px 12px 12px" }}>
        <div className="fw-700" style={{ fontSize: 14, marginBottom: 2, letterSpacing: "-0.3px", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{v.name}</div>
        <div className="row gap-4" style={{ fontSize: 11.5, color: "var(--text-sub)", marginBottom: 6 }}>
          {I.star(11)} <span className="fw-600" style={{ color: "var(--text)" }}>{v.rating}</span>
          <span>·</span>
          <span>도보 {v.walkMin}분</span>
        </div>
        <div className="row gap-4" style={{ alignItems: "baseline" }}>
          {v.discount ? (
            <>
              <span style={{ fontSize: 12, color: "var(--text-mute)", textDecoration: "line-through" }} className="num">{won(v.price)}</span>
              <span className="fw-700 num text-hot" style={{ fontSize: 15 }}>{won(final)}</span>
            </>
          ) : (
            <span className="fw-700 num" style={{ fontSize: 15 }}>{won(v.price)}<span style={{ fontSize: 11, fontWeight: 500, color: "var(--text-sub)" }}>/시간</span></span>
          )}
        </div>
      </div>
    </button>
  );
}

// 빈 코트 리스트 카드 (세로)
function VenueRow({ v, dense = false, onClick }) {
  const final = v.discount ? v.price * (1 - v.discount / 100) : v.price;
  const photoSize = dense ? 80 : 96;
  return (
    <button type="button" onClick={onClick} style={{
      display: "flex", gap: 12, width: "100%", background: "#fff",
      border: "none", padding: dense ? "10px 16px" : "12px 16px", textAlign: "left", cursor: "pointer",
      borderBottom: "1px solid var(--line)",
    }}>
      <Photo src={v.img} radius={10} style={{ width: photoSize, height: photoSize, flexShrink: 0 }} />
      <div style={{ flex: 1, minWidth: 0 }}>
        <div className="row gap-4" style={{ marginBottom: 4 }}>
          <span className="badge badge-soft">코트 {v.courts}면</span>
          {v.tags && v.tags[0] && <span className="badge" style={{background:"var(--gray-100)", color:"var(--text-sub)"}}>{v.tags[0]}</span>}
        </div>
        <div className="fw-700" style={{ fontSize: 14.5, marginBottom: 3, letterSpacing: "-0.3px", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{v.name}</div>
        <div className="row gap-4" style={{ fontSize: 12, color: "var(--text-sub)", marginBottom: 6 }}>
          {I.walk(11, "var(--text-sub)")}
          <span>도보 {v.walkMin}분</span>
          <span>·</span>
          <span>{I.star(11)} <span className="fw-600" style={{ color: "var(--text)" }}>{v.rating}</span> ({v.reviews})</span>
        </div>
        <div className="row gap-6" style={{ alignItems: "baseline" }}>
          <span className="badge badge-success" style={{ padding: "3px 7px" }}>
            <span style={{ width:6, height:6, borderRadius:"50%", background:"var(--green-500)" }}></span>
            {v.nextSlot} 가능
          </span>
          <div className="spacer" />
          {v.discount && <span style={{ fontSize: 11.5, color: "var(--text-mute)", textDecoration: "line-through" }} className="num">{won(v.price)}</span>}
          <span className={"fw-700 num " + (v.discount ? "text-hot" : "")} style={{ fontSize: 15 }}>{won(final)}<span style={{ fontSize: 11, fontWeight: 500, color: "var(--text-sub)" }}>/시간</span></span>
        </div>
      </div>
    </button>
  );
}

// 미니 지도 SVG (가짜)
function MiniMap({ height = 200, pinCount = 5, onPress }) {
  // hand-drawn-ish 도로 + 핀
  const pins = [
    { x: 90,  y: 70,  price: "3.5만", hot: false },
    { x: 200, y: 110, price: "2.4만", hot: true  },
    { x: 280, y: 60,  price: "3.2만" },
    { x: 150, y: 160, price: "2.8만" },
    { x: 320, y: 150, price: "4.0만" },
  ].slice(0, pinCount);
  return (
    <button type="button" onClick={onPress} style={{ width: "100%", height, position: "relative", overflow: "hidden", border: "none", padding: 0, borderRadius: 14, cursor: "pointer", background: "#e9eef5" }}>
      <svg width="100%" height={height} viewBox={`0 0 358 ${height}`} preserveAspectRatio="xMidYMid slice" style={{ display: "block" }}>
        <rect width="358" height={height} fill="#e9eef5"/>
        {/* parks */}
        <path d="M-10 130 Q 80 120 160 140 T 370 130 L 370 220 L -10 220 Z" fill="#dbe9d6" opacity="0.7"/>
        <circle cx="60" cy="40" r="40" fill="#dbe9d6" opacity="0.5"/>
        {/* roads */}
        <path d="M0 90 Q 80 80 180 95 T 358 85" stroke="#fff" strokeWidth="14" fill="none"/>
        <path d="M0 90 Q 80 80 180 95 T 358 85" stroke="#d0d6df" strokeWidth="1" fill="none"/>
        <path d="M120 -10 Q 110 80 130 130 T 110 230" stroke="#fff" strokeWidth="11" fill="none"/>
        <path d="M260 -10 Q 270 90 250 140 T 270 230" stroke="#fff" strokeWidth="9" fill="none"/>
        <path d="M0 170 L 358 165" stroke="#fff" strokeWidth="8" fill="none"/>
        {/* river */}
        <path d="M-10 195 Q 90 185 200 200 T 370 190 L 370 230 L -10 230 Z" fill="#bcd6f0" opacity="0.7"/>
        {/* labels */}
        <text x="20" y="35" fontSize="9" fill="#6b7280" fontWeight="600">강남대로</text>
        <text x="280" y="40" fontSize="9" fill="#6b7280" fontWeight="600">테헤란로</text>
        {/* "you" */}
        <g transform="translate(170,100)">
          <circle r="22" fill="rgba(30,80,255,0.15)"/>
          <circle r="9" fill="#1e50ff" stroke="#fff" strokeWidth="2.5"/>
        </g>
      </svg>
      {/* pins as DOM (so we can style nicely) */}
      {pins.map((p, i) => (
        <div key={i} style={{
          position: "absolute", left: p.x, top: p.y,
          transform: "translate(-50%, -100%)",
          background: p.hot ? "var(--hot-500)" : "var(--brand-500)",
          color: "#fff", fontSize: 11, fontWeight: 700,
          padding: "5px 9px", borderRadius: 999,
          boxShadow: "0 3px 10px rgba(15,19,32,.18)",
          whiteSpace: "nowrap",
        }}>
          {p.hot && "⚡ "}{p.price}
        </div>
      ))}
      <div style={{ position: "absolute", right: 10, bottom: 10, background: "#fff", padding: "6px 10px", borderRadius: 999, fontSize: 12, fontWeight: 600, boxShadow: "var(--shadow-sm)" }}>
        지도 전체보기 →
      </div>
    </button>
  );
}

// ─────────────────────────────────────────────
// A. Map-first
function HomeMapFirst({ dense = false, onVenue, ...headerProps }) {
  const venues = VENUES.slice(0, dense ? 5 : 4);
  return (
    <>
      <HomeHeader {...headerProps} />
      <TimeChips />
      <div style={{ padding: "0 16px 14px" }}>
        <MiniMap height={210} pinCount={5} />
      </div>
      <div className="hr-thick" />
      <div style={{ padding: "16px 16px 8px" }} className="row">
        <div>
          <div className="section-title">지금 비어있는 코트</div>
          <div className="section-sub">반경 2km · 5곳</div>
        </div>
        <div className="spacer" />
        <button type="button" className="btn btn-sm btn-line">정렬 ↓</button>
      </div>
      {venues.map(v => <VenueRow key={v.id} v={v} dense={dense} onClick={() => onVenue && onVenue(v.id)} />)}
      <div style={{ height: 24 }} />
    </>
  );
}

// B. List-first
function HomeListFirst({ dense = false, onVenue, ...headerProps }) {
  const venues = dense ? VENUES : VENUES.slice(0, 5);
  return (
    <>
      <HomeHeader {...headerProps} />
      <TimeChips />
      <div style={{ padding: "0 16px 16px" }}>
        <div style={{ display: "flex", gap: 6, overflowX: "auto", scrollbarWidth: "none" }}>
          <button type="button" className="chip">{I.filter(14)} 필터</button>
          <button type="button" className="chip">거리순 ↓</button>
          <button type="button" className="chip">3만원 이하</button>
          <button type="button" className="chip">주차가능</button>
          <button type="button" className="chip">샤워실</button>
        </div>
      </div>
      <div className="row" style={{ padding: "0 16px 8px" }}>
        <div className="fw-700" style={{ fontSize: 15 }}>지금 비어있는 코트 <span className="text-brand">{venues.length}</span></div>
        <div className="spacer" />
        <button type="button" className="btn btn-sm btn-line">{I.map(14)} 지도</button>
      </div>
      {venues.map(v => <VenueRow key={v.id} v={v} dense={dense} onClick={() => onVenue && onVenue(v.id)} />)}
      <div style={{ height: 24 }} />
    </>
  );
}

// C. Deal-first → "지금 가능 우선"
function HomeDealFirst({ dense = false, onVenue, ...headerProps }) {
  // 임박 슬롯 = discount 가 있거나 hot 플래그가 있는 venue 들 (DB 동적)
  const hot = VENUES.filter(v => v.discount || v.hot).slice(0, dense ? 4 : 3);
  const nowAvail = (hot.length ? hot : VENUES.slice(0, 3))
    .map(v => ({ ...v, startsIn: v.nextSlot || '곧' }));
  const hotIds = new Set(nowAvail.map(v => v.id));
  const others = VENUES.filter(v => !hotIds.has(v.id)).slice(0, dense ? 4 : 3);
  return (
    <>
      <HomeHeader {...headerProps} />
      {/* HERO: 지금 가능 */}
      <div style={{ padding: "0 16px 4px" }}>
        <div className="row" style={{ marginBottom: 10 }}>
          <div className="row gap-6">
            <span style={{ fontSize: 18, fontWeight: 800, letterSpacing: "-0.5px" }}>지금 가능한 코트</span>
            <span className="badge badge-soft">{I.bolt(11,"var(--brand-700)")} 실시간</span>
          </div>
          <div className="spacer" />
          <span className="text-sub" style={{ fontSize: 12 }}>{I.clock(12, "var(--text-sub)")} 곧 마감</span>
        </div>
        <div style={{ display: "flex", gap: 10, overflowX: "auto", scrollbarWidth: "none", paddingBottom: 4, margin: "0 -16px", padding: "0 16px 4px" }}>
          {nowAvail.map(v => <HotDealCard key={v.id} v={v} dense={dense} onClick={() => onVenue && onVenue(v.id)} />)}
        </div>
      </div>
      <TimeChips />
      <div className="hr-thick" />
      <div style={{ padding: "16px 16px 8px" }} className="row">
        <div className="section-title">내 동네 추천</div>
        <div className="spacer" />
        <button type="button" className="btn btn-sm btn-ghost">전체 보기 {I.chevR(14, "var(--brand-500)")}</button>
      </div>
      {others.map(v => <VenueRow key={v.id} v={v} dense={dense} onClick={() => onVenue && onVenue(v.id)} />)}
      <div style={{ height: 24 }} />
    </>
  );
}

Object.assign(window, { HomeMapFirst, HomeListFirst, HomeDealFirst, VenueRow, HotDealCard, MiniMap, HomeHeader, TimeChips });
