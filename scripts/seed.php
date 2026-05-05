<?php
declare(strict_types=1);

// 시드 데이터: 운영자 1명 + 구장 6곳 + 코트 4면씩 + 운영시간 + 시설태그 + 기본 슬롯규칙
// 실행: php scripts/seed.php  (idempotent — email 중복이면 skip)

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Db;

function out(string $s): void { echo $s . PHP_EOL; }

// ─── 1. 운영자 ─────────────────────────────────────────────
$opEmail = 'operator@bad.mvc.kr';
$op = Db::fetch('SELECT id FROM users WHERE email = ?', [$opEmail]);
if ($op) {
    $opId = (int) $op['id'];
    out("operator already exists: id=$opId");
} else {
    $opId = Db::insert('users', [
        'email'               => $opEmail,
        'phone'               => '010-1111-2222',
        'name'                => '강남 운영자',
        'password_hash'       => password_hash('operator1234', PASSWORD_BCRYPT),
        'role'                => 'operator',
        'depositor_name'      => '강남 운영자',
        'refund_bank_name'    => '신한은행',
        'refund_bank_account' => '110-000-000001',
        'refund_bank_holder'  => '강남 운영자',
    ]);
    out("operator created: id=$opId");
}

// ─── 2. 구장 6곳 (shared.jsx VENUES 기반) ─────────────────
$venues = [
    ['v1', '강남 스파이크 배드민턴', '서울 강남구 역삼동', '서울 강남구 테헤란로 123', '37.5005', '127.0364', 35000, ['parking','shower','racket_rental'], 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=1200&q=70'],
    ['v2', '역삼 BNK 체육관',         '서울 강남구 역삼동', '서울 강남구 테헤란로 220', '37.5008', '127.0420', 28000, ['parking','aircon'],                  'https://images.unsplash.com/photo-1599391398131-cd12dfc6c24e?w=1200&q=70'],
    ['v3', '선릉 셔틀콕 클럽',        '서울 강남구 대치동', '서울 강남구 선릉로 145',   '37.5040', '127.0490', 32000, ['pro_court','shower','locker'],         'https://images.unsplash.com/photo-1627246031882-bd60ee2eed7c?w=1200&q=70'],
    ['v4', '삼성 프라임 배드민턴',    '서울 강남구 삼성동', '서울 강남구 영동대로 521', '37.5092', '127.0560', 40000, ['parking','shower','racket_rental','free_shuttle'], 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=1200&q=70'],
    ['v5', '양재 그린코트',           '서울 서초구 양재동', '서울 서초구 양재대로 200', '37.4847', '127.0335', 25000, ['parking'],                            'https://images.unsplash.com/photo-1613918431703-aa50889e3be0?w=1200&q=70'],
    ['v6', '논현 SKY 체육관',         '서울 강남구 논현동', '서울 강남구 강남대로 480', '37.5111', '127.0218', 30000, ['aircon','shower'],                    'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=1200&q=70'],
];

$tagMap = [];
foreach (Db::fetchAll('SELECT id, code FROM facility_tags') as $t) {
    $tagMap[$t['code']] = (int) $t['id'];
}

foreach ($venues as $v) {
    [$slug, $name, $area, $address, $lat, $lng, $price, $tags, $img] = $v;

    $existing = Db::fetch('SELECT id FROM venues WHERE name = ?', [$name]);
    if ($existing) {
        out("venue exists: $name (id={$existing['id']}) — skip");
        continue;
    }

    $venueId = Db::insert('venues', [
        'owner_id'          => $opId,
        'name'              => $name,
        'area'              => $area,
        'address'           => $address,
        'lat'               => $lat,
        'lng'               => $lng,
        'phone'             => '02-555-3849',
        'description'       => $name . ' — 코트맵 등록 구장.',
        'price_per_hour'    => $price,
        'bank_name'         => '신한은행',
        'bank_account'      => '110-432-589021',
        'bank_holder'       => '강남 운영자',
        'deposit_due_hours' => 24,
        'refund_24h_pct'    => 100,
        'refund_1h_pct'     => 50,
        'refund_lt1h_pct'   => 0,
        'status'            => 'active',
    ]);

    // 메인 사진
    Db::insert('venue_photos', [
        'venue_id' => $venueId,
        'url'      => $img,
        'is_main'  => 1,
        'sort_order' => 0,
    ]);

    // 코트 4면
    foreach (['A','B','C','D'] as $i => $letter) {
        Db::insert('courts', [
            'venue_id'   => $venueId,
            'name'       => $letter . '코트',
            'sort_order' => $i,
        ]);
    }

    // 운영시간: 월~금 10~24, 주말 9~24
    for ($dow = 0; $dow <= 6; $dow++) {
        $weekend = ($dow === 0 || $dow === 6);
        Db::insert('venue_hours', [
            'venue_id'   => $venueId,
            'day_of_week'=> $dow,
            'open_time'  => $weekend ? '09:00:00' : '10:00:00',
            'close_time' => '23:59:59',
        ]);
    }

    // 시설 태그
    foreach ($tags as $code) {
        if (!isset($tagMap[$code])) continue;
        Db::query('INSERT IGNORE INTO venue_facility_tags (venue_id, tag_id) VALUES (?, ?)', [$venueId, $tagMap[$code]]);
    }

    // 기본 슬롯규칙 (1H)
    Db::insert('slot_rules', [
        'venue_id'        => $venueId,
        'rule_type'       => 'default',
        'slot_unit_hours' => 1,
    ]);
    // 공휴일은 2H
    Db::insert('slot_rules', [
        'venue_id'        => $venueId,
        'rule_type'       => 'holiday',
        'slot_unit_hours' => 2,
    ]);

    out("venue created: $name (id=$venueId)");
}

// ─── 3. 한국 공휴일 (2026년 핵심 몇 개) ────────────────────
$holidays = [
    ['2026-01-01', '신정'],
    ['2026-02-16', '설날 연휴'],
    ['2026-02-17', '설날'],
    ['2026-02-18', '설날 연휴'],
    ['2026-03-01', '삼일절'],
    ['2026-05-05', '어린이날'],
    ['2026-05-25', '부처님오신날'],
    ['2026-06-06', '현충일'],
    ['2026-08-15', '광복절'],
    ['2026-09-24', '추석 연휴'],
    ['2026-09-25', '추석'],
    ['2026-09-26', '추석 연휴'],
    ['2026-10-03', '개천절'],
    ['2026-10-09', '한글날'],
    ['2026-12-25', '성탄절'],
];
foreach ($holidays as [$d, $n]) {
    Db::query('INSERT IGNORE INTO holidays (holiday_date, name) VALUES (?, ?)', [$d, $n]);
}
out('holidays seeded: ' . count($holidays));

out('SEED DONE.');
