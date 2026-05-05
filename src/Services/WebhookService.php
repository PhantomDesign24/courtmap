<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class WebhookService
{
    /**
     * 이벤트 발생 시 등록된 webhook 들에 동기 POST.
     * 실패 시 failure_count++, 3회 누적 실패 시 status='failed'.
     */
    public static function fire(string $eventType, int $venueId, array $payload): void
    {
        $hooks = Db::fetchAll(
            'SELECT id, url, secret, failure_count FROM webhooks
             WHERE venue_id = ? AND event_type = ? AND status = "active"',
            [$venueId, $eventType]
        );
        if (!$hooks) return;

        $body = json_encode([
            'event'   => $eventType,
            'data'    => $payload,
            'fired_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE);

        foreach ($hooks as $h) {
            // 발사 직전 SSRF 재검증 + IP 핀
            $check = \App\Core\SsrfGuard::check((string) $h['url']);
            if (!$check['ok']) {
                // HTTP 실패와 동일하게 임계값 누적 (3회) — DNS hiccup 으로 영구 disable 방지
                self::recordFailure((int) $h['id'], $h['failure_count']);
                continue;
            }
            $sig = hash_hmac('sha256', $body, $h['secret']);
            $ch  = curl_init($h['url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-CourtMap-Event: ' . $eventType,
                    'X-CourtMap-Signature: sha256=' . $sig,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
                // DNS rebinding 차단 — 검증한 IP 로 직접 연결
                CURLOPT_RESOLVE        => [$check['host'] . ':' . $check['port'] . ':' . $check['ip']],
            ]);
            curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($http >= 200 && $http < 300) {
                Db::query('UPDATE webhooks SET last_success_at = NOW(), failure_count = 0 WHERE id = ?', [(int) $h['id']]);
            } else {
                self::recordFailure((int) $h['id'], $h['failure_count']);
                error_log("webhook fail venue=$venueId event=$eventType http=$http err=$err");
            }
        }
    }

    private static function recordFailure(int $hookId, int $currentCount): void
    {
        $newCount  = $currentCount + 1;
        $newStatus = $newCount >= 3 ? 'failed' : 'active';
        Db::query(
            'UPDATE webhooks SET last_failure_at = NOW(), failure_count = ?, status = ? WHERE id = ?',
            [$newCount, $newStatus, $hookId]
        );
    }
}
