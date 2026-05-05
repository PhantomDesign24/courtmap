import React from "react";

// U7 — 내 예약 (다가오는 / 지난)

function U7MyReservations({ onBack, onVenue, onEntry, onReservation, onCreateRecurring, upcoming, past }) {
  const [tab, setTab] = React.useState("upcoming");

  // props 로 받은 데이터 우선, 없으면 데모용 샘플
  upcoming = upcoming || [
    { id: "r1", status: "confirmed", venue: VENUES[0], day: "5월 5일 (화)", time: "19:00 ~ 21:00", court: "A코트", price: 73000, inDays: "오늘", inHours: 4, recurring: false },
    { id: "r2", status: "pending",   venue: VENUES[1], day: "5월 7일 (목)", time: "20:00 ~ 21:00", court: "C코트", price: 28000, inDays: "이틀 후", recurring: false },
  ];
  past = past || [
    { id: "p1", status: "done", venue: VENUES[3], day: "4월 28일 (화)", time: "19:00 ~ 20:00", court: "A코트", price: 40000 },
  ];

  return (
    <>
      {/* Header */}
      <div style={{ padding: "16px 16px 0", background: "#fff" }}>
        <div className="row" style={{ marginBottom: 12 }}>
          <div style={{ fontSize: 22, fontWeight: 800, letterSpacing: "-0.5px" }}>내 예약</div>
          <div className="spacer"/>
          <button type="button" onClick={() => alert('캘린더 뷰 — v2 에서 제공')} style={{ background: "none", border: "none", padding: 6 }}>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
              <rect x="4" y="5" width="16" height="16" rx="2" stroke="currentColor" strokeWidth="1.8"/>
              <path d="M4 9h16M9 3v4M15 3v4" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"/>
            </svg>
          </button>
        </div>

        {/* Top tabs */}
        <div style={{ display: "flex", borderBottom: "1px solid var(--line)" }}>
          {[
            { id: "upcoming", label: "다가오는", count: upcoming.length },
            { id: "past",     label: "지난 예약", count: past.length },
          ].map(t => (
            <button
              key={t.id}
              type="button"
              onClick={() => setTab(t.id)}
              style={{
                flex: 1, height: 44, background: "none", border: "none",
                fontSize: 14, fontWeight: 700,
                color: tab === t.id ? "var(--text)" : "var(--text-mute)",
                borderBottom: "2px solid " + (tab === t.id ? "var(--brand-500)" : "transparent"),
                marginBottom: -1,
                display: "flex", alignItems: "center", justifyContent: "center", gap: 6,
              }}
            >
              {t.label}
              <span style={{
                fontSize: 11, fontWeight: 700,
                padding: "2px 7px", borderRadius: 999,
                background: tab === t.id ? "var(--brand-50)" : "var(--gray-100)",
                color: tab === t.id ? "var(--brand-700)" : "var(--text-mute)",
              }}>{t.count}</span>
            </button>
          ))}
        </div>
      </div>

      {/* List body */}
      <div style={{ background: "var(--bg-soft)", padding: "12px 12px 24px", minHeight: "100%" }}>
        {tab === "upcoming" && (
          <>
            {/* 다가오는 핵심 카드 — 첫 번째 (오늘) */}
            {upcoming[0] && <UpcomingHero r={upcoming[0]} onClick={() => onEntry && onEntry(upcoming[0])} onCard={() => onReservation && onReservation(upcoming[0])}/>}

            {/* 정기예약 표시 */}
            {upcoming.slice(1).map(r => <UpcomingCard key={r.id} r={r} onClick={() => onReservation && onReservation(r)}/>)}

            <button type="button" onClick={() => { if (onCreateRecurring) onCreateRecurring(); else window.location.href = '/recurring/new'; }} style={{ width: "100%", padding: 0, background: "none", border: "none", cursor: "pointer", marginTop: 14, textAlign: "left" }}>
              <div className="card" style={{ padding: 14, background: "#fff", display: "flex", alignItems: "center", gap: 12 }}>
                <div style={{ width: 38, height: 38, borderRadius: 19, background: "var(--brand-50)", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="var(--brand-500)" strokeWidth="2.2" strokeLinecap="round"/>
                  </svg>
                </div>
                <div style={{ flex: 1 }}>
                  <div className="fw-700" style={{ fontSize: 13.5 }}>정기 예약 만들기</div>
                  <div className="text-sub" style={{ fontSize: 12 }}>매주 같은 시간 자동으로 예약 신청</div>
                </div>
                {I.chevR(18, "var(--text-mute)")}
              </div>
            </button>
          </>
        )}

        {tab === "past" && (
          <>
            {past.map(r => <PastCard key={r.id} r={r} onClick={() => onReservation && onReservation(r)}/>)}
          </>
        )}
      </div>
    </>
  );
}

// ─── 상단 큰 카드 (가장 임박한 예약) ───
function UpcomingHero({ r, onClick, onCard }) {
  return (
    <div style={{
      background: "var(--brand-500)",
      borderRadius: 16,
      padding: 0,
      color: "#fff",
      marginBottom: 10,
      position: "relative",
      overflow: "hidden",
    }}>
      <div style={{
        position: "absolute", right: -30, top: -30,
        width: 140, height: 140, borderRadius: "50%",
        background: "rgba(255,255,255,.08)",
      }}/>
      <button type="button" onClick={onCard} style={{
        width: "100%", padding: "16px 16px 4px", background: "none", border: "none",
        textAlign: "left", cursor: "pointer", color: "#fff", position: "relative",
      }}>
        <div className="row gap-6" style={{ marginBottom: 10 }}>
          <span className="badge" style={{ background: "#fff", color: "var(--brand-700)" }}>
            ● {r.inDays}
          </span>
          <span className="badge" style={{ background: "rgba(255,255,255,.18)", color: "#fff" }}>
            {r.status === "confirmed" ? "확정" : "입금 대기"}
          </span>
          <div className="spacer"/>
          <span style={{ fontSize: 11.5, opacity: 0.8 }}>상세 {">"}</span>
        </div>
        <div className="fw-700" style={{ fontSize: 18, marginBottom: 4, letterSpacing: "-0.4px" }}>{r.venue.name}</div>
        <div className="num fw-700" style={{ fontSize: 24, letterSpacing: "-0.5px", marginBottom: 4 }}>{r.time}</div>
        <div style={{ fontSize: 13, opacity: 0.85, marginBottom: 14 }}>{r.day} · {r.court}</div>
      </button>
      <div style={{ padding: "0 16px 16px", display: "flex", gap: 8, position: "relative" }}>
        <button type="button" onClick={onClick} className="btn btn-md" style={{ flex: 1, background: "#fff", color: "var(--brand-700)", fontWeight: 700 }}>
          입장 안내 보기
        </button>
        <button type="button" className="btn btn-md" style={{ width: 44, padding: 0, background: "rgba(255,255,255,.18)", color: "#fff" }}>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 22s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z" stroke="#fff" strokeWidth="1.8" strokeLinejoin="round"/><circle cx="12" cy="10" r="2.5" stroke="#fff" strokeWidth="1.8"/></svg>
        </button>
      </div>
    </div>
  );
}

// ─── 일반 다가오는 카드 ───
function UpcomingCard({ r, onClick }) {
  const statusBadge = r.status === "confirmed"
    ? <span className="badge badge-success">● 확정</span>
    : <span className="badge badge-warn">● 입금 대기</span>;
  return (
    <button type="button" onClick={onClick} style={{
      width: "100%", background: "#fff", border: "none",
      borderRadius: 14, padding: 14, marginBottom: 10,
      textAlign: "left", cursor: "pointer",
      display: "flex", gap: 12,
    }}>
      <Photo src={r.venue.img} radius={10} style={{ width: 64, height: 64, flexShrink: 0 }} />
      <div style={{ flex: 1, minWidth: 0 }}>
        <div className="row gap-4" style={{ marginBottom: 4 }}>
          {statusBadge}
          {r.recurring && <span className="badge badge-soft">정기</span>}
        </div>
        <div className="fw-700" style={{ fontSize: 14, marginBottom: 2, letterSpacing: "-0.3px" }}>{r.venue.name}</div>
        <div className="text-sub" style={{ fontSize: 12, marginBottom: 6 }}>{r.day} · {r.court}</div>
        <div className="row" style={{ alignItems: "baseline" }}>
          <span className="fw-700 num" style={{ fontSize: 14 }}>{r.time}</span>
          <div className="spacer"/>
          <span className="fw-700 num" style={{ fontSize: 13.5 }}>{won(r.price)}</span>
        </div>
        {r.recurring && (
          <div className="text-sub" style={{ fontSize: 11.5, marginTop: 6, paddingTop: 6, borderTop: "1px dashed var(--line)" }}>
            ↻ {r.recurringInfo}
          </div>
        )}
      </div>
    </button>
  );
}

// ─── 지난 예약 카드 ───
function PastCard({ r, onClick }) {
  const cfg = {
    done:     { label: "완료",   bg: "var(--gray-100)", color: "var(--gray-700)" },
    noshow:   { label: "노쇼",   bg: "var(--hot-50)",   color: "var(--hot-700)"  },
    canceled: { label: "취소됨", bg: "var(--gray-100)", color: "var(--gray-500)" },
  }[r.status];
  const muted = r.status !== "done";
  return (
    <button type="button" onClick={onClick} style={{
      width: "100%", background: "#fff", border: "none",
      borderRadius: 14, padding: 14, marginBottom: 10,
      textAlign: "left", cursor: "pointer",
      display: "flex", gap: 12,
      opacity: muted ? 0.85 : 1,
    }}>
      <Photo src={r.venue.img} radius={10} style={{ width: 60, height: 60, flexShrink: 0, filter: muted ? "saturate(0.6)" : "none" }} />
      <div style={{ flex: 1, minWidth: 0 }}>
        <div className="row gap-4" style={{ marginBottom: 4 }}>
          <span className="badge" style={{ background: cfg.bg, color: cfg.color }}>{cfg.label}</span>
        </div>
        <div className="fw-700" style={{ fontSize: 13.5, marginBottom: 2, letterSpacing: "-0.3px", color: muted ? "var(--text-sub)" : "var(--text)" }}>{r.venue.name}</div>
        <div className="text-sub" style={{ fontSize: 12, marginBottom: 6 }}>{r.day} · {r.time} · {r.court}</div>
        <div className="row">
          <span className="num" style={{ fontSize: 12.5, color: "var(--text-sub)" }}>{won(r.price)}</span>
          <div className="spacer"/>
          {r.status === "done" && <span className="text-brand fw-600" style={{ fontSize: 12 }}>다시 예약</span>}
        </div>
      </div>
    </button>
  );
}

Object.assign(window, { U7MyReservations });
