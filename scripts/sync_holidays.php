<?php
// 한국 공휴일 자동 동기화 — date.nager.at (무료·키 불필요).
// 매일 04:30 cron 실행.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Db;

$years  = [(int) date('Y'), (int) date('Y') + 1]; // 올해 + 내년
$total  = 0;

foreach ($years as $y) {
    $url = "https://date.nager.at/api/v3/PublicHolidays/$y/KR";
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) {
        error_log("sync_holidays: fetch fail for $y");
        continue;
    }
    $items = json_decode($json, true);
    if (!is_array($items)) {
        error_log("sync_holidays: bad json for $y");
        continue;
    }

    foreach ($items as $it) {
        $date = (string) ($it['date']      ?? '');
        $name = (string) ($it['localName'] ?? ($it['name'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $name === '') continue;

        // 대체공휴일 판정 — 이름에 "대체" 포함 또는 types 배열에 substitute 단서
        $isSubst = (bool) preg_match('/대체/u', $name);

        Db::query(
            'INSERT INTO holidays (holiday_date, name, is_substitute) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), is_substitute = VALUES(is_substitute)',
            [$date, $name, $isSubst ? 1 : 0]
        );
        $total++;
    }
}

echo date('Y-m-d H:i:s') . " sync_holidays: $total row(s) upserted (date.nager.at)\n";
