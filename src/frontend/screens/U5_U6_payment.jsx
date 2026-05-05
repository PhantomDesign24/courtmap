import React from "react";

// U5 입금 안내 + U6 예약 완료

// 입금 마감 시각 ISO("2026-05-06 23:59:59") → "오늘 23:59" / "내일 12:00" / "5/8 19:00"
function formatDueAt(iso) {
  if (!iso) return "오늘 23:59";
  const d = new Date(iso.replace(' ', 'T'));
  if (isNaN(d.getTime())) return "오늘 23:59";
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const dD = new Date(d.getFullYear(), d.getMonth(), d.getDate());
  const diffDays = Math.round((dD.getTime() - today.getTime()) / 86400000);
  const hh = String(d.getHours()).padStart(2,'0');
  const mm = String(d.getMinutes()).padStart(2,'0');
  if (diffDays === 0) return `오늘 ${hh}:${mm}`;
  if (diffDays === 1) return `내일 ${hh}:${mm}`;
  return `${d.getMonth()+1}/${d.getDate()} ${hh}:${mm}`;
}

// ───────────── U5: 입금 안내 ─────────────
function U5Deposit({ booking, onBack, onPaid }) {
  const b = booking;
  const v = b.venue;
  const acc = b.bank ? `${b.bank.name} ${b.bank.account}` : "신한은행 110-432-589021";
  const accName = b.bank ? b.bank.holder : "(주)코트맵";
  const dueAt = formatDueAt(b.deposit_due_at);
  const [copied, setCopied] = React.useState(false);
  const handleCopy = () => {
    try { navigator.clipboard && navigator.clipboard.writeText(acc); } catch(e) {}
    setCopied(true); setTimeout(() => setCopied(false), 1500);
  };

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden", background: "var(--bg-soft)" }}>
      <div style={{ padding: "10px 12px", display: "flex", alignItems: "center", background: "#fff", borderBottom: "1px solid var(--line)", flexShrink: 0 }}>
        <button type="button" onClick={onBack} style={{ background:"none", border:"none", padding: 6 }}>{I.back(22)}</button>
        <div className="fw-700" style={{ fontSize: 16, marginLeft: 4 }}>입금 안내</div>
      </div>

      <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none" }}>
        {/* Hero — pending */}
        <div style={{ padding: "24px 16px 20px", textAlign: "center", background: "#fff" }}>
          <div style={{ width: 64, height: 64, borderRadius: 32, background: "var(--brand-50)", display: "inline-flex", alignItems: "center", justifyContent: "center", marginBottom: 12 }}>
            {I.clock(28, "var(--brand-500)")}
          </div>
          <div className="fw-700" style={{ fontSize: 18, letterSpacing: "-0.4px", marginBottom: 4 }}>입금 후 자동 확정됩니다</div>
          <div className="text-sub" style={{ fontSize: 13 }}>아래 계좌로 입금하면 운영자가 확인 후 확정해요</div>
        </div>

        {/* 예약 정보 */}
        <div style={{ padding: 12 }}>
          <div className="card" style={{ padding: 14, marginBottom: 10 }}>
            <div className="row gap-10" style={{ marginBottom: 12 }}>
              <Photo src={v.img} radius={10} style={{ width: 56, height: 56, flexShrink: 0 }} />
              <div style={{ flex: 1, minWidth: 0 }}>
                <div className="fw-700" style={{ fontSize: 14.5, marginBottom: 2 }}>{v.name}</div>
                <div className="text-sub" style={{ fontSize: 12 }}>{v.area}</div>
              </div>
            </div>
            <div className="hr" style={{ margin: "0 0 12px" }}/>
            <div style={{ display: "grid", gridTemplateColumns: "70px 1fr", rowGap: 8, fontSize: 13 }}>
              <span className="text-sub">날짜</span><span className="fw-600">{b.day.date ? `${b.day.date.slice(0,4)}년 ${parseInt(b.day.date.slice(5,7),10)}월 ${parseInt(b.day.date.slice(8,10),10)}일 (${b.day.dow})` : `${b.day.day}일 (${b.day.dow})`}</span>
              <span className="text-sub">시간</span><span className="fw-600 num">{String(b.hour).padStart(2,"0")}:00 ~ {String(b.hour + b.duration).padStart(2,"0")}:00 <span className="text-sub" style={{fontWeight:500}}>({b.duration}시간)</span></span>
              <span className="text-sub">코트</span><span className="fw-600">{["A","B","C","D"][b.court-1]}코트</span>
              {(b.rentRacket || b.rentShuttle) && (
                <>
                  <span className="text-sub">대여</span>
                  <span className="fw-600">{[b.rentRacket && "라켓", b.rentShuttle && "셔틀콕"].filter(Boolean).join(", ")}</span>
                </>
              )}
            </div>
          </div>

          {/* 계좌 */}
          <div className="card" style={{ padding: 14, marginBottom: 10 }}>
            <div className="row" style={{ marginBottom: 10 }}>
              <span className="fw-700" style={{ fontSize: 14 }}>입금 계좌</span>
              <div className="spacer"/>
              <span className="badge badge-warn">{dueAt}까지</span>
            </div>
            <div style={{ background: "var(--gray-25)", borderRadius: 12, padding: 14, marginBottom: 10 }}>
              <div className="text-sub" style={{ fontSize: 11.5, marginBottom: 4 }}>예금주 · {accName}</div>
              <div className="row gap-8">
                <span className="fw-700 num" style={{ fontSize: 17, letterSpacing: "-0.2px" }}>{acc}</span>
                <div className="spacer"/>
                <button type="button" onClick={handleCopy} className="btn btn-sm btn-line" style={{ height: 30, padding: "0 10px" }}>
                  {I.copy(13)} {copied ? "복사됨" : "복사"}
                </button>
              </div>
            </div>
            <div style={{ background: "var(--brand-50)", borderRadius: 12, padding: 12 }}>
              <div className="row" style={{ alignItems: "baseline", marginBottom: 6 }}>
                <span className="text-sub" style={{ fontSize: 12 }}>입금 금액</span>
                <div className="spacer"/>
                <span className="fw-700 num text-brand" style={{ fontSize: 22, letterSpacing: "-0.3px" }}>{won(b.total)}</span>
              </div>
              <div className="row" style={{ alignItems: "baseline" }}>
                <span className="text-sub" style={{ fontSize: 12 }}>입금자명</span>
                <div className="spacer"/>
                <span className="fw-700">{b.depositor_name || '예약자'}</span>
              </div>
            </div>
          </div>

          {/* 환불 정책 */}
          <div className="card" style={{ padding: 14, marginBottom: 10 }}>
            <div className="fw-700" style={{ fontSize: 14, marginBottom: 10 }}>취소·환불 안내</div>
            <div style={{ display: "grid", gridTemplateColumns: "auto 1fr auto", columnGap: 10, rowGap: 8, fontSize: 13, alignItems: "center" }}>
              <span style={{ color: "var(--green-500)", fontSize: 10, lineHeight: 1 }}>●</span><span>이용 24시간 전</span><span className="fw-700 num">100%</span>
              <span style={{ color: "var(--gold-500)", fontSize: 10, lineHeight: 1 }}>●</span><span>이용 1시간 전</span><span className="fw-700 num">50%</span>
              <span style={{ color: "var(--hot-500)", fontSize: 10, lineHeight: 1 }}>●</span><span>이용 1시간 이내</span><span className="fw-700 num text-mute">환불 불가</span>
            </div>
            <div className="hr" style={{ margin: "12px 0" }}/>
            <div className="text-sub" style={{ fontSize: 12, lineHeight: 1.6 }}>
              · 미입장 시 자동 노쇼 처리되며 신뢰점수가 차감됩니다.<br/>
              · 입금기한을 초과하면 예약이 자동 취소됩니다.
            </div>
          </div>
        </div>

        <div style={{ height: 80 }}/>
      </div>

      <div style={{ flexShrink: 0, padding: "10px 16px 14px", background: "#fff", borderTop: "1px solid var(--line)" }}>
        <button type="button" className="btn btn-primary btn-lg btn-block" onClick={onPaid}>
          입금 완료 · 확인 대기로 이동
        </button>
      </div>
    </div>
  );
}

