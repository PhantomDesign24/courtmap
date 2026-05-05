import React from "react";

// U3 — 구장 상세 (캘린더 + 슬롯 그리드)
// + U4 — 예약 시간 선택 (선택된 슬롯 기반 시트)

// ───────────── helpers ─────────────
// 오늘부터 14일치 날짜 strip (공휴일/슬롯단위는 API 응답으로 채워짐)
const KOR_DAYS = ["일","월","화","수","목","금","토"];
const TODAY = (() => { const d = new Date(); d.setHours(0,0,0,0); return d; })();

function fmtDateLocal(d) {
  return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

function makeWeek(start) {
  const arr = [];
  for (let i = 0; i < 14; i++) {
    const d = new Date(start); d.setDate(start.getDate() + i);
    arr.push({
      date: d,
      day: d.getDate(),
      dow: KOR_DAYS[d.getDay()],
      isToday: i === 0,
      isWeekend: d.getDay() === 0 || d.getDay() === 6,
      isHoliday: false, // API 가 채움
      slotUnit: 1,      // API 가 채움
    });
  }
  return arr;
}

// API → UI shape 변환
function transformSlots(apiData) {
  const slots = (apiData?.slots || []).map(s => ({
    hour: s.hour,
    label: s.label,
    endLabel: s.end_label,
    courts: (s.courts || []).map((c, i) => ({
      court:    i + 1,           // 1-based UI 인덱스 (A/B/C/D 컬럼)
      courtId:  c.court_id,      // 실제 DB id
      name:     c.name,
      avail:    c.avail,
      hot:      c.hot,
      discount: c.discount_pct || 0,
    })),
  }));
  return slots;
}

// ───────────── U3: 구장 상세 ─────────────
function U3Detail({ venueId = "v1", onBack, onSelectSlot, isFav: isFavInit = false, onFavToggle }) {
  const v = VENUES.find(x => x.id === venueId) || VENUES[0];
  const [tab, setTab] = React.useState("court"); // court | lesson | info
  const [dayIdx, setDayIdx] = React.useState(0);
  const [isFav, setIsFav] = React.useState(!!isFavInit);
  const [apiData, setApiData] = React.useState(null);
  const week = makeWeek(TODAY);
  const day = week[dayIdx];

  // 슬롯 가용성 API 조회 — 날짜 변경 시
  React.useEffect(() => {
    const dateStr = fmtDateLocal(day.date);
    let cancelled = false;
    fetch(`/api/venues/${v.id}/slots?date=${dateStr}`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => { if (!cancelled) setApiData(data); })
      .catch(() => {});
    return () => { cancelled = true; };
  }, [v.id, dayIdx]);

  // API 응답으로 day 정보 보강
  if (apiData) {
    day.isHoliday = !!apiData.is_holiday;
    day.slotUnit = apiData.slot_unit || 1;
  }
  const slots = apiData ? transformSlots(apiData) : [];

  const handleFav = () => {
    const next = !isFav;
    setIsFav(next);
    if (onFavToggle) onFavToggle(next);
  };

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden" }}>
      {/* Hero */}
      <div style={{ position: "relative", flexShrink: 0 }}>
        <Photo src={v.img} radius={0} style={{ width: "100%", height: 240 }} />
        <div style={{ position: "absolute", top: 8, left: 8, right: 8, display: "flex", justifyContent: "space-between" }}>
          <button type="button" onClick={onBack} style={{ width: 36, height: 36, borderRadius: 18, background: "rgba(255,255,255,.92)", border: "none", display:"flex", alignItems:"center", justifyContent:"center" }}>{I.back(20)}</button>
          <div style={{ display: "flex", gap: 8 }}>
            <button type="button" onClick={async () => {
              const url = window.location.href;
              const title = v.name + ' — 코트맵';
              try {
                if (navigator.share) await navigator.share({ title, url });
                else { await navigator.clipboard.writeText(url); alert('링크가 복사되었습니다.'); }
              } catch (e) {}
            }} style={{ width: 36, height: 36, borderRadius: 18, background: "rgba(255,255,255,.92)", border: "none", display:"flex", alignItems:"center", justifyContent:"center" }}>{I.share(18)}</button>
            <button type="button" onClick={handleFav} style={{ width: 36, height: 36, borderRadius: 18, background: "rgba(255,255,255,.92)", border: "none", display:"flex", alignItems:"center", justifyContent:"center" }}>{I.heart(20, isFav ? "var(--hot-500)" : "currentColor", isFav ? "var(--hot-500)" : "none")}</button>
          </div>
        </div>
        {(() => {
          const photos = (window.__DATA__ && window.__DATA__.venueDetail && window.__DATA__.venueDetail.photos) || [];
          const total = Math.max(1, photos.length);
          return <div style={{ position: "absolute", right: 12, bottom: 12, background: "rgba(0,0,0,.6)", color: "#fff", fontSize: 11, fontWeight: 600, padding: "4px 9px", borderRadius: 999 }}>1 / {total}</div>;
        })()}
      </div>

      {/* Scroll body */}
      <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none" }} className="hide-scroll">
        {/* Title */}
        <div style={{ padding: "16px 16px 12px" }}>
          <div className="row gap-4" style={{ marginBottom: 6 }}>
            <span className="badge badge-success">실시간 예약 가능</span>
            <span className="badge badge-soft">코트 {v.courts}면</span>
            {v.hot && <span className="badge" style={{ background:"var(--brand-500)", color:"#fff" }}>{I.bolt(10,"#fff")} 지금 임박</span>}
          </div>
          <div style={{ fontSize: 20, fontWeight: 800, letterSpacing: "-0.5px", marginBottom: 4 }}>{v.name}</div>
          <div className="row gap-4" style={{ fontSize: 13, color: "var(--text-sub)" }}>
            {I.star(13)} <span className="fw-700" style={{ color: "var(--text)" }}>{v.rating}</span>
            <span>({v.reviews}개)</span>
            <span>·</span>
            {I.pin(13, "var(--text-sub)")}
            <span>{v.area} · 도보 {v.walkMin}분</span>
          </div>
        </div>

        {/* Tabs */}
        <div style={{ display: "flex", borderBottom: "1px solid var(--line)", padding: "0 16px", background: "#fff", position: "sticky", top: 0, zIndex: 2 }}>
          {[
            { id: "court", label: "코트 예약" },
            { id: "lesson", label: "레슨" },
            { id: "info", label: "시설 정보" },
          ].map(t => (
            <button
              key={t.id}
              type="button"
              onClick={() => setTab(t.id)}
              style={{
                flex: 1, height: 44, background: "none", border: "none",
                fontSize: 14, fontWeight: 600,
                color: tab === t.id ? "var(--text)" : "var(--text-mute)",
                borderBottom: "2px solid " + (tab === t.id ? "var(--brand-500)" : "transparent"),
                marginBottom: -1,
              }}
            >
              {t.label}
            </button>
          ))}
        </div>

        {tab === "court" && (
          <>
            {/* Date strip */}
            <div style={{ padding: "16px 16px 8px" }}>
              <div className="row gap-6" style={{ marginBottom: 10 }}>
                <span className="fw-700" style={{ fontSize: 15 }}>날짜 선택</span>
                {day.isHoliday && (
                  <span className="badge badge-warn">⚠ 이 날은 2시간 단위</span>
                )}
              </div>
              <div style={{ display: "flex", gap: 6, overflowX: "auto", scrollbarWidth: "none", margin: "0 -16px", padding: "2px 16px 4px" }}>
                {week.map((d, i) => {
                  const sel = i === dayIdx;
                  const color = d.day === 5 && d.date.getMonth() === 4 ? "var(--hot-500)" :
                                d.dow === "일" ? "var(--hot-500)" :
                                d.dow === "토" ? "var(--brand-500)" : "var(--text)";
                  return (
                    <button
                      key={i}
                      type="button"
                      onClick={() => setDayIdx(i)}
                      style={{
                        flexShrink: 0,
                        width: 52, height: 64,
                        borderRadius: 12,
                        border: sel ? "none" : "1px solid var(--line-strong)",
                        background: sel ? "var(--brand-500)" : "#fff",
                        color: sel ? "#fff" : color,
                        display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center",
                        gap: 2, position: "relative",
                      }}
                    >
                      <span style={{ fontSize: 11, fontWeight: 600, opacity: sel ? 0.9 : 0.7 }}>{d.dow}</span>
                      <span style={{ fontSize: 16, fontWeight: 800 }}>{d.day}</span>
                      {d.isToday && <span style={{ fontSize: 9, fontWeight: 700, color: sel ? "#fff" : "var(--brand-500)", marginTop: 2 }}>오늘</span>}
                      {d.isHoliday && !d.isToday && <span style={{ width: 4, height: 4, borderRadius: "50%", background: sel ? "#fff" : "var(--hot-500)", marginTop: 4 }}/>}
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Slot grid header */}
            <div style={{ padding: "10px 16px 6px" }} className="row">
              <span className="fw-700" style={{ fontSize: 15 }}>시간 선택</span>
              <span className="text-sub" style={{ fontSize: 12, marginLeft: 6 }}>· {day.slotUnit}시간 단위</span>
              <div className="spacer"/>
              <span className="text-sub" style={{ fontSize: 11.5 }}>
                <span style={{ display: "inline-block", width: 8, height: 8, borderRadius: 2, background: "#fff", border: "1px solid var(--line-strong)", marginRight: 4 }}/>가능
                <span style={{ display: "inline-block", width: 8, height: 8, borderRadius: 2, background: "var(--gray-100)", marginLeft: 8, marginRight: 4 }}/>마감
              </span>
            </div>

            {/* Court labels */}
            <div style={{ display: "grid", gridTemplateColumns: "56px 1fr 1fr 1fr 1fr", gap: 6, padding: "0 16px", marginBottom: 6, fontSize: 11, color: "var(--text-sub)", fontWeight: 600 }}>
              <div></div>
              <div style={{ textAlign: "center" }}>A코트</div>
              <div style={{ textAlign: "center" }}>B코트</div>
              <div style={{ textAlign: "center" }}>C코트</div>
              <div style={{ textAlign: "center" }}>D코트</div>
            </div>

            {/* Slot grid rows */}
            <div style={{ padding: "0 16px 16px" }}>
              {slots.map((s, i) => (
                <div key={i} style={{ display: "grid", gridTemplateColumns: "56px 1fr 1fr 1fr 1fr", gap: 6, marginBottom: 6 }}>
                  <div style={{ display: "flex", flexDirection: "column", justifyContent: "center", fontSize: 11, color: "var(--text-sub)", fontWeight: 600 }} className="num">
                    <span style={{ color: "var(--text)" }}>{s.label}</span>
                    <span>~{s.endLabel}</span>
                  </div>
                  {s.courts.map((c, ci) => {
                    const disabled = !c.avail;
                    return (
                      <button
                        key={ci}
                        type="button"
                        disabled={disabled}
                        onClick={() => onSelectSlot && onSelectSlot({ venue: v, day, hour: s.hour, court: c.court, courtId: c.courtId, hot: c.hot, unit: day.slotUnit })}
                        style={{
                          height: 36,
                          borderRadius: 8,
                          border: disabled ? "none" : "1px solid " + (c.hot ? "var(--hot-500)" : "var(--line-strong)"),
                          background: disabled ? "var(--gray-100)" : (c.hot ? "var(--hot-50)" : "#fff"),
                          color: disabled ? "var(--text-mute)" : (c.hot ? "var(--hot-700)" : "var(--text)"),
                          fontSize: 11.5,
                          fontWeight: 700,
                          letterSpacing: "-0.2px",
                          cursor: disabled ? "default" : "pointer",
                        }}
                      >
                        {disabled ? "마감" : c.hot ? `−${30}%` : "예약"}
                      </button>
                    );
                  })}
                </div>
              ))}
            </div>
          </>
        )}

        {tab === "lesson" && (
          <div style={{ padding: 16 }}>
            {(() => {
              const detail = (window.__DATA__ && window.__DATA__.venueDetail) || {};
              const coaches = detail.coaches || [];
              if (!coaches.length) return <div className="text-sub" style={{ textAlign: "center", padding: 40, fontSize: 13 }}>등록된 강사가 없습니다.</div>;
              return coaches.map((c) => (
                <div key={c.id} className="card" style={{ display: "flex", gap: 12, padding: 12, marginBottom: 10 }}>
                  <Photo src={c.img} radius={999} style={{ width: 56, height: 56, flexShrink: 0 }} />
                  <div style={{ flex: 1 }}>
                    <div className="fw-700" style={{ fontSize: 14, marginBottom: 2 }}>{c.name}</div>
                    <div className="text-sub" style={{ fontSize: 12, marginBottom: 6 }}>{c.career}</div>
                    <div className="row">
                      <span className="fw-700 num">{won(c.price)}</span>
                      <span className="text-sub" style={{ fontSize: 11.5, marginLeft: 2 }}>/{c.duration_min}분</span>
                      <div className="spacer"/>
                      <button type="button" className="btn btn-sm btn-line" onClick={async () => {
                        const date = prompt('레슨 날짜 (YYYY-MM-DD)', new Date().toISOString().slice(0,10));
                        if (!date) return;
                        const hourStr = prompt('시작 시각 (0~23)', '14');
                        if (!hourStr) return;
                        const fd = new FormData();
                        fd.append('coach_id', c.id);
                        fd.append('lesson_date', date);
                        fd.append('start_hour', hourStr);
                        const res = await fetch('/api/lessons', { method: 'POST', body: fd, credentials: 'same-origin' });
                        const data = await res.json();
                        alert(res.ok ? `레슨 예약 신청 완료 (${data.code})` : ('실패: ' + (data.error || res.status)));
                      }}>예약</button>
                    </div>
                  </div>
                </div>
              ));
            })()}
          </div>
        )}

        {tab === "info" && (() => {
          const detail = (window.__DATA__ && window.__DATA__.venueDetail) || {};
          const KOR = ['일','월','화','수','목','금','토'];
          const tags = detail.tags || [];
          const hours = detail.hours || [];
          return (
            <div style={{ padding: 16 }}>
              <div className="card" style={{ padding: 14, marginBottom: 10 }}>
                <div className="fw-700" style={{ marginBottom: 10 }}>운영 시간</div>
                {hours.length === 0 && <div className="text-sub" style={{ fontSize: 13 }}>운영시간 미등록</div>}
                {hours.map(h => (
                  <div key={h.dow} className="row" style={{ fontSize: 13, marginBottom: 4 }}>
                    <span className="text-sub" style={{ width: 50 }}>{KOR[h.dow]}요일</span>
                    <span>{h.closed ? '휴무' : `${h.open} ~ ${h.close === '23:59' ? '24:00' : h.close}`}</span>
                  </div>
                ))}
              </div>
              <div className="card" style={{ padding: 14, marginBottom: 10 }}>
                <div className="fw-700" style={{ marginBottom: 10 }}>편의시설</div>
                {tags.length === 0 ? <div className="text-sub" style={{ fontSize: 13 }}>등록된 시설 정보가 없습니다.</div> : (
                  <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
                    {tags.map((t, i) => <span key={i} className="badge badge-gray">{t}</span>)}
                  </div>
                )}
              </div>
              <div className="card" style={{ padding: 14 }}>
                <div className="fw-700" style={{ marginBottom: 8 }}>위치</div>
                <div className="text-sub" style={{ fontSize: 13, marginBottom: 4 }}>{detail.address || v.area}</div>
                <div className="text-sub" style={{ fontSize: 12 }}>{detail.phone || ''}</div>
              </div>
            </div>
          );
        })()}

        <div style={{ height: 80 }}/>
      </div>

      {/* Sticky CTA */}
      <div style={{ flexShrink: 0, padding: "10px 16px 14px", background: "#fff", borderTop: "1px solid var(--line)", display: "flex", alignItems: "center", gap: 10 }}>
        <div>
          <div className="text-sub" style={{ fontSize: 11 }}>1시간 기준</div>
          <div className="fw-700 num" style={{ fontSize: 17 }}>
            {v.discount && <span style={{ color: "var(--hot-500)", marginRight: 4 }}>{v.discount}%</span>}
            {won(v.discount ? v.price * (1-v.discount/100) : v.price)}
          </div>
        </div>
        <div className="spacer"/>
        <button type="button" className="btn btn-primary btn-lg" style={{ minWidth: 180 }}>
          예약하기
        </button>
      </div>
    </div>
  );
}

// ───────────── U4: 예약 시간 선택 (시트) ─────────────
function U4ReserveSheet({ selection, onClose, onConfirm, courtsList = [], equipmentList = [] }) {
  const sel = selection || { venue: VENUES[0], day: { day: 5, dow: "화", isHoliday: false, slotUnit: 1 }, hour: 19, court: 1, unit: 1 };
  const [duration, setDuration] = React.useState(sel.unit);

  // 멀티 코트 선택 — 시작은 클릭한 코트
  const initialCourtId = sel.courtId || (courtsList[sel.court - 1] && courtsList[sel.court - 1].id);
  const [selectedCourtIds, setSelectedCourtIds] = React.useState(initialCourtId ? [initialCourtId] : []);

  // 장비 선택 — { [equipmentId]: qty }
  const [eqQty, setEqQty] = React.useState(() => {
    const init = {};
    equipmentList.forEach(eq => { if (eq.default_check) init[eq.id] = 1; });
    return init;
  });

  const v = sel.venue;
  const base = v.discount ? v.price * (1 - v.discount/100) : v.price;
  const courtCount = Math.max(1, selectedCourtIds.length);
  const courtPrice = base * duration * courtCount;
  const equipmentPrice = equipmentList.reduce((sum, eq) => sum + (eqQty[eq.id] || 0) * eq.price, 0);
  const total = courtPrice + equipmentPrice;

  function toggleCourt(cid) {
    setSelectedCourtIds(prev => prev.includes(cid) ? prev.filter(x => x !== cid) : [...prev, cid]);
  }
  function setEq(id, qty) {
    setEqQty(prev => ({ ...prev, [id]: qty }));
  }

  return (
    <div style={{ position: "absolute", inset: 0, background: "rgba(0,0,0,.45)", zIndex: 10, display: "flex", flexDirection: "column", justifyContent: "flex-end" }} onClick={onClose}>
      <div onClick={(e)=>e.stopPropagation()} style={{ background: "#fff", borderRadius: "20px 20px 0 0", maxHeight: "90%", overflowY: "auto", scrollbarWidth: "none" }}>
        <div style={{ padding: "10px 0 6px", display: "flex", justifyContent: "center" }}>
          <div style={{ width: 36, height: 4, background: "var(--gray-200)", borderRadius: 2 }}/>
        </div>
        <div style={{ padding: "8px 16px 16px" }}>
          <div className="row" style={{ marginBottom: 16 }}>
            <div>
              <div className="fw-700" style={{ fontSize: 17, letterSpacing: "-0.4px" }}>예약 옵션</div>
              <div className="text-sub" style={{ fontSize: 12, marginTop: 2 }}>{v.name}</div>
            </div>
            <div className="spacer"/>
            <button type="button" onClick={onClose} style={{ background: "none", border: "none", padding: 4 }}>{I.close(22)}</button>
          </div>

          {/* 시간 요약 */}
          <div className="card" style={{ padding: 14, marginBottom: 14, background: "var(--gray-25)", border: "1px solid var(--line)" }}>
            <div className="row gap-6" style={{ marginBottom: 6 }}>
              {I.clock(14, "var(--brand-500)")}
              <span className="fw-700">{sel.day.date ? `${sel.day.date.getFullYear()}년 ${sel.day.date.getMonth()+1}월 ${sel.day.date.getDate()}일 (${sel.day.dow})` : `${sel.day.day}일 (${sel.day.dow})`}</span>
              {sel.day.isHoliday && <span className="badge badge-warn">공휴일</span>}
            </div>
            <div className="num fw-700" style={{ fontSize: 20, letterSpacing: "-0.5px" }}>
              {String(sel.hour).padStart(2,"0")}:00 ~ {String(sel.hour + duration).padStart(2,"0")}:00
            </div>
          </div>

          {/* 이용 시간 */}
          <div style={{ marginBottom: 16 }}>
            <div className="fw-700" style={{ fontSize: 14, marginBottom: 8 }}>이용 시간</div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 8 }}>
              {[1,2,3].map(h => {
                const disabled = sel.day.isHoliday && h === 1;
                const sel2 = duration === h;
                return (
                  <button key={h} type="button" disabled={disabled} onClick={() => setDuration(h)} style={{
                    height: 56, borderRadius: 12,
                    border: sel2 ? "2px solid var(--brand-500)" : "1px solid var(--line-strong)",
                    background: sel2 ? "var(--brand-50)" : (disabled ? "var(--gray-100)" : "#fff"),
                    color: disabled ? "var(--text-mute)" : (sel2 ? "var(--brand-700)" : "var(--text)"),
                    fontWeight: 700, fontSize: 14,
                  }}>
                    {h}시간
                    <div style={{ fontSize: 11, fontWeight: 500, marginTop: 2, opacity: 0.85 }} className="num">{won(base * h)}</div>
                  </button>
                );
              })}
            </div>
          </div>

          {/* 코트 선택 (다중 가능) */}
          <div style={{ marginBottom: 16 }}>
            <div className="row" style={{ marginBottom: 8 }}>
              <div className="fw-700" style={{ fontSize: 14 }}>코트 선택</div>
              <span className="text-sub" style={{ fontSize: 11, marginLeft: 6 }}>여러 코트 동시 예약 가능</span>
            </div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr 1fr", gap: 8 }}>
              {(courtsList.length ? courtsList : [{ id: 1, name: 'A코트' },{ id: 2, name: 'B코트' },{ id: 3, name: 'C코트' },{ id: 4, name: 'D코트' }]).map(c => {
                const sel2 = selectedCourtIds.includes(c.id);
                return (
                  <button key={c.id} type="button" onClick={() => toggleCourt(c.id)} style={{
                    height: 48, borderRadius: 10,
                    border: sel2 ? "2px solid var(--brand-500)" : "1px solid var(--line-strong)",
                    background: sel2 ? "var(--brand-50)" : "#fff",
                    color: sel2 ? "var(--brand-700)" : "var(--text)",
                    fontWeight: 700, fontSize: 13,
                  }}>{c.name}</button>
                );
              })}
            </div>
          </div>

          {/* 장비 대여 */}
          <div style={{ marginBottom: 18 }}>
            <div className="fw-700" style={{ fontSize: 14, marginBottom: 8 }}>장비 대여</div>
            {equipmentList.length === 0 ? (
              <div className="text-sub" style={{ fontSize: 12, padding: "8px 0" }}>등록된 장비 옵션이 없습니다.</div>
            ) : equipmentList.map(eq => {
              const qty = eqQty[eq.id] || 0;
              const checked = qty > 0;
              return (
                <div key={eq.id} style={{ display: "flex", alignItems: "center", gap: 12, padding: "12px 0", borderBottom: "1px solid var(--line)" }}>
                  <button type="button" onClick={() => setEq(eq.id, checked ? 0 : 1)} style={{
                    width: 22, height: 22, borderRadius: 6,
                    border: checked ? "none" : "1.5px solid var(--line-strong)",
                    background: checked ? "var(--brand-500)" : "#fff",
                    display: "flex", alignItems: "center", justifyContent: "center",
                    padding: 0, cursor: "pointer",
                  }}>{checked && I.check(16, "#fff")}</button>
                  <div style={{ flex: 1 }}>
                    <div className="fw-600" style={{ fontSize: 13.5 }}>{eq.name}</div>
                    {eq.description && <div className="text-sub" style={{ fontSize: 11.5 }}>{eq.description}</div>}
                  </div>
                  {checked && eq.max_qty > 1 && (
                    <div style={{ display: "flex", alignItems: "center", gap: 6 }}>
                      <button type="button" onClick={() => setEq(eq.id, Math.max(1, qty - 1))} style={{ width: 26, height: 26, borderRadius: 13, border: "1px solid var(--line-strong)", background: "#fff" }}>−</button>
                      <span className="num fw-600" style={{ minWidth: 18, textAlign: "center" }}>{qty}</span>
                      <button type="button" onClick={() => setEq(eq.id, Math.min(eq.max_qty, qty + 1))} style={{ width: 26, height: 26, borderRadius: 13, border: "1px solid var(--line-strong)", background: "#fff" }}>+</button>
                    </div>
                  )}
                  <div className="num fw-600" style={{ fontSize: 13.5, minWidth: 60, textAlign: "right" }}>+{won(eq.price * (qty || 1))}</div>
                </div>
              );
            })}
          </div>

          {/* 합계 */}
          <div className="card" style={{ padding: 14, background: "var(--gray-25)", border: "1px solid var(--line)", marginBottom: 14 }}>
            <div className="row" style={{ fontSize: 12.5, color: "var(--text-sub)", marginBottom: 4 }}>
              <span>코트 {courtCount}면 × {duration}시간</span><div className="spacer"/><span className="num">{won(courtPrice)}</span>
            </div>
            {equipmentList.filter(eq => (eqQty[eq.id] || 0) > 0).map(eq => (
              <div key={eq.id} className="row" style={{ fontSize: 12.5, color: "var(--text-sub)", marginBottom: 4 }}>
                <span>{eq.name} × {eqQty[eq.id]}</span><div className="spacer"/><span className="num">+{won(eq.price * eqQty[eq.id])}</span>
              </div>
            ))}
            <div className="hr" style={{ margin: "8px 0" }}/>
            <div className="row" style={{ alignItems: "baseline" }}>
              <span className="fw-700">총 결제 금액</span>
              <div className="spacer"/>
              <span className="fw-700 num text-brand" style={{ fontSize: 20, letterSpacing: "-0.3px" }}>{won(total)}</span>
            </div>
          </div>

          <button type="button" className="btn btn-primary btn-lg btn-block" disabled={selectedCourtIds.length === 0} onClick={() => {
            if (!selectedCourtIds.length) return;
            const equipment = equipmentList.filter(eq => (eqQty[eq.id] || 0) > 0).map(eq => ({ id: eq.id, qty: eqQty[eq.id] }));
            onConfirm && onConfirm({
              venue: v,
              hour: sel.hour,
              day: sel.day,
              duration,
              courtIds: selectedCourtIds,
              equipment,
              total,
            });
          }}>
            무통장입금으로 예약 신청 {selectedCourtIds.length > 1 ? `(${selectedCourtIds.length}코트)` : ''}
          </button>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { U3Detail, U4ReserveSheet, makeWeek });
