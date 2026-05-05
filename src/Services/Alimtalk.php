<?php
declare(strict_types=1);

namespace App\Services;

/**
 * 카카오 알림톡 발송 — SOLAPI 호환 인터페이스 (.env 키 미설정 시 자동 skip).
 * 실제 발송하려면 SOLAPI 가입 + 발신프로필·템플릿 승인 후 키 입력.
 *
 * .env 추가:
 *   SOLAPI_API_KEY=...
 *   SOLAPI_API_SECRET=...
 *   ALIMTALK_PFID=...   (발신 프로필 ID)
 */
final class Alimtalk
{
    public static function isConfigured(): bool
    {
        return !empty($_ENV['SOLAPI_API_KEY'])
            && !empty($_ENV['SOLAPI_API_SECRET'])
            && !empty($_ENV['ALIMTALK_PFID']);
    }

    /**
     * 알림톡 발송. 성공 시 true, 실패 시 false (로그 남김).
     * @param string $templateId 사전 승인된 템플릿 ID
     * @param string $toPhone   "01012345678" 형식 (하이픈 자동 제거)
     * @param array  $vars      템플릿 변수 ['#{name}' => '박지훈', ...]
     * @param string $fallback  알림톡 실패 시 SMS 로 보낼 본문 (선택)
     */
    public static function send(string $templateId, string $toPhone, array $vars = [], string $fallback = ''): bool
    {
        if (!self::isConfigured()) {
            error_log('alimtalk: not configured — skip');
            return false;
        }

        $phone = preg_replace('/\D+/', '', $toPhone) ?? '';
        if ($phone === '') return false;

        $apiKey    = $_ENV['SOLAPI_API_KEY'];
        $apiSecret = $_ENV['SOLAPI_API_SECRET'];
        $pfid      = $_ENV['ALIMTALK_PFID'];

        // SOLAPI 인증 시그니처 (HMAC-SHA256)
        $now    = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
        $salt   = bin2hex(random_bytes(16));
        $sig    = hash_hmac('sha256', $now . $salt, $apiSecret);
        $auth   = "HMAC-SHA256 ApiKey=$apiKey, Date=$now, Salt=$salt, Signature=$sig";

        $body = json_encode([
            'message' => [
                'to'              => $phone,
                'from'            => $_ENV['SMS_FROM'] ?? '',
                'type'            => 'ATA',
                'kakaoOptions'    => [
                    'pfId'           => $pfid,
                    'templateId'     => $templateId,
                    'variables'      => $vars,
                    'disableSms'     => false,
                ],
                'text' => $fallback,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.solapi.com/messages/v4/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: ' . $auth,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $res  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http >= 200 && $http < 300) return true;
        error_log("alimtalk fail: http=$http body=" . substr((string) $res, 0, 200));
        return false;
    }
}
