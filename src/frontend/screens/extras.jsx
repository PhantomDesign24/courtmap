import React from "react";

// 보조 인터랙션 화면들 — 위치 변경 / 알림 목록 / 검색

// ─────────────────────────────────────────────
// 1. 위치 변경 시트 (LocationSheet)
// ─────────────────────────────────────────────
function LocationSheet({ onClose, onPick, current = "강남구 역삼동" }) {
  const recent = [
    { name: "강남구 역삼동",  sub: "현재 위치",       gps: true },
    { name: "강남구 대치동",  sub: "회사 근처",       fav: true },
    { name: "서초구 서초동",  sub: "최근 검색" },
    { name: "용산구 한남동",  sub: "최근 검색" },
  ];
  const popular = ["송파구 잠실동", "마포구 합정동", "성동구 성수동", "광진구 자양동", "종로구 종로1가"];

  return (
    <div onClick={onClose} style={{
      position: "absolute", inset: 0, background: "rgba(15,19,32,.45)",
      display: "flex", alignItems: "flex-end", zIndex: 50,
    }}>
      <div onClick={(e)=>e.stopPropagation()} style={{
        background: "#fff", width: "100%", borderRadius: "20px 20px 0 0",
        maxHeight: "76%", display: "flex", flexDirection: "column",
        animation: "slideUp .25s ease-out",
      }}>
        <div style={{ padding: "12px 0 8px", display: "flex", justifyContent: "center" }}>
          <div style={{ width: 40, height: 4, borderRadius: 2, background: "var(--gray-200)" }}/>
        </div>
        <div style={{ padding: "0 16px 12px", display: "flex", alignItems: "center" }}>
          <div style={{ fontSize: 17, fontWeight: 800, letterSpacing: "-0.4px" }}>위치 설정</div>
          <div className="spacer"/>
          <button type="button" onClick={onClose} style={{ background: "none", border: "none", padding: 4 }}>
            {I.close(20, "var(--text-sub)")}
          </button>
        </div>

        {/* 검색 */}
        <div style={{ padding: "0 16px 12px" }}>
          <div style={{ display: "flex", alignItems: "center", gap: 8, height: 44, background: "var(--gray-50)", borderRadius: 12, padding: "0 14px" }}>
            {I.search(18, "var(--text-mute)")}
            <input placeholder="동·읍·면 또는 지하철역 검색" style={{
              flex: 1, border: "none", background: "transparent", outline: "none",
              fontSize: 14, color: "var(--text)", fontFamily: "inherit",
            }} />
          </div>
        </div>

        {/* GPS 버튼 */}
        <div style={{ padding: "0 16px 8px" }}>
          <button type="button" style={{
            width: "100%", display: "flex", alignItems: "center", gap: 10,
            background: "var(--brand-50)", border: "none", borderRadius: 12,
            padding: "12px 14px", textAlign: "left", cursor: "pointer",
          }}>
            <div style={{ width: 32, height: 32, borderRadius: 16, background: "var(--brand-500)", display:"flex", alignItems:"center", justifyContent:"center" }}>
              {I.pin(16, "#fff")}
            </div>
            <div style={{ flex: 1 }}>
              <div className="fw-700" style={{ fontSize: 13.5, color: "var(--brand-700)" }}>현재 위치로 설정</div>
              <div style={{ fontSize: 11.5, color: "var(--brand-700)", opacity: 0.75 }}>GPS · 정확도 ±20m</div>
            </div>
          </button>
        </div>

        {/* 스크롤 영역 */}
        <div style={{ flex: 1, overflowY: "auto", padding: "8px 0" }}>
          {/* 최근 / 즐겨찾기 */}
          <div style={{ padding: "8px 16px 6px", fontSize: 12, fontWeight: 700, color: "var(--text-sub)" }}>최근 · 즐겨찾기</div>
          {recent.map((r, i) => (
            <button key={i} type="button" onClick={()=>onPick && onPick(r.name)} style={{
              width: "100%", display: "flex", alignItems: "center", gap: 12,
              padding: "12px 16px", border: "none", background: "none", textAlign: "left", cursor: "pointer",
            }}>
              <div style={{ width: 32, height: 32, borderRadius: 16, background: "var(--gray-50)", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                {r.gps ? I.pin(15, "var(--brand-500)") : r.fav ? I.star(14, "var(--gold-500)") : I.clock(14, "var(--text-mute)")}
              </div>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div className="fw-600" style={{ fontSize: 14, color: r.name === current ? "var(--brand-500)" : "var(--text)" }}>{r.name}</div>
                <div className="text-sub" style={{ fontSize: 11.5 }}>{r.sub}</div>
              </div>
              {r.name === current && I.check(18, "var(--brand-500)")}
            </button>
          ))}

          {/* 인기 동네 */}
          <div style={{ padding: "16px 16px 8px", fontSize: 12, fontWeight: 700, color: "var(--text-sub)" }}>인기 동네</div>
          <div style={{ padding: "0 16px 16px", display: "flex", flexWrap: "wrap", gap: 8 }}>
            {popular.map(p => (
              <button key={p} type="button" onClick={()=>onPick && onPick(p)} style={{
                background: "var(--gray-50)", border: "1px solid var(--line)", borderRadius: 999,
                padding: "8px 14px", fontSize: 12.5, fontWeight: 600, color: "var(--text)", cursor: "pointer",
              }}>{p}</button>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

// ─────────────────────────────────────────────
// 2. 알림 목록 (NotificationList) — 풀스크린
// ─────────────────────────────────────────────
function NotificationList({ onBack, onVenue, items: itemsProp }) {
  const items = itemsProp || [
    { id: 1, type: "system", icon: "bell", title: "알림이 없습니다", sub: "", time: "", unread: false },
  ];

  const iconBg = (t) => ({
    alert:   { bg: "var(--brand-50)",  fg: "var(--brand-500)" },
    confirm: { bg: "#e7f7ec",          fg: "var(--green-500)" },
    remind:  { bg: "#fff8e6",          fg: "var(--gold-500)"  },
    deal:    { bg: "var(--hot-50)",    fg: "var(--hot-500)"   },
    review:  { bg: "#fff8e6",          fg: "var(--gold-500)"  },
    system:  { bg: "var(--gray-100)",  fg: "var(--text-sub)"  },
  }[t]);

  const renderIcon = (name, fg) => {
    if (name === "bell")  return I.bell(18, fg);
    if (name === "check") return I.check(18, fg);
    if (name === "clock") return I.clock(18, fg);
    if (name === "bolt")  return I.bolt(16, fg);
    if (name === "star")  return I.star(16, fg);
    return I.bell(18, fg);
  };

  return (
    <>
      <div style={{ padding: "10px 8px 8px", background: "#fff", display: "flex", alignItems: "center", borderBottom: "1px solid var(--line)" }}>
        <button type="button" onClick={onBack} style={{ background: "none", border: "none", padding: 8 }}>
          {I.back(22, "var(--text)")}
        </button>
        <div style={{ flex: 1, fontSize: 17, fontWeight: 700, letterSpacing: "-0.4px" }}>알림</div>
        <button type="button" style={{ background: "none", border: "none", padding: "8px 12px", fontSize: 13, color: "var(--text-sub)", cursor: "pointer", fontFamily: "inherit" }}>
          모두 읽음
        </button>
      </div>

      <div style={{ background: "var(--bg-soft)", minHeight: "100%" }}>
        {items.map(it => {
          const ic = iconBg(it.type);
          return (
            <button key={it.id} type="button" onClick={()=> it.vId && onVenue && onVenue(it.vId)} style={{
              width: "100%", display: "flex", gap: 12, padding: "14px 16px",
              background: it.unread ? "#fff" : "var(--bg-soft)",
              border: "none", borderBottom: "1px solid var(--line)",
              textAlign: "left", cursor: "pointer", position: "relative",
            }}>
              <div style={{ width: 40, height: 40, borderRadius: 20, background: ic.bg, display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                {renderIcon(it.icon, ic.fg)}
              </div>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div className="fw-700" style={{ fontSize: 13.5, marginBottom: 3, letterSpacing: "-0.3px", lineHeight: 1.35 }}>{it.title}</div>
                <div className="text-sub" style={{ fontSize: 12, marginBottom: 4, lineHeight: 1.4 }}>{it.sub}</div>
                <div className="text-mute" style={{ fontSize: 11 }}>{it.time}</div>
              </div>
              {it.unread && <span style={{ position: "absolute", top: 18, right: 14, width: 7, height: 7, borderRadius: "50%", background: "var(--brand-500)" }}/>}
            </button>
          );
        })}
        <div style={{ padding: "20px 16px 40px", textAlign: "center", color: "var(--text-mute)", fontSize: 12 }}>
          최근 7일 알림만 표시됩니다
        </div>
      </div>
    </>
  );
}

// ─────────────────────────────────────────────
// 3. 검색 화면 (SearchScreen) — 풀스크린
// ─────────────────────────────────────────────
function SearchScreen({ onBack, onVenue }) {
  const [q, setQ] = React.useState("");
  const [matches, setMatches] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const recent  = ["강남 스파이크", "역삼 BNK", "양재 그린코트", "도곡동 코트"];
  const popular = ["스파이크", "그린코트", "셔틀콕", "삼성동", "잠실"];

  React.useEffect(() => {
    if (!q) { setMatches([]); return; }
    setLoading(true);
    const t = setTimeout(async () => {
      try {
        const res = await fetch('/api/venues?q=' + encodeURIComponent(q), { credentials: 'same-origin' });
        const data = await res.json();
        setMatches(data.venues || []);
      } catch (e) { setMatches([]); }
      setLoading(false);
    }, 200);
    return () => clearTimeout(t);
  }, [q]);

  return (
    <>
      {/* 검색바 */}
      <div style={{ padding: "10px 8px 12px", background: "#fff", display: "flex", alignItems: "center", gap: 8, borderBottom: "1px solid var(--line)" }}>
        <button type="button" onClick={onBack} style={{ background: "none", border: "none", padding: 8 }}>
          {I.back(22, "var(--text)")}
        </button>
        <div style={{ flex: 1, display: "flex", alignItems: "center", gap: 8, height: 40, background: "var(--gray-50)", borderRadius: 10, padding: "0 12px" }}>
          {I.search(18, "var(--text-mute)")}
          <input
            autoFocus
            value={q}
            onChange={(e)=>setQ(e.target.value)}
            placeholder="구장명, 지역, 지하철역"
            style={{ flex: 1, border: "none", background: "transparent", outline: "none", fontSize: 14, color: "var(--text)", fontFamily: "inherit" }}
          />
          {q && (
            <button type="button" onClick={()=>setQ("")} style={{ background: "var(--gray-300)", border: "none", borderRadius: "50%", width: 18, height: 18, display: "flex", alignItems: "center", justifyContent: "center", padding: 0 }}>
              {I.close(12, "#fff")}
            </button>
          )}
        </div>
      </div>

      {/* 결과 / 빈 상태 */}
      {q ? (
        <div style={{ background: "#fff" }}>
          {matches.length === 0 ? (
            <div style={{ padding: "60px 24px", textAlign: "center", color: "var(--text-sub)" }}>
              <div style={{ fontSize: 14, marginBottom: 8 }}>"{q}" 검색 결과가 없어요</div>
              <div style={{ fontSize: 12, color: "var(--text-mute)" }}>구장명·지하철역·동 이름으로 검색해보세요</div>
            </div>
          ) : (
            <>
              <div style={{ padding: "12px 16px 8px", fontSize: 12, color: "var(--text-sub)" }}>
                <span className="fw-700 text-brand">{matches.length}</span>개 결과
              </div>
              {matches.map(v => (
                <button key={v.id} type="button" onClick={()=>onVenue && onVenue(v.id)} style={{
                  width: "100%", display: "flex", gap: 12, padding: "12px 16px",
                  background: "none", border: "none", borderBottom: "1px solid var(--line)",
                  textAlign: "left", cursor: "pointer",
                }}>
                  <Photo src={v.img} radius={10} style={{ width: 60, height: 60, flexShrink: 0 }} />
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div className="fw-700" style={{ fontSize: 14, marginBottom: 3 }}>
                      {highlightMatch(v.name, q)}
                    </div>
                    <div className="text-sub" style={{ fontSize: 12, marginBottom: 4 }}>{v.area}</div>
                    <div className="row gap-4" style={{ fontSize: 11.5, color: "var(--text-sub)" }}>
                      {I.star(11)} <span className="fw-600" style={{ color: "var(--text)" }}>{v.rating}</span>
                      <span>·</span>
                      <span>도보 {v.walkMin}분</span>
                      <span>·</span>
                      <span className="num">{won(v.price)}</span>
                    </div>
                  </div>
                </button>
              ))}
            </>
          )}
        </div>
      ) : (
        <div style={{ background: "#fff", paddingBottom: 24 }}>
          {/* 최근 검색 */}
          <div style={{ padding: "16px 16px 8px", display: "flex", alignItems: "center" }}>
            <div style={{ fontSize: 13, fontWeight: 700, color: "var(--text)" }}>최근 검색</div>
            <div className="spacer"/>
            <button type="button" style={{ background: "none", border: "none", fontSize: 12, color: "var(--text-sub)", padding: 4, fontFamily: "inherit", cursor: "pointer" }}>전체 삭제</button>
          </div>
          <div style={{ padding: "0 16px 12px", display: "flex", flexWrap: "wrap", gap: 8 }}>
            {recent.map(r => (
              <button key={r} type="button" onClick={()=>setQ(r)} style={{
                background: "var(--gray-50)", border: "1px solid var(--line)", borderRadius: 999,
                padding: "7px 12px 7px 14px", fontSize: 12.5, fontWeight: 600, color: "var(--text)",
                display: "flex", alignItems: "center", gap: 6, cursor: "pointer",
              }}>
                {r}
                <span style={{ color: "var(--text-mute)", marginLeft: 2 }}>{I.close(12, "var(--text-mute)")}</span>
              </button>
            ))}
          </div>

          <div className="hr-thick"/>

          {/* 인기 검색어 */}
          <div style={{ padding: "16px 16px 8px", fontSize: 13, fontWeight: 700 }}>인기 검색어</div>
          <div style={{ padding: "0 16px 16px" }}>
            {popular.map((p, i) => (
              <button key={p} type="button" onClick={()=>setQ(p)} style={{
                width: "100%", display: "flex", alignItems: "center", gap: 14,
                padding: "10px 0", background: "none", border: "none", textAlign: "left", cursor: "pointer",
              }}>
                <span className="fw-800" style={{ fontSize: 15, color: i < 3 ? "var(--brand-500)" : "var(--text)", width: 18 }}>{i+1}</span>
                <span className="fw-600" style={{ fontSize: 14 }}>{p}</span>
                <div className="spacer"/>
                <span style={{ fontSize: 11, color: i % 2 === 0 ? "var(--green-500)" : "var(--text-mute)", fontWeight: 700 }}>
                  {i % 2 === 0 ? "↑ +2" : "—"}
                </span>
              </button>
            ))}
          </div>
        </div>
      )}
    </>
  );
}

function highlightMatch(text, q) {
  if (!q) return text;
  const idx = text.indexOf(q);
  if (idx < 0) return text;
  return (
    <>
      {text.slice(0, idx)}
      <span style={{ color: "var(--brand-500)" }}>{q}</span>
      {text.slice(idx + q.length)}
    </>
  );
}

// ─────────────────────────────────────────────
// 4. 입장 안내 (EntryGuide) — 풀스크린, 예약번호·QR·운영자 정보
// ─────────────────────────────────────────────
function EntryGuide({ onBack, reservation }) {
  const r = reservation || {
    venue: VENUES[0],
    day: "5월 5일 (화)",
    time: "19:00 ~ 21:00",
    court: "A코트",
    code: "CMAP-2026-5J7K2",
    name: "박지훈",
  };
  const v = r.venue;

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden", background: "var(--bg-soft)" }}>
      <div style={{ padding: "10px 12px", display: "flex", alignItems: "center", background: "#fff", borderBottom: "1px solid var(--line)", flexShrink: 0 }}>
        <button type="button" onClick={onBack} style={{ background:"none", border:"none", padding: 6 }}>{I.back(22)}</button>
        <div className="fw-700" style={{ fontSize: 16, marginLeft: 4 }}>입장 안내</div>
      </div>

      <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none" }}>
        {/* Hero — 시간 임박 카운트다운 */}
        <div style={{ background: "var(--brand-500)", color: "#fff", padding: "20px 16px 24px", textAlign: "center" }}>
          <div style={{ fontSize: 12, opacity: 0.85, marginBottom: 6, letterSpacing: 0.3 }}>예약 시작까지</div>
          <div className="fw-700 num" style={{ fontSize: 38, letterSpacing: "-1px", lineHeight: 1, marginBottom: 8 }}>
            03:42:18
          </div>
          <div style={{ fontSize: 13, opacity: 0.9 }}>{r.day} · {r.time}</div>
        </div>

        <div style={{ padding: 12 }}>
          {/* 본인 확인 카드 + QR */}
          <div className="card" style={{ padding: 20, marginBottom: 10, textAlign: "center" }}>
            <div className="fw-800" style={{ fontSize: 24, letterSpacing: "-0.5px", marginBottom: 6, color: "var(--text)" }}>{r.name}</div>
            <div className="num" style={{ fontSize: 14, color: "var(--text-sub)", marginBottom: 14, letterSpacing: 0.3 }}>{r.phone || ''}</div>
            <div style={{ display: "inline-block", padding: 10, background: "#fff", border: "1px solid var(--line)", borderRadius: 12 }}>
              <img
                src={`https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=0&data=${encodeURIComponent(r.code || '')}`}
                width={180} height={180} alt={r.code}
                style={{ display: "block" }}
              />
            </div>
            <div className="num fw-700" style={{ fontSize: 13, marginTop: 10, letterSpacing: 0.5 }}>{r.code}</div>
            <div className="hr" style={{ margin: "12px 0" }}/>
            <div style={{ fontSize: 12.5, color: "var(--text-sub)", lineHeight: 1.6 }}>
              QR 을 보여주거나, 프론트에서 <span className="fw-700" style={{ color: "var(--text)" }}>"코트맵 {r.name}"</span> 라고<br/>
              알려주시면 됩니다.
            </div>
          </div>

          {/* 구장 정보 */}
          <div className="card" style={{ padding: 14, marginBottom: 10 }}>
            <div className="row gap-10" style={{ marginBottom: 12 }}>
              <Photo src={v.img} radius={10} style={{ width: 52, height: 52, flexShrink: 0 }} />
              <div style={{ flex: 1, minWidth: 0 }}>
                <div className="fw-700" style={{ fontSize: 14.5, marginBottom: 2 }}>{v.name}</div>
                <div className="text-sub" style={{ fontSize: 12 }}>{v.area} · 도보 {v.walkMin}분</div>
              </div>
            </div>
            <div className="hr" style={{ margin: "0 0 10px" }}/>
            <div style={{ display: "grid", gridTemplateColumns: "auto 1fr", columnGap: 14, rowGap: 8, fontSize: 13 }}>
              <span className="text-sub">코트</span><span className="fw-600">{r.court}</span>
              <span className="text-sub">시간</span><span className="fw-600 num">{r.time}</span>
              <span className="text-sub">주소</span>
              <span className="fw-500">서울 강남구 테헤란로 123 · B2층</span>
              <span className="text-sub">전화</span>
              <span className="fw-600 num">02-555-3849</span>
            </div>
            <div style={{ display: "flex", gap: 8, marginTop: 12 }}>
              <button type="button" className="btn btn-line btn-sm" style={{ flex: 1, height: 38 }}>
                {I.pin(14)} 길찾기
              </button>
              <button type="button" className="btn btn-line btn-sm" style={{ flex: 1, height: 38 }}>
                전화 02-555-3849
              </button>
            </div>
          </div>

          {/* 입장 절차 */}
          <div className="card" style={{ padding: 16, marginBottom: 10 }}>
            <div className="fw-700" style={{ fontSize: 14, marginBottom: 12 }}>입장 절차</div>
            {[
              { n: "1", t: "예약 5분 전 도착",        d: "B2층 프론트로 와주세요" },
              { n: "2", t: "예약번호 또는 이름 알리기", d: '"코트맵 박지훈"이라고 하시면 됩니다' },
              { n: "3", t: "코트 키 받기",             d: "본인 인증 후 A코트 키를 드려요" },
              { n: "4", t: "이용 후 키 반납",          d: "퇴장 시 프론트에 반납해주세요" },
            ].map((s, i) => (
              <div key={i} className="row gap-12" style={{ alignItems: "flex-start", marginBottom: i === 3 ? 0 : 12 }}>
                <div style={{
                  width: 26, height: 26, borderRadius: 13,
                  background: "var(--brand-50)", color: "var(--brand-700)",
                  display: "flex", alignItems: "center", justifyContent: "center",
                  fontSize: 13, fontWeight: 700, flexShrink: 0,
                }}>{s.n}</div>
                <div style={{ flex: 1, paddingTop: 2 }}>
                  <div className="fw-700" style={{ fontSize: 13.5, marginBottom: 2 }}>{s.t}</div>
                  <div className="text-sub" style={{ fontSize: 12 }}>{s.d}</div>
                </div>
              </div>
            ))}
          </div>

          {/* 준비물 */}
          <div className="card" style={{ padding: 14, marginBottom: 10 }}>
            <div className="fw-700" style={{ fontSize: 14, marginBottom: 10 }}>준비물 체크리스트</div>
            {[
              { t: "운동복 / 실내화", req: true },
              { t: "라켓 (대여 가능)",  req: false, note: "+5,000원" },
              { t: "셔틀콕 (대여 가능)", req: false, note: "+3,000원" },
              { t: "물 / 수건",        req: true },
            ].map((it, i) => (
              <div key={i} className="row" style={{ padding: "6px 0", borderBottom: i === 3 ? "none" : "1px dashed var(--line)" }}>
                <span style={{ fontSize: 13 }}>{it.t}</span>
                <div className="spacer"/>
                {it.req
                  ? <span className="badge badge-soft" style={{ fontSize: 10.5 }}>필수</span>
                  : <span className="text-mute" style={{ fontSize: 11.5 }}>{it.note}</span>}
              </div>
            ))}
          </div>

          {/* 주의 */}
          <div style={{ background: "var(--gold-50, #fff8e6)", border: "1px solid #ffe6a8", borderRadius: 14, padding: 14, marginBottom: 10 }}>
            <div className="row gap-8" style={{ marginBottom: 6 }}>
              <span style={{ fontSize: 14 }}>⚠️</span>
              <span className="fw-700" style={{ fontSize: 13 }}>꼭 확인해주세요</span>
            </div>
            <div style={{ fontSize: 12, color: "var(--text-sub)", lineHeight: 1.65 }}>
              · 시작 10분 후 미입장 시 자동 노쇼 처리<br/>
              · 다른 사람 양도 불가 (본인 확인 필수)<br/>
              · 시설 파손 시 별도 청구
            </div>
          </div>

          <div style={{ height: 24 }}/>
        </div>
      </div>

      <div style={{ flexShrink: 0, padding: "10px 16px 14px", background: "#fff", borderTop: "1px solid var(--line)", display: "flex", gap: 10 }}>
        <button type="button" className="btn btn-md btn-line" style={{ width: 120 }}>예약 취소</button>
        <button type="button" className="btn btn-primary btn-md" style={{ flex: 1 }}>길찾기 시작</button>
      </div>
    </div>
  );
}

// ─────────────────────────────────────────────
// 5. 예약 상세 (ReservationDetail) — U7에서 카드 탭하면 진입
// ─────────────────────────────────────────────
function ReservationDetail({ reservation, onBack, onEntry, onVenue }) {
  const r = reservation;
  if (!r) return null;
  const v = r.venue;
  const isConfirmed = r.status === "confirmed";
  const isPending   = r.status === "pending";
  const isPast      = r.status === "done" || r.status === "noshow" || r.status === "canceled";

  const statusCfg = {
    confirmed: { label: "확정",    bg: "var(--green-50, #e7f7ec)", color: "var(--green-700, #1e7d3a)", dot: "var(--green-500)" },
    pending:   { label: "입금 대기", bg: "#fff8e6",                 color: "#7a5b00",                  dot: "var(--gold-500)" },
    done:      { label: "이용 완료", bg: "var(--gray-100)",          color: "var(--gray-700)",         dot: "var(--gray-500)" },
    noshow:    { label: "노쇼",      bg: "var(--hot-50)",             color: "var(--hot-700)",          dot: "var(--hot-500)" },
    canceled:  { label: "취소됨",    bg: "var(--gray-100)",          color: "var(--gray-500)",         dot: "var(--gray-300)" },
  }[r.status] || { label: "—", bg: "var(--gray-100)", color: "var(--text-sub)", dot: "var(--gray-500)" };

  // 결제 내역 계산 (price를 코트 + 옵션으로 분해, 적당히 실제같이)
  const courtPrice = r.price ? Math.round(r.price * 0.93 / 1000) * 1000 : 49000;
  const extras = r.price ? r.price - courtPrice : 3000;

  const heroBg = isConfirmed ? "var(--brand-500)" : (isPending ? "#fff" : "var(--gray-50)");
  const heroFg = isConfirmed ? "#fff" : "var(--text)";

  return (
    <div style={{ flex: 1, display: "flex", flexDirection: "column", overflow: "hidden", background: "var(--bg-soft)" }}>
      <div style={{ padding: "10px 12px", display: "flex", alignItems: "center", background: "#fff", borderBottom: "1px solid var(--line)", flexShrink: 0 }}>
        <button type="button" onClick={onBack} style={{ background:"none", border:"none", padding: 6 }}>{I.back(22)}</button>
        <div className="fw-700" style={{ fontSize: 16, marginLeft: 4 }}>예약 상세</div>
        <div className="spacer"/>
        <button type="button" style={{ background: "none", border: "none", padding: 6 }}>
          {I.share(20, "var(--text-sub)")}
        </button>
      </div>

      <div style={{ flex: 1, overflowY: "auto", scrollbarWidth: "none" }}>
        {/* Hero */}
        <div style={{ background: heroBg, color: heroFg, padding: "20px 16px 24px", position: "relative", overflow: "hidden" }}>
          {isConfirmed && (
            <div style={{ position: "absolute", right: -40, top: -40, width: 160, height: 160, borderRadius: "50%", background: "rgba(255,255,255,.08)" }}/>
          )}
          <div className="row gap-6" style={{ marginBottom: 14, position: "relative" }}>
            <span className="badge" style={{ background: isConfirmed ? "rgba(255,255,255,.2)" : statusCfg.bg, color: isConfirmed ? "#fff" : statusCfg.color }}>
              ● {statusCfg.label}
            </span>
            {r.recurring && <span className="badge" style={{ background: isConfirmed ? "rgba(255,255,255,.18)" : "var(--brand-50)", color: isConfirmed ? "#fff" : "var(--brand-700)" }}>↻ 정기</span>}
          </div>
          <div className="fw-700" style={{ fontSize: 19, marginBottom: 6, letterSpacing: "-0.4px", position: "relative" }}>{v.name}</div>
          <div className="num fw-800" style={{ fontSize: 28, letterSpacing: "-0.6px", marginBottom: 4, position: "relative" }}>{r.time}</div>
          <div style={{ fontSize: 13.5, opacity: isConfirmed ? 0.9 : 0.7, position: "relative" }}>{r.day} · {r.court}</div>

          {isConfirmed && r.inDays === "오늘" && (
            <button type="button" onClick={onEntry} style={{
              marginTop: 16, width: "100%", background: "#fff", color: "var(--brand-600)",
              border: "none", borderRadius: 12, padding: "12px 14px", fontSize: 14, fontWeight: 700,
              cursor: "pointer", fontFamily: "inherit", position: "relative",
              display: "flex", alignItems: "center", justifyContent: "center", gap: 6,
            }}>
              {I.bolt(15, "var(--brand-600)")} 입장 안내 보기
            </button>
          )}
          {isPending && (
            <div style={{
              marginTop: 14, background: "#fff8e6", border: "1px solid #ffe6a8",
              borderRadius: 10, padding: "10px 12px", fontSize: 12.5, color: "#7a5b00",
            }}>
              ⏰ 오늘 23:59까지 입금하지 않으면 자동 취소됩니다
            </div>
          )}
        </div>

        <div style={{ padding: 12 }}>
          {/* 예약번호 */}
          <div className="card" style={{ padding: 14, marginBottom: 10 }}>
            <div className="row" style={{ marginBottom: 8 }}>
              <span className="text-sub" style={{ fontSize: 12 }}>예약번호</span>
              <div className="spacer"/>
              <button type="button" className="btn btn-sm btn-line" style={{ height: 28, padding: "0 10px", fontSize: 11.5 }}>
                {I.copy(11)} 복사
              </button>
            </div>
            <div className="fw-700 num" style={{ fontSize: 16, letterSpacing: "0.3px" }}>
              CMAP-2026-{r.id ? r.id.toUpperCase().padStart(5,"0") : "5J7K2"}
            </div>
          </div>

          {/* 예약 정보 */}
          <div className="card" style={{ padding: 14, marginBottom: 10 }}>
            <button type="button" onClick={()=> onVenue && onVenue(v.id)} style={{ width: "100%", padding: 0, background: "none", border: "none", textAlign: "left", cursor: "pointer", display: "flex", gap: 12, alignItems: "center", marginBottom: 12 }}>
              <Photo src={v.img} radius={10} style={{ width: 56, height: 56, flexShrink: 0 }} />
              <div style={{ flex: 1, minWidth: 0 }}>
                <div className="fw-700" style={{ fontSize: 14.5, marginBottom: 2 }}>{v.name}</div>
                <div className="text-sub" style={{ fontSize: 12 }}>{v.area} · 도보 {v.walkMin}분</div>
              </div>
              {I.chevR(18, "var(--text-mute)")}
            </button>
            <div className="hr" style={{ margin: "0 0 12px" }}/>
            <div style={{ display: "grid", gridTemplateColumns: "70px 1fr", rowGap: 9, fontSize: 13 }}>
              <span className="text-sub">날짜</span><span className="fw-600">2026년 {r.day}</span>
              <span className="text-sub">시간</span><span className="fw-600 num">{r.time}</span>
              <span className="text-sub">코트</span><span className="fw-600">{r.court}</span>
              <span className="text-sub">예약자</span><span className="fw-600">박지훈 · 010-****-3849</span>
              {r.recurring && (<>
                <span className="text-sub">정기</span>
                <span className="fw-600">{r.recurringInfo || "매주 일요일 · 4주차 중 1회차"}</span>
              </>)}
            </div>
          </div>

          {/* 결제 내역 */}
          <div className="card" style={{ padding: 14, marginBottom: 10 }}>
            <div className="fw-700" style={{ fontSize: 14, marginBottom: 12 }}>결제 내역</div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr auto", rowGap: 8, fontSize: 13 }}>
              <span className="text-sub">코트 이용</span><span className="num">{won(courtPrice)}</span>
              {extras > 0 && (<>
                <span className="text-sub">대여 (셔틀콕)</span><span className="num">{won(extras)}</span>
              </>)}
            </div>
            <div className="hr" style={{ margin: "10px 0" }}/>
            <div className="row">
              <span className="fw-700" style={{ fontSize: 13.5 }}>결제 금액</span>
              <div className="spacer"/>
              <span className="fw-800 num text-brand" style={{ fontSize: 18, letterSpacing: "-0.3px" }}>{won(r.price)}</span>
            </div>
            <div className="hr" style={{ margin: "10px 0" }}/>
            <div className="row" style={{ fontSize: 12, color: "var(--text-sub)" }}>
              <span>결제 수단</span>
              <div className="spacer"/>
              <span>무통장입금 · 신한 110-432-589021</span>
            </div>
          </div>

          {/* 환불 정책 (확정/대기 시) */}
          {(isConfirmed || isPending) && (
            <div className="card" style={{ padding: 14, marginBottom: 10 }}>
              <div className="fw-700" style={{ fontSize: 14, marginBottom: 10 }}>취소·환불 안내</div>
              <div style={{ display: "grid", gridTemplateColumns: "auto 1fr auto", columnGap: 10, rowGap: 8, fontSize: 13, alignItems: "center" }}>
                <span style={{ color: "var(--green-500)", fontSize: 10, lineHeight: 1 }}>●</span><span>이용 24시간 전</span><span className="fw-700 num">100%</span>
                <span style={{ color: "var(--gold-500)", fontSize: 10, lineHeight: 1 }}>●</span><span>이용 1시간 전</span><span className="fw-700 num">50%</span>
                <span style={{ color: "var(--hot-500)", fontSize: 10, lineHeight: 1 }}>●</span><span>이용 1시간 이내</span><span className="fw-700 num text-mute">환불 불가</span>
              </div>
            </div>
          )}

          {/* 운영자 정보 */}
          <div className="card" style={{ padding: 14, marginBottom: 10, display: "flex", alignItems: "center", gap: 12 }}>
            <div style={{ width: 38, height: 38, borderRadius: 19, background: "var(--gray-100)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: 14, fontWeight: 700, color: "var(--text-sub)", flexShrink: 0 }}>
              {v.name.slice(0, 1)}
            </div>
            <div style={{ flex: 1, minWidth: 0 }}>
              <div className="fw-700" style={{ fontSize: 13.5 }}>운영자에게 문의</div>
              <div className="text-sub" style={{ fontSize: 11.5 }}>02-555-3849 · 카카오톡 채널</div>
            </div>
            <button type="button" className="btn btn-sm btn-line" style={{ height: 32 }}>채팅</button>
          </div>

          {/* 지난 예약 — 다시 예약/리뷰 */}
          {r.status === "done" && (
            <div className="card" style={{ padding: 14, marginBottom: 10 }}>
              <div className="fw-700" style={{ fontSize: 14, marginBottom: 10 }}>이번 예약은 어떠셨나요?</div>
              <div className="row" style={{ marginBottom: 10 }}>
                {[1,2,3,4,5].map(n => (
                  <span key={n} style={{ fontSize: 22, color: "var(--gold-500)", marginRight: 4 }}>★</span>
                ))}
              </div>
              <button type="button" className="btn btn-primary btn-md btn-block">리뷰 작성하고 500P 받기</button>
            </div>
          )}

          <div style={{ height: 24 }}/>
        </div>
      </div>

      {/* Footer */}
      {!isPast && (
        <div style={{ flexShrink: 0, padding: "10px 16px 14px", background: "#fff", borderTop: "1px solid var(--line)", display: "flex", gap: 10 }}>
          <button type="button" className="btn btn-md btn-line" style={{ width: 110, color: "var(--hot-700)" }}>예약 취소</button>
          {isConfirmed
            ? <button type="button" className="btn btn-primary btn-md" onClick={onEntry} style={{ flex: 1 }}>입장 안내 보기</button>
            : <button type="button" className="btn btn-primary btn-md" style={{ flex: 1 }}>입금 계좌 보기</button>
          }
        </div>
      )}
      {r.status === "done" && (
        <div style={{ flexShrink: 0, padding: "10px 16px 14px", background: "#fff", borderTop: "1px solid var(--line)", display: "flex", gap: 10 }}>
          <button type="button" className="btn btn-md btn-line" style={{ flex: 1 }} onClick={()=> onVenue && onVenue(v.id)}>다시 예약</button>
          <button type="button" className="btn btn-primary btn-md" style={{ flex: 1 }}>리뷰 작성</button>
        </div>
      )}
    </div>
  );
}

Object.assign(window, { LocationSheet, NotificationList, SearchScreen, EntryGuide, ReservationDetail });
