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
            // 발사 직전 SSRF 재검증 (DNS 변경·등록 후 차단 정책 변경 대비)
            $check = \App\Core\SsrfGuard::check((string) $h['url']);
            if (!$check['ok']) {
                Db::query('UPDATE webhooks SET status = "failed", failure_count = failure_count + 1, last_failure_at = NOW() WHERE id = ?', [(int) $h['id']]);
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
                CURLOPT_FOLLOWLOCATION => false,  // 리다이렉트 차단 (재검증 비용)
                CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            ]);
            curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($http >= 200 && $http < 300) {
                Db::query('UPDATE webhooks SET last_success_at = NOW(), failure_count = 0 WHERE id = ?', [(int) $h['id']]);
            } else {
                $newCount = (int) $h['failure_count'] + 1;
                $newStatus = $newCount >= 3 ? 'failed' : 'active';
                Db::query(
                    'UPDATE webhooks SET last_failure_at = NOW(), failure_count = ?, status = ? WHERE id = ?',
                    [$newCount, $newStatus, (int) $h['id']]
                );
                error_log("webhook fail venue=$venueId event=$eventType http=$http err=$err");
            }
        }
    }
}
