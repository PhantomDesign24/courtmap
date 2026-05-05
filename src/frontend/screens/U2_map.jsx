import React from "react";

// U2 — 검색결과/지도 (지도+리스트 토글)

function FilterChipBar() {
  return (
    <div style={{ display: "flex", gap: 6, padding: "10px 16px", overflowX: "auto", scrollbarWidth: "none", borderBottom: "1px solid var(--line)", background: "#fff" }}>
      <button type="button" className="chip">{I.filter(14)} 필터</button>
      <button type="button" className="chip active">지금 가능</button>
      <button type="button" className="chip">3만원 이하</button>
      <button type="button" className="chip">2km 이내</button>
      <button type="button" className="chip">주차</button>
      <button type="button" className="chip">샤워실</button>
    </div>
  );
}

// 큰 지도 + 핀 + 하단 시트
function U2Map({ onBack, onVenue }) {
  const [selected, setSelected] = React.useState("v2");
  const v = VENUES.find(x => x.id === selected) || VENUES[0];
  const final = v.discount ? v.price * (1 - v.discount/100) : v.price;
  const pins = [
    { id: "v1", x: 80,  y: 180, price: "3.5만" },
    { id: "v2", x: 200, y: 240, price: "2.4만", hot: true },
    { id: "v3", x: 290, y: 160, price: "3.2만" },
    { id: "v4", x: 150, y: 340, price: "4.0만" },
    { id: "v5", x: 320, y: 320, price: "2.0만", hot: true },
    { id: "v6", x: 60,  y: 320, price: "3.0만" },
  ];

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden", position: "relative" }}>
      {/* Top bar */}
      <div style={{ padding: "10px 12px", display: "flex", alignItems: "center", gap: 8, background: "#fff", borderBottom: "1px solid var(--line)" }}>
        <button type="button" onClick={onBack} style={{ background: "none", border: "none", padding: 6 }}>{I.back(22)}</button>
        <div className="search-bar solid" style={{ flex: 1, height: 40 }}>
          {I.search(16, "var(--gray-500)")}
          <span style={{ flex: 1 }}>강남구 역삼동</span>
          {I.close(16, "var(--gray-400)")}
        </div>
      </div>
      <FilterChipBar />

      {/* Map */}
      <div style={{ flex: 1, position: "relative", background: "#e9eef5", overflow: "hidden" }}>
        <svg width="100%" height="100%" viewBox="0 0 390 480" preserveAspectRatio="xMidYMid slice" style={{ position: "absolute", inset: 0 }}>
          <rect width="390" height="480" fill="#e9eef5"/>
          <path d="M-10 130 Q 80 120 160 140 T 400 130 L 400 220 L -10 220 Z" fill="#dbe9d6" opacity="0.55"/>
          <path d="M-10 380 Q 100 370 220 385 T 400 375 L 400 480 L -10 480 Z" fill="#dbe9d6" opacity="0.45"/>
          <circle cx="60" cy="60" r="50" fill="#dbe9d6" opacity="0.5"/>
          <circle cx="340" cy="430" r="60" fill="#dbe9d6" opacity="0.5"/>
          <path d="M0 100 Q 100 90 200 105 T 400 95" stroke="#fff" strokeWidth="18" fill="none"/>
          <path d="M120 0 Q 110 200 130 350 T 110 480" stroke="#fff" strokeWidth="14" fill="none"/>
          <path d="M280 0 Q 290 220 270 380 T 290 480" stroke="#fff" strokeWidth="11" fill="none"/>
          <path d="M0 280 L 390 270" stroke="#fff" strokeWidth="9" fill="none"/>
          <path d="M0 430 L 390 425" stroke="#fff" strokeWidth="8" fill="none"/>
          <text x="20" y="35" fontSize="11" fill="#6b7280" fontWeight="600">테헤란로</text>
          <text x="200" y="92" fontSize="11" fill="#6b7280" fontWeight="600">강남대로</text>
          <text x="20" y="265" fontSize="10" fill="#6b7280" fontWeight="600">역삼역</text>
          <text x="300" y="265" fontSize="10" fill="#6b7280" fontWeight="600">선릉역</text>
          {/* user */}
          <g transform="translate(195,260)">
            <circle r="32" fill="rgba(30,80,255,0.12)"/>
            <circle r="11" fill="#1e50ff" stroke="#fff" strokeWidth="3"/>
          </g>
        </svg>

        {/* Pins */}
        {pins.map(p => {
          const isSel = p.id === selected;
          return (
            <button
              key={p.id}
              type="button"
              onClick={() => setSelected(p.id)}
              style={{
                position: "absolute", left: p.x, top: p.y,
                transform: `translate(-50%, -100%) scale(${isSel ? 1.15 : 1})`,
                background: p.hot ? "var(--brand-700)" : (isSel ? "var(--gray-900)" : "var(--brand-500)"),
                color: "#fff", fontSize: 12, fontWeight: 700,
                padding: "6px 11px", borderRadius: 999, border: "none",
                boxShadow: isSel ? "0 8px 22px rgba(15,19,32,.25)" : "0 4px 12px rgba(15,19,32,.18)",
                whiteSpace: "nowrap", cursor: "pointer",
                transition: "transform .15s",
                zIndex: isSel ? 2 : 1,
              }}
            >
              {p.hot && "⚡ "}{p.price}
            </button>
          );
        })}

        {/* Map controls */}
        <div style={{ position: "absolute", right: 12, top: 12, display: "flex", flexDirection: "column", gap: 8 }}>
          <button type="button" style={{ width: 40, height: 40, borderRadius: 10, background: "#fff", border: "none", boxShadow: "var(--shadow-sm)", fontSize: 18, fontWeight: 600 }}>＋</button>
          <button type="button" style={{ width: 40, height: 40, borderRadius: 10, background: "#fff", border: "none", boxShadow: "var(--shadow-sm)", fontSize: 18, fontWeight: 600 }}>−</button>
          <button type="button" style={{ width: 40, height: 40, borderRadius: 10, background: "#fff", border: "none", boxShadow: "var(--shadow-sm)" }}>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style={{margin:"auto",display:"block"}}>
              <circle cx="12" cy="12" r="3" fill="#1e50ff"/>
              <circle cx="12" cy="12" r="8" stroke="#1e50ff" strokeWidth="1.8"/>
              <path d="M12 2v3M12 19v3M2 12h3M19 12h3" stroke="#1e50ff" strokeWidth="1.8" strokeLinecap="round"/>
            </svg>
          </button>
        </div>

        {/* List toggle */}
        <button type="button" style={{
          position: "absolute", left: "50%", top: 12, transform: "translateX(-50%)",
          background: "var(--gray-900)", color: "#fff",
          padding: "8px 14px", borderRadius: 999, border: "none",
          fontSize: 12, fontWeight: 600, display: "flex", alignItems: "center", gap: 6,
          boxShadow: "0 6px 18px rgba(15,19,32,.2)",
        }}>
          {I.list(14, "#fff")} 리스트로 보기
        </button>
      </div>

      {/* Bottom card — selected venue */}
      <div style={{
        position: "absolute", left: 12, right: 12, bottom: 12,
        background: "#fff", borderRadius: 16, padding: 12, display: "flex", gap: 12,
        boxShadow: "0 12px 28px rgba(15,19,32,.18)",
      }} onClick={() => onVenue && onVenue(v.id)}>
        <Photo src={v.img} radius={10} style={{ width: 84, height: 84, flexShrink: 0 }} />
        <div style={{ flex: 1, minWidth: 0 }}>
          <div className="row gap-4" style={{ marginBottom: 3 }}>
            {v.hot && <span className="badge" style={{padding:"2px 6px", background:"var(--brand-500)", color:"#fff"}}>{I.bolt(10,"#fff")} 임박</span>}
            <span className="badge badge-soft">코트 {v.courts}면</span>
          </div>
          <div className="fw-700" style={{ fontSize: 14.5, marginBottom: 3, letterSpacing: "-0.3px" }}>{v.name}</div>
          <div className="row gap-4" style={{ fontSize: 12, color: "var(--text-sub)", marginBottom: 6 }}>
            <span>도보 {v.walkMin}분</span>
            <span>·</span>
            {I.star(11)} <span className="fw-600" style={{ color: "var(--text)" }}>{v.rating}</span>
            <span>({v.reviews})</span>
          </div>
          <div className="row gap-6" style={{ alignItems: "baseline" }}>
            <span className="badge badge-success" style={{padding:"3px 7px"}}>
              <span style={{ width:6, height:6, borderRadius:"50%", background:"var(--green-500)" }}></span>
              {v.nextSlot} 가능
            </span>
            <div className="spacer"/>
            <span className="fw-700 num" style={{ fontSize: 15 }}>{won(final)}<span style={{fontSize:11,fontWeight:500,color:"var(--text-sub)"}}>/시간</span></span>
          </div>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { U2Map });
