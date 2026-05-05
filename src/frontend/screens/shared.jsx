import React from "react";

// shared.jsx — 공유 컴포넌트 모음
// 모바일 박스(상단 상태바, 하단 탭바, 스크롤 본문),
// 아이콘, 배지, 사진 placeholder, 한국 데이터.

// ───────────────────────────── Status bar ─────────────────────────────
function StatusBar({ time = "오후 6:42", dark = false }) {
  const stroke = dark ? "#fff" : "#1d2330";
  return (
    <div className={"status-bar" + (dark ? " dark" : "")}>
      <div>{time}</div>
      <div className="status-icons">
        {/* signal */}
        <svg width="17" height="11" viewBox="0 0 17 11" fill="none">
          <rect x="0"  y="7" width="3" height="4" rx="0.7" fill={stroke}/>
          <rect x="4.5" y="5" width="3" height="6" rx="0.7" fill={stroke}/>
          <rect x="9"  y="3" width="3" height="8" rx="0.7" fill={stroke}/>
          <rect x="13.5" y="0" width="3" height="11" rx="0.7" fill={stroke}/>
        </svg>
        {/* wifi */}
        <svg width="15" height="11" viewBox="0 0 15 11" fill="none">
          <path d="M7.5 9.5a1.2 1.2 0 100-2.4 1.2 1.2 0 000 2.4z" fill={stroke}/>
          <path d="M3 5.5c1.2-1.2 2.8-1.9 4.5-1.9s3.3.7 4.5 1.9" stroke={stroke} strokeWidth="1.4" strokeLinecap="round" fill="none"/>
          <path d="M.7 3.1C2.5 1.3 4.9.3 7.5.3S12.5 1.3 14.3 3.1" stroke={stroke} strokeWidth="1.4" strokeLinecap="round" fill="none"/>
        </svg>
        {/* battery */}
        <svg width="26" height="12" viewBox="0 0 26 12" fill="none">
          <rect x="0.5" y="0.5" width="22" height="11" rx="3" stroke={stroke} strokeOpacity="0.35" fill="none"/>
          <rect x="2"   y="2"   width="19" height="8" rx="1.6" fill={stroke}/>
          <rect x="23.2" y="4" width="1.6" height="4" rx="0.6" fill={stroke} fillOpacity="0.35"/>
        </svg>
      </div>
    </div>
  );
}