// ───────────── U6: 예약 완료 (대기→확정) ─────────────
function U6Complete({ booking, onHome, onMyReservations, onEntry }) {
  const b = booking;
  const v = b.venue;
  const [confirmed, setConfirmed] = React.useState(false);

  // 운영자가 입금 확인하면 confirmed 로 전환 — 5초마다 폴링
  React.useEffect(() => {
    if (!b.code) {
      // 데모 모드 (code 없으면 4초 후 자동 확정)
      const t = setTimeout(() => setConfirmed(true), 4000);
      return () => clearTimeout(t);
    }
    let stopped = false;
    let timer;
    const tick = async () => {
      if (stopped) return;
      try {
        const res = await fetch('/api/reservations/' + b.code + '/status', { credentials: 'same-origin' });
        if (res.ok) {
          const data = await res.json();
          if (data.status === 'confirmed' || data.status === 'done') {
            setConfirmed(true);
            return;
          }
        }
      } catch(e) {}
      timer = setTimeout(tick, 5000);
    };
    tick();
    return () => { stopped = true; if (timer) clearTimeout(timer); };
  }, []);

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden", background: confirmed ? "var(--brand-500)" : "#fff", transition: "background .5s" }}>
      <StatusBar dark={confirmed} time="오후 6:48"/>
      <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none", padding: "20px 16px 0" }}>
        {/* Status hero */}
        <div style={{ textAlign: "center", padding: "20px 0 28px", color: confirmed ? "#fff" : "var(--text)" }}>
          <div style={{
            width: 80, height: 80, borderRadius: 40,
            background: confirmed ? "rgba(255,255,255,.2)" : "var(--gray-100)",
            display: "inline-flex", alignItems: "center", justifyContent: "center",
            marginBottom: 14,
            position: "relative",
          }}>
            {confirmed
              ? I.check(40, "#fff")
              : (
                <>
                  <svg width="40" height="40" viewBox="0 0 40 40" style={{ animation: "spin 2s linear infinite" }}>
                    <circle cx="20" cy="20" r="16" stroke="var(--gray-300)" strokeWidth="3" fill="none"/>
                    <path d="M20 4a16 16 0 0116 16" stroke="var(--brand-500)" strokeWidth="3" strokeLinecap="round" fill="none"/>
                  </svg>
                  <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
                </>
              )}
          </div>
          <div className="fw-700" style={{ fontSize: 22, letterSpacing: "-0.5px", marginBottom: 6 }}>
            {confirmed ? "예약 확정!" : "입금 확인 중"}
          </div>
          <div style={{ fontSize: 13.5, opacity: confirmed ? 0.9 : 0.65 }}>
            {confirmed ? "예약 시간에 맞춰 방문해주세요" : "운영자가 입금을 확인하면 자동 확정됩니다"}
          </div>
        </div>

        {/* 입장 안내 (확정 시) or 진행 상황 */}
        {confirmed ? (
          <div style={{ background: "#fff", borderRadius: 16, padding: 18, marginBottom: 14, color: "var(--text)" }}>
            <div className="row gap-6" style={{ marginBottom: 12 }}>
              <span className="badge badge-soft">입장 안내</span>
              <span className="text-sub" style={{ fontSize: 11.5 }}>예약 5분 전부터 입장 가능</span>
            </div>
            <div style={{ background: "var(--gray-25)", borderRadius: 10, padding: 14, fontSize: 13.5, lineHeight: 1.6, color: "var(--text-sub)", marginBottom: 12, textAlign: "center" }}>
              프론트에서 <span className="fw-800" style={{color:"var(--text)", fontSize: 15}}>"코트맵 {b.depositor_name || '예약자'}"</span>이라고<br/>
              알려주시면 됩니다
            </div>
            <button type="button" onClick={onEntry} className="btn btn-line btn-md btn-block">
              입장 안내 자세히 보기
            </button>
          </div>
        ) : (
          <div style={{ background: "var(--gray-25)", border: "1px solid var(--line)", borderRadius: 16, padding: 16, marginBottom: 14 }}>
            <div className="row" style={{ marginBottom: 12 }}>
              <span className="fw-700" style={{ fontSize: 13.5 }}>진행 상황</span>
              <div className="spacer"/>
              <span className="text-sub" style={{ fontSize: 11.5 }}>실시간 자동 갱신</span>
            </div>
            {[
              { l: "예약 신청", done: true },
              { l: "입금 완료 등록", done: true },
              { l: "운영자 확인 (1~10분)", done: false, cur: true },
              { l: "예약 확정", done: false },
            ].map((s, i, arr) => (
              <div key={i} className="row gap-10" style={{ marginBottom: i === arr.length-1 ? 0 : 10, position: "relative" }}>
                <div style={{
                  width: 22, height: 22, borderRadius: 11, flexShrink: 0,
                  background: s.done ? "var(--green-500)" : (s.cur ? "var(--brand-500)" : "var(--gray-200)"),
                  color: "#fff", display: "flex", alignItems: "center", justifyContent: "center",
                }} className={s.cur ? "pulse" : ""}>
                  {s.done && I.check(14, "#fff")}
                  {s.cur && <span style={{ width: 6, height: 6, borderRadius: 3, background: "#fff" }}/>}
                </div>
                <span className="fw-600" style={{ fontSize: 13.5, color: (s.done || s.cur) ? "var(--text)" : "var(--text-mute)" }}>{s.l}</span>
              </div>
            ))}
          </div>
        )}

        {/* Booking summary */}
        <div style={{ background: "#fff", borderRadius: 16, padding: 16, marginBottom: 14 }}>
          <div className="row gap-10" style={{ marginBottom: 12 }}>
            <Photo src={v.img} radius={10} style={{ width: 52, height: 52, flexShrink: 0 }} />
            <div style={{ flex: 1, minWidth: 0 }}>
              <div className="fw-700" style={{ fontSize: 14.5, marginBottom: 2 }}>{v.name}</div>
              <div className="text-sub" style={{ fontSize: 12 }}>{v.area}</div>
            </div>
            <button type="button" className="btn btn-sm btn-line">길찾기</button>
          </div>
          <div className="hr" style={{ margin: "0 0 12px" }}/>
          <div style={{ display: "grid", gridTemplateColumns: "70px 1fr", rowGap: 8, fontSize: 13 }}>
            <span className="text-sub">예약번호</span><span className="fw-600 num">{b.code || `CMAP-${b.day.day}${b.hour}${b.court}`}</span>
            <span className="text-sub">날짜</span><span className="fw-600">2026년 5월 {b.day.day}일 ({b.day.dow})</span>
            <span className="text-sub">시간</span><span className="fw-600 num">{String(b.hour).padStart(2,"0")}:00 ~ {String(b.hour + b.duration).padStart(2,"0")}:00</span>
            <span className="text-sub">코트</span><span className="fw-600">{["A","B","C","D"][b.court-1]}코트</span>
            <span className="text-sub">결제금액</span><span className="fw-700 num text-brand">{won(b.total)}</span>
          </div>
        </div>

        <button type="button" className="btn btn-line btn-md btn-block" style={{ marginBottom: 10 }}>
          {I.share(16)} 동호회 단톡방에 공유
        </button>
        <div style={{ height: 16 }}/>
      </div>

      <div style={{ flexShrink: 0, padding: "10px 16px 14px", background: confirmed ? "var(--brand-600)" : "#fff", borderTop: "1px solid " + (confirmed ? "var(--brand-700)" : "var(--line)"), display: "flex", gap: 10 }}>
        <button type="button" className="btn btn-md" onClick={onHome} style={{ flex: 1, background: confirmed ? "rgba(255,255,255,.18)" : "var(--gray-100)", color: confirmed ? "#fff" : "var(--text)" }}>홈으로</button>
        <button type="button" className="btn btn-md" onClick={onMyReservations} style={{ flex: 1, background: confirmed ? "#fff" : "var(--brand-500)", color: confirmed ? "var(--brand-600)" : "#fff", fontWeight: 700 }}>내 예약 보기</button>
      </div>
    </div>
  );
}

Object.assign(window, { U5Deposit, U6Complete });
