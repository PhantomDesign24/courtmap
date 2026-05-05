-- ============================================================================
-- 코트맵 (CourtMap) — DB 스키마 (MVP v1)
-- 프론트(zip)에서 추출한 데이터 모델 + 기획서(기획.md) 기반.
--
-- 엔진: InnoDB / utf8mb4
-- 시간대: 모든 DATETIME 은 KST 기준으로 저장 (앱 레벨에서 통일)
--
-- 컨벤션:
--   - PK: BIGINT UNSIGNED AUTO_INCREMENT
--   - FK: ON DELETE 는 의미에 따라 CASCADE / RESTRICT / SET NULL 선택
--   - 비즈니스 식별자(예약번호 등)는 별도 컬럼 + UNIQUE
--   - 금액: 정수 원화 (소수점 없음)
--   - is_*, *_at 타입 일관성 유지
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. 사용자 & 인증
-- ============================================================================

CREATE TABLE users (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email           VARCHAR(255)    NOT NULL,
  phone           VARCHAR(20)     NOT NULL,                 -- 010-XXXX-XXXX
  name            VARCHAR(50)     NOT NULL,                 -- 본명
  password_hash   VARCHAR(255)    DEFAULT NULL,             -- 소셜 단독 로그인이면 NULL
  role            ENUM('user','operator','admin') NOT NULL DEFAULT 'user',

  -- 소셜 로그인
  kakao_id        VARCHAR(64)     DEFAULT NULL,
  naver_id        VARCHAR(64)     DEFAULT NULL,

  -- 입금자명 (가족 명의 계좌로 입금하는 경우 본명과 다를 수 있음)
  depositor_name  VARCHAR(50)     DEFAULT NULL,

  -- 환불받을 본인 계좌 (회원가입 시 필수 입력)
  refund_bank_name    VARCHAR(40) NOT NULL,
  refund_bank_account VARCHAR(40) NOT NULL,
  refund_bank_holder  VARCHAR(60) NOT NULL,

  -- 신뢰점수 (노쇼 차감, 기본 100)
  trust_score     SMALLINT        NOT NULL DEFAULT 100,
  -- 점수 하한 시 예약 제한 해제 시점
  restricted_until DATETIME       DEFAULT NULL,

  status          ENUM('active','suspended','withdrawn') NOT NULL DEFAULT 'active',
  last_login_at   DATETIME        DEFAULT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uk_users_email (email),
  UNIQUE KEY uk_users_phone (phone),
  UNIQUE KEY uk_users_kakao (kakao_id),
  UNIQUE KEY uk_users_naver (naver_id),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE user_sessions (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  session_token   VARCHAR(128)    NOT NULL,
  user_agent      VARCHAR(255)    DEFAULT NULL,
  ip_addr         VARCHAR(45)     DEFAULT NULL,
  expires_at      DATETIME        NOT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uk_session_token (session_token),
  KEY idx_session_user (user_id),
  CONSTRAINT fk_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- API 토큰 (S5 외부시스템 연동용. 운영자별로 발급)
CREATE TABLE api_tokens (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  token           VARCHAR(128)    NOT NULL,
  name            VARCHAR(80)     NOT NULL,                 -- 사용자 식별용 라벨
  scopes          VARCHAR(255)    NOT NULL DEFAULT 'reservations:read',
  last_used_at    DATETIME        DEFAULT NULL,
  expires_at      DATETIME        DEFAULT NULL,
  status          ENUM('active','revoked') NOT NULL DEFAULT 'active',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uk_api_token (token),
  KEY idx_api_venue (venue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 2. 구장(Venue) & 코트
-- ============================================================================

CREATE TABLE venues (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id        BIGINT UNSIGNED NOT NULL,                 -- users.id (role='operator')
  name            VARCHAR(120)    NOT NULL,                 -- "강남 스파이크 배드민턴"
  area            VARCHAR(100)    NOT NULL,                 -- "서울 강남구 역삼동" (검색용)
  address         VARCHAR(255)    NOT NULL,                 -- 상세주소
  address_detail  VARCHAR(120)    DEFAULT NULL,             -- "B2층" 등
  lat             DECIMAL(10,7)   NOT NULL,                 -- 지도/거리계산
  lng             DECIMAL(10,7)   NOT NULL,
  phone           VARCHAR(20)     NOT NULL,                 -- 입장안내용
  description     TEXT,

  -- 가격 (운영자 기본값. 코트별 오버라이드는 venue_courts.price_override)
  price_per_hour  INT UNSIGNED    NOT NULL,                 -- 단위: 원

  -- 무통장입금 계좌 (구장별)
  bank_name       VARCHAR(40)     NOT NULL,                 -- "신한은행"
  bank_account    VARCHAR(40)     NOT NULL,                 -- "110-432-589021"
  bank_holder     VARCHAR(60)     NOT NULL,                 -- "(주)코트맵"
  deposit_due_hours TINYINT UNSIGNED NOT NULL DEFAULT 24,   -- 입금 기한(시간). 0 = 23:59 당일

  -- 환불 정책 (% 단위. 운영자별 오버라이드. 미설정시 시스템 기본 100/50/0)
  refund_24h_pct  TINYINT UNSIGNED NOT NULL DEFAULT 100,
  refund_1h_pct   TINYINT UNSIGNED NOT NULL DEFAULT 50,
  refund_lt1h_pct TINYINT UNSIGNED NOT NULL DEFAULT 0,

  -- 집계 (denormalized — 리뷰 v2이지만 컬럼만 미리)
  rating_avg      DECIMAL(2,1)    NOT NULL DEFAULT 0.0,
  review_count    INT UNSIGNED    NOT NULL DEFAULT 0,

  status          ENUM('pending','active','suspended','closed') NOT NULL DEFAULT 'pending',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_venues_owner (owner_id),
  KEY idx_venues_status (status),
  KEY idx_venues_area (area),
  -- 지도 영역 검색용 (BBox 쿼리 위해)
  KEY idx_venues_geo (lat, lng),
  CONSTRAINT fk_venues_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE venue_photos (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  url             VARCHAR(500)    NOT NULL,
  sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_main         TINYINT(1)      NOT NULL DEFAULT 0,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_photo_venue (venue_id, sort_order),
  CONSTRAINT fk_photo_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 시설/태그 마스터 (주차가능, 샤워실, 라켓대여, 에어컨, 락커, 정수기 등)
CREATE TABLE facility_tags (
  id              SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code            VARCHAR(40)     NOT NULL,                 -- "parking", "shower"
  name            VARCHAR(40)     NOT NULL,                 -- "주차가능"
  icon            VARCHAR(40)     DEFAULT NULL,
  sort_order      SMALLINT        NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  UNIQUE KEY uk_tag_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE venue_facility_tags (
  venue_id        BIGINT UNSIGNED NOT NULL,
  tag_id          SMALLINT UNSIGNED NOT NULL,

  PRIMARY KEY (venue_id, tag_id),
  CONSTRAINT fk_vft_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
  CONSTRAINT fk_vft_tag   FOREIGN KEY (tag_id)   REFERENCES facility_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE courts (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(20)     NOT NULL,                 -- "A코트" / "1번 코트"
  sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  price_override  INT UNSIGNED    DEFAULT NULL,             -- 코트별 가격 다를 시 (NULL = venues.price_per_hour)
  status          ENUM('active','maintenance','closed') NOT NULL DEFAULT 'active',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_court_venue (venue_id, sort_order),
  CONSTRAINT fk_court_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 3. 운영시간 & 슬롯 규칙 & 공휴일
-- ============================================================================

-- 요일별 운영시간 (하루 단위. 다중 시간대 필요시 row 추가)
CREATE TABLE venue_hours (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  day_of_week     TINYINT UNSIGNED NOT NULL,                -- 0=일 ... 6=토
  open_time       TIME            NOT NULL,                 -- "10:00:00"
  close_time      TIME            NOT NULL,                 -- "24:00:00" → 23:59:59 또는 익일처리
  is_closed       TINYINT(1)      NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  KEY idx_hours_venue (venue_id, day_of_week),
  CONSTRAINT fk_hours_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 임시 휴무 (특정 날짜)
CREATE TABLE venue_closures (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  closed_date     DATE            NOT NULL,
  reason          VARCHAR(200)    DEFAULT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uk_closure (venue_id, closed_date),
  CONSTRAINT fk_closure_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 슬롯 단위 규칙 (S3) — 우선순위: specific_date > holiday > day_of_week > default
CREATE TABLE slot_rules (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  rule_type       ENUM('default','dow','holiday','specific_date') NOT NULL,
  day_of_week     TINYINT UNSIGNED DEFAULT NULL,            -- rule_type='dow'
  specific_date   DATE             DEFAULT NULL,            -- rule_type='specific_date'
  slot_unit_hours TINYINT UNSIGNED NOT NULL,                -- 1, 2, 3
  note            VARCHAR(120)    DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_rule_venue_type (venue_id, rule_type),
  KEY idx_rule_date (specific_date),
  CONSTRAINT fk_rule_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 한국 공휴일 마스터 (외부 API 자동 동기화)
CREATE TABLE holidays (
  holiday_date    DATE            NOT NULL,
  name            VARCHAR(40)     NOT NULL,
  is_substitute   TINYINT(1)      NOT NULL DEFAULT 0,       -- 대체공휴일 여부

  PRIMARY KEY (holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 4. 예약
-- ============================================================================

CREATE TABLE recurring_groups (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  venue_id        BIGINT UNSIGNED NOT NULL,
  court_id        BIGINT UNSIGNED NOT NULL,
  day_of_week     TINYINT UNSIGNED NOT NULL,
  start_hour      TINYINT UNSIGNED NOT NULL,                -- 0~23
  duration_hours  TINYINT UNSIGNED NOT NULL,
  start_date      DATE            NOT NULL,
  end_date        DATE            NOT NULL,                 -- start_date + (week_count-1)*7
  week_count      SMALLINT UNSIGNED NOT NULL DEFAULT 4,
  status          ENUM('active','canceled','completed') NOT NULL DEFAULT 'active',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_recur_user (user_id),
  KEY idx_recur_venue (venue_id),
  CONSTRAINT fk_recur_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_recur_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
  CONSTRAINT fk_recur_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE reservations (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code            VARCHAR(20)     NOT NULL,                 -- "CMAP-2026-5J7K2"

  user_id         BIGINT UNSIGNED NOT NULL,
  venue_id        BIGINT UNSIGNED NOT NULL,
  court_id        BIGINT UNSIGNED NOT NULL,

  reservation_date DATE           NOT NULL,
  start_hour      TINYINT UNSIGNED NOT NULL,                -- 0~23 (시작 시각)
  duration_hours  TINYINT UNSIGNED NOT NULL,                -- 1, 2, 3

  -- 가격 스냅샷 (예약 시점 기준 동결)
  base_price      INT UNSIGNED    NOT NULL,                 -- 코트 사용료 (할인 반영 후)
  base_price_original INT UNSIGNED NOT NULL,                -- 할인 적용 전 원가 (정산용)
  discount_pct    TINYINT UNSIGNED NOT NULL DEFAULT 0,      -- 다이나믹 프라이싱 %
  extras_price    INT UNSIGNED    NOT NULL DEFAULT 0,       -- 장비 대여 합계
  coupon_discount INT UNSIGNED    NOT NULL DEFAULT 0,
  total_price     INT UNSIGNED    NOT NULL,                 -- 최종 결제(=입금)금액

  -- 무통장입금
  depositor_name  VARCHAR(50)     NOT NULL,                 -- 입금자명 매칭용
  deposit_due_at  DATETIME        NOT NULL,                 -- 입금기한
  paid_at         DATETIME        DEFAULT NULL,             -- 운영자가 확정한 시점

  -- 상태
  status          ENUM('pending','confirmed','done','noshow','canceled','expired') NOT NULL DEFAULT 'pending',
  -- pending  : 예약 신청, 입금 대기
  -- confirmed: 운영자가 입금 확인 → 확정
  -- done     : 이용 완료 (입장 체크 후 시간 경과)
  -- noshow   : 미입장
  -- canceled : 사용자/운영자 취소
  -- expired  : 입금기한 초과 자동 취소

  -- 입장 체크
  entered_at      DATETIME        DEFAULT NULL,             -- 운영자가 입장 체크한 시점

  -- 취소/환불
  canceled_at     DATETIME        DEFAULT NULL,
  cancel_reason   VARCHAR(200)    DEFAULT NULL,
  canceled_by     ENUM('user','operator','system') DEFAULT NULL,
  refund_amount   INT UNSIGNED    NOT NULL DEFAULT 0,

  -- 정기예약 묶음
  recurring_group_id BIGINT UNSIGNED DEFAULT NULL,

  -- 다이나믹 프라이싱 매칭
  dynamic_pricing_id BIGINT UNSIGNED DEFAULT NULL,

  -- 사용 쿠폰
  used_coupon_id  BIGINT UNSIGNED DEFAULT NULL,

  -- 멤버십 차감 (멤버십 시간으로 결제 시 total_price=0)
  used_membership_id BIGINT UNSIGNED DEFAULT NULL,

  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uk_reservation_code (code),

  -- 동시 예약 방지 핵심 인덱스: 같은 코트 같은 날짜 같은 시작시각 중복 방지는
  -- 애플리케이션 트랜잭션 + 이 인덱스로 처리. (status 가 active 일 때만 의미있음)
  KEY idx_court_slot (court_id, reservation_date, start_hour),
  KEY idx_venue_date (venue_id, reservation_date),
  KEY idx_user (user_id, status, reservation_date),
  KEY idx_status_due (status, deposit_due_at),
  KEY idx_recurring (recurring_group_id),

  CONSTRAINT fk_res_user   FOREIGN KEY (user_id)  REFERENCES users(id)    ON DELETE RESTRICT,
  CONSTRAINT fk_res_venue  FOREIGN KEY (venue_id) REFERENCES venues(id)   ON DELETE RESTRICT,
  CONSTRAINT fk_res_court  FOREIGN KEY (court_id) REFERENCES courts(id)   ON DELETE RESTRICT,
  CONSTRAINT fk_res_recur  FOREIGN KEY (recurring_group_id) REFERENCES recurring_groups(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 5. 다이나믹 프라이싱 (S4)
-- ============================================================================

CREATE TABLE dynamic_pricing (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  court_id        BIGINT UNSIGNED DEFAULT NULL,             -- NULL = 구장 전체
  target_date     DATE            NOT NULL,
  target_start_hour TINYINT UNSIGNED NOT NULL,
  target_end_hour TINYINT UNSIGNED NOT NULL,
  discount_pct    TINYINT UNSIGNED NOT NULL,                -- 1~99
  status          ENUM('active','expired','canceled') NOT NULL DEFAULT 'active',
  created_by      BIGINT UNSIGNED NOT NULL,                 -- operator
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at      DATETIME        DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_dp_lookup (venue_id, target_date, status),
  KEY idx_dp_court  (court_id, target_date),
  CONSTRAINT fk_dp_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
  CONSTRAINT fk_dp_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 다이나믹 프라이싱 자동 룰 — 운영자가 룰만 켜두면 조건 맞으면 시스템이 발행
CREATE TABLE auto_pricing_rules (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(80)     NOT NULL,                 -- "평일 임박 자동할인"
  -- 트리거: 시작 N시간 전까지 빈 코트면 발동
  trigger_hours_before TINYINT UNSIGNED NOT NULL,           -- 1~6 권장
  discount_pct    TINYINT UNSIGNED NOT NULL,
  -- 적용 요일 마스크 (비트필드: 일=1, 월=2, ..., 토=64. 평일=62, 주말=65)
  dow_mask        TINYINT UNSIGNED NOT NULL DEFAULT 127,    -- 127 = 모든 요일
  -- 적용 시간대
  apply_from_hour TINYINT UNSIGNED NOT NULL DEFAULT 0,
  apply_to_hour   TINYINT UNSIGNED NOT NULL DEFAULT 24,
  status          ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_apr_venue (venue_id, status),
  CONSTRAINT fk_apr_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 6. 쿠폰 & 멤버십
-- ============================================================================

CREATE TABLE coupons (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code            VARCHAR(40)     DEFAULT NULL,             -- 사용자 입력형이면 사용
  name            VARCHAR(80)     NOT NULL,
  venue_id        BIGINT UNSIGNED DEFAULT NULL,             -- NULL = 전체 사용 가능
  issued_by       BIGINT UNSIGNED DEFAULT NULL,             -- operator/admin user_id
  discount_type   ENUM('fixed','percent') NOT NULL,
  discount_value  INT UNSIGNED    NOT NULL,                 -- 원 또는 %
  min_amount      INT UNSIGNED    NOT NULL DEFAULT 0,       -- 최소 사용 금액
  max_discount    INT UNSIGNED    DEFAULT NULL,             -- percent 일 때 상한
  valid_from      DATETIME        DEFAULT NULL,
  valid_until     DATETIME        DEFAULT NULL,
  total_quota     INT UNSIGNED    DEFAULT NULL,             -- NULL = 무제한
  issued_count    INT UNSIGNED    NOT NULL DEFAULT 0,
  status          ENUM('active','suspended','expired') NOT NULL DEFAULT 'active',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uk_coupon_code (code),
  KEY idx_coupon_venue (venue_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE user_coupons (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  coupon_id       BIGINT UNSIGNED NOT NULL,
  issued_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at         DATETIME        DEFAULT NULL,
  used_in_reservation_id BIGINT UNSIGNED DEFAULT NULL,
  expires_at      DATETIME        DEFAULT NULL,             -- coupon.valid_until 스냅샷 가능

  PRIMARY KEY (id),
  KEY idx_uc_user (user_id, used_at),
  CONSTRAINT fk_uc_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  CONSTRAINT fk_uc_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE memberships (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(80)     NOT NULL,                 -- "월 8시간권"
  description     TEXT,
  price           INT UNSIGNED    NOT NULL,
  hours_total     SMALLINT UNSIGNED NOT NULL,               -- 월 N시간
  valid_months    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_membership_venue (venue_id, status),
  CONSTRAINT fk_membership_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE user_memberships (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  membership_id   BIGINT UNSIGNED NOT NULL,
  started_at      DATETIME        NOT NULL,
  expires_at      DATETIME        NOT NULL,
  hours_remaining SMALLINT UNSIGNED NOT NULL,
  status          ENUM('active','expired','canceled') NOT NULL DEFAULT 'active',
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_um_user (user_id, status),
  CONSTRAINT fk_um_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_um_membership FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 7. 장비 대여
-- ============================================================================

CREATE TABLE equipment_options (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  type            ENUM('racket','shuttle','other') NOT NULL,
  name            VARCHAR(60)     NOT NULL,                 -- "라켓 1자루 (YONEX)"
  description     VARCHAR(120)    DEFAULT NULL,             -- "12개입"
  price           INT UNSIGNED    NOT NULL,
  status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
  sort_order      SMALLINT        NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  KEY idx_eq_venue (venue_id, status),
  CONSTRAINT fk_eq_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE reservation_equipment (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reservation_id  BIGINT UNSIGNED NOT NULL,
  equipment_id    BIGINT UNSIGNED NOT NULL,
  quantity        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  price_at_time   INT UNSIGNED    NOT NULL,                 -- 스냅샷

  PRIMARY KEY (id),
  KEY idx_re_res (reservation_id),
  CONSTRAINT fk_re_res FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
  CONSTRAINT fk_re_eq  FOREIGN KEY (equipment_id)   REFERENCES equipment_options(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 8. 알림 & 즐겨찾기
-- ============================================================================

CREATE TABLE notifications (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  type            ENUM('alert','confirm','remind','deal','review','system') NOT NULL,
  title           VARCHAR(150)    NOT NULL,
  body            VARCHAR(500)    DEFAULT NULL,
  link_url        VARCHAR(255)    DEFAULT NULL,             -- 인앱 라우트 또는 URL
  related_type    VARCHAR(40)     DEFAULT NULL,             -- 'venue', 'reservation' 등
  related_id      BIGINT UNSIGNED DEFAULT NULL,
  is_read         TINYINT(1)      NOT NULL DEFAULT 0,
  read_at         DATETIME        DEFAULT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_notif_user (user_id, is_read, created_at),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 단골 구장 (즐겨찾기 + 빈자리 알림 ON/OFF)
CREATE TABLE favorites (
  user_id         BIGINT UNSIGNED NOT NULL,
  venue_id        BIGINT UNSIGNED NOT NULL,
  notify_open_slot TINYINT(1)     NOT NULL DEFAULT 1,       -- 빈자리 알림 수신
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id, venue_id),
  KEY idx_fav_venue (venue_id),
  CONSTRAINT fk_fav_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_fav_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 9. 강사 & 레슨 (페르소나 3)
-- ============================================================================

CREATE TABLE coaches (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(50)     NOT NULL,
  career          VARCHAR(255)    DEFAULT NULL,             -- "前 국가대표 상비군 · 12년"
  bio             TEXT,
  price_per_lesson INT UNSIGNED   NOT NULL,
  duration_min    SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  img_url         VARCHAR(500)    DEFAULT NULL,
  status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
  sort_order      SMALLINT        NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  KEY idx_coach_venue (venue_id, status),
  CONSTRAINT fk_coach_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE lesson_reservations (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code            VARCHAR(20)     NOT NULL,
  user_id         BIGINT UNSIGNED NOT NULL,
  coach_id        BIGINT UNSIGNED NOT NULL,
  venue_id        BIGINT UNSIGNED NOT NULL,
  lesson_date     DATE            NOT NULL,
  start_hour      TINYINT UNSIGNED NOT NULL,
  duration_min    SMALLINT UNSIGNED NOT NULL,
  price           INT UNSIGNED    NOT NULL,
  status          ENUM('pending','confirmed','done','noshow','canceled','expired') NOT NULL DEFAULT 'pending',
  paid_at         DATETIME        DEFAULT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uk_lesson_code (code),
  KEY idx_lesson_coach (coach_id, lesson_date),
  KEY idx_lesson_user (user_id),
  CONSTRAINT fk_lr_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE RESTRICT,
  CONSTRAINT fk_lr_coach FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE RESTRICT,
  CONSTRAINT fk_lr_venue FOREIGN KEY (venue_id) REFERENCES venues(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 10. 노쇼 로그 / 신뢰점수 정책
-- ============================================================================
-- 신뢰점수 정책 (애플리케이션 레벨에서 적용. 추후 settings 테이블로 이관 가능)
--   기본:      100
--   자동 노쇼:  -15  (예약 시작 후 10분 입장 체크 안 되면)
--   수동 노쇼:  -15  (운영자 신고 → 관리자 승인 후 확정)
--   정상 완료:  +1   (월 최대 +5, 빠른 회복 방지)
--   임계치:
--     80~100  : 제한 없음
--     60~ 79  : 다음 예약 시 입금기한 12시간으로 단축
--     40~ 59  : 7일 신규 예약 제한
--      0~ 39  : 30일 신규 예약 제한
--   restricted_until 컬럼으로 해제 시점 관리.

CREATE TABLE noshow_logs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reservation_id  BIGINT UNSIGNED NOT NULL,
  user_id         BIGINT UNSIGNED NOT NULL,
  venue_id        BIGINT UNSIGNED NOT NULL,
  detected_by     ENUM('auto','operator') NOT NULL,
  reported_by     BIGINT UNSIGNED DEFAULT NULL,             -- operator user_id
  score_delta     SMALLINT        NOT NULL,                 -- 음수
  note            VARCHAR(255)    DEFAULT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_noshow_user (user_id),
  KEY idx_noshow_res  (reservation_id),
  CONSTRAINT fk_ns_res   FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
  CONSTRAINT fk_ns_user  FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
  CONSTRAINT fk_ns_venue FOREIGN KEY (venue_id)       REFERENCES venues(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 11. 외부 시스템 연동 (S5)
-- ============================================================================

CREATE TABLE webhooks (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        BIGINT UNSIGNED NOT NULL,
  event_type      VARCHAR(60)     NOT NULL,                 -- "reservation.confirmed" 등
  url             VARCHAR(500)    NOT NULL,
  secret          VARCHAR(120)    NOT NULL,                 -- HMAC signing key
  status          ENUM('active','paused','failed') NOT NULL DEFAULT 'active',
  failure_count   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  last_success_at DATETIME        DEFAULT NULL,
  last_failure_at DATETIME        DEFAULT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_webhook_venue (venue_id, status),
  CONSTRAINT fk_webhook_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 12. 검색 보조 (선택)
-- ============================================================================

-- 최근/인기 검색어 (extras.jsx 참고)
CREATE TABLE search_logs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED DEFAULT NULL,             -- 로그인 안 한 경우 NULL
  query           VARCHAR(120)    NOT NULL,
  result_count    INT UNSIGNED    NOT NULL DEFAULT 0,
  searched_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_search_user (user_id, searched_at),
  KEY idx_search_query (query, searched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 시드 데이터: facility_tags
-- ============================================================================
INSERT INTO facility_tags (code, name, sort_order) VALUES
  ('parking',         '주차가능',     1),
  ('shower',          '샤워실',       2),
  ('racket_rental',   '라켓대여',     3),
  ('aircon',          '에어컨',       4),
  ('locker',          '락커',         5),
  ('pro_court',       '프로 코트',    6),
  ('free_shuttle',    '셔틀콕무료',   7),
  ('water',           '정수기',       8);