// ───────────────────────────── Icons ─────────────────────────────
const I = {
  search:   (s=20,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke={c} strokeWidth="1.8"/><path d="M20 20l-3.5-3.5" stroke={c} strokeWidth="1.8" strokeLinecap="round"/></svg>,
  pin:      (s=18,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M12 22s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z" stroke={c} strokeWidth="1.8" strokeLinejoin="round"/><circle cx="12" cy="10" r="2.5" stroke={c} strokeWidth="1.8"/></svg>,
  bell:     (s=22,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M6 17h12l-1.5-2v-4a4.5 4.5 0 00-9 0v4L6 17zM10.5 20a1.5 1.5 0 003 0" stroke={c} strokeWidth="1.8" strokeLinejoin="round" strokeLinecap="round"/></svg>,
  back:     (s=24,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M15 5l-7 7 7 7" stroke={c} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/></svg>,
  close:    (s=20,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke={c} strokeWidth="2" strokeLinecap="round"/></svg>,
  heart:    (s=22,c="currentColor",fill="none") => <svg width={s} height={s} viewBox="0 0 24 24" fill={fill}><path d="M12 20s-7-4.3-7-10.2C5 6.6 7.5 5 9.7 5c1.4 0 2.7.7 3.3 2 .6-1.3 1.9-2 3.3-2 2.2 0 4.7 1.6 4.7 4.8C21 15.7 12 20 12 20z" stroke={c} strokeWidth="1.8" strokeLinejoin="round"/></svg>,
  share:    (s=22,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M12 4v12M12 4l-4 4M12 4l4 4M5 13v5a2 2 0 002 2h10a2 2 0 002-2v-5" stroke={c} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"/></svg>,
  filter:   (s=18,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M4 6h16M7 12h10M10 18h4" stroke={c} strokeWidth="2" strokeLinecap="round"/></svg>,
  list:     (s=18,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h16" stroke={c} strokeWidth="2" strokeLinecap="round"/></svg>,
  map:      (s=18,c="currentColor",fill="none") => <svg width={s} height={s} viewBox="0 0 24 24" fill={fill==='solid'?c:"none"}><path d="M9 4l-5 2v14l5-2 6 2 5-2V4l-5 2-6-2zM9 4v14M15 6v14" stroke={c} strokeWidth="1.8" strokeLinejoin="round"/></svg>,
  chevR:    (s=18,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke={c} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/></svg>,
  chevL:    (s=18,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke={c} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/></svg>,
  chevDown: (s=16,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke={c} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/></svg>,
  star:     (s=14,c="#FFB020")     => <svg width={s} height={s} viewBox="0 0 24 24" fill={c}><path d="M12 2.5l3 6.4 7 .9-5.1 4.7 1.3 6.9L12 18l-6.2 3.4 1.3-6.9L2 9.8l7-.9 3-6.4z"/></svg>,
  clock:    (s=16,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke={c} strokeWidth="1.8"/><path d="M12 7v5l3 2" stroke={c} strokeWidth="1.8" strokeLinecap="round"/></svg>,
  bolt:     (s=14,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill={c}><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"/></svg>,
  check:    (s=18,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4.5 4.5L19 7" stroke={c} strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"/></svg>,
  copy:     (s=16,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><rect x="8" y="8" width="12" height="12" rx="2" stroke={c} strokeWidth="1.8"/><path d="M16 8V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h2" stroke={c} strokeWidth="1.8"/></svg>,
  home:     (s=22,c="currentColor",fill="none") => <svg width={s} height={s} viewBox="0 0 24 24" fill={fill==='solid'?c:"none"}><path d="M4 11l8-7 8 7v9a1 1 0 01-1 1h-5v-6h-4v6H5a1 1 0 01-1-1v-9z" stroke={c} strokeWidth="1.8" strokeLinejoin="round"/></svg>,
  ticket:   (s=22,c="currentColor",fill="none") => <svg width={s} height={s} viewBox="0 0 24 24" fill={fill==='solid'?c:"none"}><path d="M3 8a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2a2 2 0 100-4V8z" stroke={c} strokeWidth="1.8" strokeLinejoin="round"/></svg>,
  user:     (s=22,c="currentColor",fill="none") => <svg width={s} height={s} viewBox="0 0 24 24" fill={fill==='solid'?c:"none"}><circle cx="12" cy="8" r="4" stroke={c} strokeWidth="1.8"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8" stroke={c} strokeWidth="1.8" strokeLinecap="round"/></svg>,
  shuttle:  (s=22,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><circle cx="7" cy="17" r="3" stroke={c} strokeWidth="1.8"/><path d="M9.5 14.5L18 6M14 4l4 4M18 4l2 2" stroke={c} strokeWidth="1.8" strokeLinecap="round"/></svg>,
  walk:     (s=14,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><circle cx="13" cy="4.5" r="1.8" fill={c}/><path d="M9 22l2-7-3-3 1-4 4 1 3 3M14 22l-1-5" stroke={c} strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round"/></svg>,
  fire:     (s=12,c="currentColor") => <svg width={s} height={s} viewBox="0 0 24 24" fill={c}><path d="M12 2s4 4 4 8a4 4 0 11-8 0c0-1 .3-2 .8-2.6C8 9 7 11 7 13a5 5 0 0010 0c0-5-5-11-5-11z"/></svg>,
  fireOutline: (s=22,c="currentColor",fill="none") => <svg width={s} height={s} viewBox="0 0 24 24" fill={fill==='solid'?c:"none"}><path d="M12 3.5s3.5 3 3.5 7a3.5 3.5 0 11-7 0c0-.9.3-1.7.7-2.3-.8 1-1.7 2.6-1.7 4.3a4.5 4.5 0 009 0c0-4.5-4.5-9-4.5-9z" stroke={c} strokeWidth="1.6" strokeLinejoin="round"/></svg>,
  flash:    (s=22,c="currentColor",fill="none") => <svg width={s} height={s} viewBox="0 0 24 24" fill={fill==='solid'?c:"none"}><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke={c} strokeWidth="1.6" strokeLinejoin="round"/></svg>,
  dot:      (s=8,c="currentColor")  => <svg width={s} height={s} viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" fill={c}/></svg>,
  qr:       (s=160) => (
    <svg width={s} height={s} viewBox="0 0 160 160" fill="#0f1320">
      <rect x="0" y="0" width="160" height="160" fill="#fff"/>
      {/* Corner squares */}
      <rect x="10" y="10" width="40" height="40" fill="#0f1320"/>
      <rect x="18" y="18" width="24" height="24" fill="#fff"/>
      <rect x="24" y="24" width="12" height="12" fill="#0f1320"/>
      <rect x="110" y="10" width="40" height="40" fill="#0f1320"/>
      <rect x="118" y="18" width="24" height="24" fill="#fff"/>
      <rect x="124" y="24" width="12" height="12" fill="#0f1320"/>
      <rect x="10" y="110" width="40" height="40" fill="#0f1320"/>
      <rect x="18" y="118" width="24" height="24" fill="#fff"/>
      <rect x="24" y="124" width="12" height="12" fill="#0f1320"/>
      {/* Random-ish pattern */}
      {[
        [60,12],[68,12],[76,20],[84,12],[92,20],[100,12],
        [60,20],[76,28],[92,28],[100,28],
        [60,36],[68,28],[84,36],[92,36],[100,36],
        [12,60],[20,60],[36,60],[44,60],[60,60],[68,60],[76,52],[84,60],[100,60],[108,60],[124,60],[140,60],[148,60],
        [12,68],[28,68],[44,68],[60,68],[76,68],[92,68],[108,68],[124,68],[140,68],
        [12,76],[20,76],[28,76],[44,76],[60,76],[68,76],[84,76],[100,76],[108,76],[124,76],[132,76],[148,76],
        [12,84],[36,84],[52,84],[68,84],[84,84],[92,84],[108,84],[140,84],
        [20,92],[28,92],[36,92],[60,92],[76,92],[84,92],[100,92],[116,92],[124,92],[132,92],[148,92],
        [12,100],[44,100],[60,100],[76,100],[100,100],[108,100],[140,100],
        [60,108],[68,108],[84,108],[100,108],[116,108],[132,108],
        [60,116],[76,116],[92,116],[108,116],[124,116],[140,116],[148,116],
        [60,124],[68,124],[84,124],[100,124],[124,124],
        [60,132],[76,132],[92,132],[108,132],[116,132],[140,132],[148,132],
        [60,140],[68,140],[84,140],[100,140],[124,140],[140,140],
        [60,148],[76,148],[92,148],[108,148],[132,148],[148,148],
      ].map(([x,y],i)=>(<rect key={i} x={x} y={y} width="8" height="8" fill="#0f1320"/>))}
    </svg>
  ),
};

// ───────────────────────────── Bottom tab bar ─────────────────────────────
function TabBar({ active = "home", onNav }) {
  const items = [
    { id: "home",   label: "홈",       icon: I.home },
    { id: "search", label: "지도",     icon: I.map },
    { id: "ticket", label: "내 예약",  icon: I.ticket },
    { id: "user",   label: "마이",     icon: I.user },
  ];
  return (
    <nav className="tabbar">
      {items.map(it => {
        const isActive = it.id === active;
        const color = isActive ? "var(--brand-500)" : "var(--gray-500)";
        return (
          <button key={it.id} className={"tab" + (isActive ? " active" : "")} onClick={() => onNav && onNav(it.id)} type="button">
            {it.icon(22, color, isActive ? "solid" : "none")}
            <span>{it.label}</span>
          </button>
        );
      })}
    </nav>
  );
}

// ───────────────────────────── Mobile shell ─────────────────────────────
function Mobile({ statusDark = false, time = "오후 6:42", children, tab = "home", onNav, hideTab = false, scrollRef, fullBleed = false }) {
  return (
    <div className="mobile">
      <StatusBar dark={statusDark} time={time} />
      <div className="app-body" ref={scrollRef}>
        {children}
      </div>
      {!hideTab && <TabBar active={tab} onNav={onNav} />}
    </div>
  );
}

// ───────────────────────────── Photo (Unsplash) ─────────────────────────────
// Use Unsplash image URLs; fall back to gradient on error.
function Photo({ src, alt = "", style, children, radius = 12, ratio }) {
  const [ok, setOk] = React.useState(true);
  const wrap = {
    position: "relative",
    overflow: "hidden",
    borderRadius: radius,
    background: "linear-gradient(135deg,#1335a8 0%,#1e50ff 60%,#5c80ff 100%)",
    ...(ratio ? { aspectRatio: ratio } : {}),
    ...style,
  };
  return (
    <div style={wrap}>
      {ok && (
        <img
          src={src}
          alt={alt}
          onError={() => setOk(false)}
          style={{ width: "100%", height: "100%", objectFit: "cover", display: "block" }}
          loading="lazy"
        />
      )}
      {children}
    </div>
  );
}

// ───────────────────────────── Korean fixture data ─────────────────────────────
// Real-feeling venue data. URLs are Unsplash photos w/ size params for thumbnails.
const VENUES = [
  {
    id: "v1",
    name: "강남 스파이크 배드민턴",
    area: "서울 강남구 역삼동",
    price: 35000,
    discount: 30, // hot deal %
    distanceKm: 0.7,
    walkMin: 9,
    rating: 4.8,
    reviews: 312,
    courts: 4,
    tags: ["주차가능", "샤워실", "라켓대여"],
    img: "https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&q=70",
    nextSlot: "오늘 19:00",
    hot: true,
  },
  {
    id: "v2",
    name: "역삼 BNK 체육관",
    area: "서울 강남구 역삼동",
    price: 28000,
    distanceKm: 1.2,
    walkMin: 14,
    rating: 4.6,
    reviews: 188,
    courts: 6,
    tags: ["주차가능", "에어컨"],
    img: "https://images.unsplash.com/photo-1599391398131-cd12dfc6c24e?w=600&q=70",
    nextSlot: "오늘 20:00",
  },
  {
    id: "v3",
    name: "선릉 셔틀콕 클럽",
    area: "서울 강남구 대치동",
    price: 32000,
    distanceKm: 1.8,
    walkMin: 22,
    rating: 4.9,
    reviews: 421,
    courts: 3,
    tags: ["프로 코트", "샤워실", "락커"],
    img: "https://images.unsplash.com/photo-1627246031882-bd60ee2eed7c?w=600&q=70",
    nextSlot: "오늘 21:00",
  },
  {
    id: "v4",
    name: "삼성 프라임 배드민턴",
    area: "서울 강남구 삼성동",
    price: 40000,
    distanceKm: 2.4,
    walkMin: 30,
    rating: 4.7,
    reviews: 256,
    courts: 5,
    tags: ["주차가능", "샤워실", "라켓대여", "셔틀콕무료"],
    img: "https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=600&q=70",
    nextSlot: "오늘 22:00",
  },
  {
    id: "v5",
    name: "양재 그린코트",
    area: "서울 서초구 양재동",
    price: 25000,
    discount: 20,
    distanceKm: 3.1,
    walkMin: 38,
    rating: 4.5,
    reviews: 142,
    courts: 2,
    tags: ["주차가능"],
    img: "https://images.unsplash.com/photo-1613918431703-aa50889e3be0?w=600&q=70",
    nextSlot: "오늘 19:30",
    hot: true,
  },
  {
    id: "v6",
    name: "논현 SKY 체육관",
    area: "서울 강남구 논현동",
    price: 30000,
    distanceKm: 1.5,
    walkMin: 18,
    rating: 4.4,
    reviews: 98,
    courts: 4,
    tags: ["에어컨", "샤워실"],
    img: "https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&q=70",
    nextSlot: "오늘 20:30",
  },
];

const won = (n) => n.toLocaleString("ko-KR") + "원";

// ───────────────────────────── Export ─────────────────────────────
Object.assign(window, {
  StatusBar, TabBar, Mobile, Photo, I, VENUES, won,
});
