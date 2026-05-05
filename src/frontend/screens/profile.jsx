import React from "react";

// 마이페이지 (U8) — main / favorites / coupons / membership 4개 뷰

function logoutSubmit() {
  const f = document.createElement('form');
  f.method = 'POST';
  f.action = '/logout';
  document.body.appendChild(f);
  f.submit();
}

function ProfileMain({ user, stats, onSection }) {
  const score = (user && user.trust_score) ?? 100;
  const scoreLabel =
    score >= 80 ? '매우 좋음' :
    score >= 60 ? '양호'      :
    score >= 40 ? '주의'      : '제한';
  const scoreCls =
    score >= 80 ? 'badge-success' :
    score >= 60 ? 'badge-soft'    :
    score >= 40 ? 'badge-warn'    : 'badge-hot';
  const initial = (user.name || '회').slice(0, 1);

  const menu = [
    { id: 'favorites',  l: '단골 구장',     sub: stats.favorites_count > 0 ? `${stats.favorites_count}곳 · 빈자리 알림 ON` : '아직 등록된 단골이 없어요' },
    { id: 'coupons',    l: '쿠폰함',         sub: stats.coupons_count > 0 ? `보유 ${stats.coupons_count}장` : '보유 쿠폰 없음' },
    { id: 'membership', l: '내 멤버십',      sub: stats.membership_remaining ? `잔여 ${stats.membership_remaining}시간` : '가입된 멤버십 없음' },
    { id: 'bank',       l: '환불계좌',       sub: user.refund_bank_name ? `${user.refund_bank_name} ${user.refund_bank_account_masked || ''}` : '미등록' },
    { id: 'notif',      l: '알림 설정',      sub: null },
    { id: 'support',    l: '고객센터',       sub: null },
    { id: 'logout',     l: '로그아웃',       sub: null },
  ];

  return (
    <>
      <div style={{ padding: "20px 16px 16px", background: "#fff" }}>
        <div style={{ fontSize: 22, fontWeight: 800, letterSpacing: "-0.5px", marginBottom: 16 }}>마이</div>
        <div style={{ display: "flex", gap: 12, alignItems: "center", marginBottom: 16 }}>
          <div style={{ width: 56, height: 56, borderRadius: 28, background: "var(--brand-100)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: 22, fontWeight: 800, color: "var(--brand-700)" }}>{initial}</div>
          <div style={{ flex: 1 }}>
            <div className="fw-700" style={{ fontSize: 16 }}>{user.name || '회원'}</div>
            <div className="text-sub" style={{ fontSize: 12 }}>{user.email}</div>
          </div>
        </div>
        <div className="card" style={{ padding: 14, display: "flex", alignItems: "center", gap: 12, background: "var(--brand-50)", border: "none" }}>
          <div style={{ width: 44, height: 44, borderRadius: 22, background: "#fff", display: "flex", alignItems: "center", justifyContent: "center", fontSize: 18 }}>★</div>
          <div style={{ flex: 1 }}>
            <div className="text-sub" style={{ fontSize: 11.5, marginBottom: 2 }}>신뢰점수</div>
            <div className="row gap-6" style={{ alignItems: "baseline" }}>
              <span className="fw-700 num" style={{ fontSize: 22 }}>{score}</span>
              <span className="text-sub" style={{ fontSize: 11.5 }}>/ 100</span>
              <span className={`badge ${scoreCls}`} style={{ marginLeft: 4 }}>{scoreLabel}</span>
            </div>
          </div>
        </div>
      </div>
      <div className="hr-thick"/>
      <div style={{ background: "#fff" }}>
        {menu.map((it, i) => (
          <button key={i} type="button"
            onClick={() => onSection(it.id)}
            style={{ width: "100%", display: "flex", alignItems: "center", padding: "16px", background: "none", border: "none", borderBottom: i === menu.length - 1 ? 'none' : "1px solid var(--line)", textAlign: "left", cursor: "pointer" }}>
            <div style={{ flex: 1 }}>
              <div className="fw-600" style={{ fontSize: 14, color: it.id === 'logout' ? 'var(--hot-500)' : 'var(--text)' }}>{it.l}</div>
              {it.sub && <div className="text-sub" style={{ fontSize: 12, marginTop: 2 }}>{it.sub}</div>}
            </div>
            {it.id !== 'logout' && I.chevR(18, "var(--text-mute)")}
          </button>
        ))}
      </div>
    </>
  );
}

function ProfileFavorites({ onBack, favorites = [] }) {
  return (
    <>
      <div style={{ padding: "10px 8px 8px", background: "#fff", display: "flex", alignItems: "center", borderBottom: "1px solid var(--line)" }}>
        <button type="button" onClick={onBack} style={{ background: "none", border: "none", padding: 8 }}>{I.back(22)}</button>
        <div style={{ flex: 1, fontSize: 17, fontWeight: 700 }}>단골 구장</div>
      </div>
      <div style={{ background: "var(--bg-soft)", padding: 12, minHeight: "100%" }}>
        {favorites.length === 0 ? (
          <div style={{ padding: "60px 24px", textAlign: "center", color: "var(--text-sub)", fontSize: 13 }}>
            아직 단골 구장이 없습니다.<br/>구장 상세 페이지에서 ♥ 를 눌러 등록하세요.
          </div>
        ) : (
          <>
            <div style={{ background: "var(--brand-50)", borderRadius: 12, padding: "10px 14px", marginBottom: 12, fontSize: 12.5, color: "var(--brand-700)", fontWeight: 600 }}>
              ⚡ 빈자리 알림 ON — 자리 나면 즉시 알려드려요
            </div>
            {favorites.map(v => (
              <div key={v.id} className="card" style={{ background: "#fff", padding: 12, marginBottom: 10, display: "flex", gap: 12, alignItems: "center" }}>
                <Photo src={v.img} radius={10} style={{ width: 60, height: 60, flexShrink: 0 }}/>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div className="fw-700" style={{ fontSize: 14, marginBottom: 3 }}>{v.name}</div>
                  <div className="text-sub" style={{ fontSize: 12, marginBottom: 4 }}>{v.area}</div>
                  <div className="row gap-4">
                    {v.notify_open_slot ? <span className="badge badge-success" style={{ fontSize: 10.5 }}>● 알림 ON</span> : <span className="badge badge-gray" style={{ fontSize: 10.5 }}>알림 OFF</span>}
                    <span className="num text-sub" style={{ fontSize: 11.5, marginLeft: 6 }}>총 {v.use_count || 0}회 이용</span>
                  </div>
                </div>
              </div>
            ))}
          </>
        )}
      </div>
    </>
  );
}

function ProfileCoupons({ onBack, coupons = [] }) {
  return (
    <>
      <div style={{ padding: "10px 8px 8px", background: "#fff", display: "flex", alignItems: "center", borderBottom: "1px solid var(--line)" }}>
        <button type="button" onClick={onBack} style={{ background: "none", border: "none", padding: 8 }}>{I.back(22)}</button>
        <div style={{ flex: 1, fontSize: 17, fontWeight: 700 }}>쿠폰함</div>
      </div>
      <div style={{ background: "var(--bg-soft)", padding: 12, minHeight: "100%" }}>
        {coupons.length === 0 ? (
          <div style={{ padding: "60px 24px", textAlign: "center", color: "var(--text-sub)", fontSize: 13 }}>
            보유 쿠폰이 없습니다.
          </div>
        ) : (
          coupons.map((c, i) => (
            <div key={i} style={{ background: "#fff", borderRadius: 14, marginBottom: 10, display: "flex", overflow: "hidden", border: "1px solid var(--line)" }}>
              <div style={{ background: "var(--brand-500)", color: "#fff", padding: "16px 14px", display: "flex", flexDirection: "column", justifyContent: "center", alignItems: "center", minWidth: 100 }}>
                <div className="fw-800 num" style={{ fontSize: 22, letterSpacing: "-0.5px" }}>{c.amount_label}</div>
                <div style={{ fontSize: 11, opacity: 0.85 }}>{c.unit}</div>
              </div>
              <div style={{ flex: 1, padding: 14 }}>
                <div className="fw-700" style={{ fontSize: 13.5, marginBottom: 4 }}>{c.title}</div>
                <div className="text-sub" style={{ fontSize: 11.5, marginBottom: 2 }}>~ {c.expires}</div>
                <div className="text-sub" style={{ fontSize: 11.5 }}>{c.min_amount}</div>
              </div>
            </div>
          ))
        )}
      </div>
    </>
  );
}

function ProfileMembership({ onBack, membership }) {
  return (
    <>
      <div style={{ padding: "10px 8px 8px", background: "#fff", display: "flex", alignItems: "center", borderBottom: "1px solid var(--line)" }}>
        <button type="button" onClick={onBack} style={{ background: "none", border: "none", padding: 8 }}>{I.back(22)}</button>
        <div style={{ flex: 1, fontSize: 17, fontWeight: 700 }}>내 멤버십</div>
      </div>
      <div style={{ background: "var(--bg-soft)", padding: 12, minHeight: "100%" }}>
        {!membership ? (
          <div style={{ padding: "60px 24px", textAlign: "center", color: "var(--text-sub)", fontSize: 13 }}>
            가입된 멤버십이 없습니다.<br/>구장 상세에서 가입할 수 있습니다.
          </div>
        ) : (
          <>
            <div style={{ background: "linear-gradient(135deg, var(--brand-500), var(--brand-700))", borderRadius: 16, padding: 18, color: "#fff", marginBottom: 12, position: "relative", overflow: "hidden" }}>
              <div style={{ position: "absolute", right: -30, top: -30, width: 140, height: 140, borderRadius: "50%", background: "rgba(255,255,255,.08)" }}/>
              <div style={{ fontSize: 12, opacity: 0.85, marginBottom: 4, letterSpacing: 0.5 }}>{membership.venue_name}</div>
              <div className="fw-800" style={{ fontSize: 22, marginBottom: 14, letterSpacing: "-0.5px" }}>{membership.name}</div>
              <div className="row" style={{ alignItems: "baseline" }}>
                <span style={{ fontSize: 12, opacity: 0.85, marginRight: 6 }}>잔여</span>
                <span className="fw-800 num" style={{ fontSize: 28, letterSpacing: "-0.5px" }}>{membership.hours_remaining}</span>
                <span style={{ fontSize: 13, opacity: 0.85, marginLeft: 4 }}>/ {membership.hours_total}시간</span>
                <div className="spacer"/>
                <span style={{ fontSize: 11.5, opacity: 0.85 }}>~ {membership.expires_at_short}</span>
              </div>
              <div style={{ height: 4, background: "rgba(255,255,255,.2)", borderRadius: 2, marginTop: 10, overflow: "hidden" }}>
                <div style={{ width: `${Math.round((membership.hours_remaining / membership.hours_total) * 100)}%`, height: "100%", background: "#fff", borderRadius: 2 }}/>
              </div>
            </div>
          </>
        )}
      </div>
    </>
  );
}

function ProfileScreen() {
  const [view, setView] = React.useState("main");
  const data = window.__DATA__ || {};
  const user = data.me_user || window.__USER__ || {};
  const stats = data.stats || {};

  if (view === "favorites")  return <ProfileFavorites  onBack={() => setView("main")} favorites={data.favorites} />;
  if (view === "coupons")    return <ProfileCoupons    onBack={() => setView("main")} coupons={data.coupons} />;
  if (view === "membership") return <ProfileMembership onBack={() => setView("main")} membership={data.membership} />;

  return (
    <ProfileMain
      user={user}
      stats={stats}
      onSection={(id) => {
        if (id === 'favorites' || id === 'coupons' || id === 'membership') setView(id);
        else if (id === 'logout')  logoutSubmit();
        else if (id === 'support') window.location.href = '/support';
        else if (id === 'notif')   window.location.href = '/notifications';
        else if (id === 'bank')    alert('환불 계좌 변경 — 가입 후 변경하려면 고객센터로 문의해주세요.');
        else alert(`${id} — 다음 단계에서 구현`);
      }}
    />
  );
}

Object.assign(window, { ProfileScreen, ProfileMain, ProfileFavorites, ProfileCoupons, ProfileMembership });
