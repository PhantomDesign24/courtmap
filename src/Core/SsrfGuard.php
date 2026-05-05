<?php
declare(strict_types=1);

namespace App\Core;

/**
 * 외부 호출 URL 안전성 검증 (Webhook, fetch 등 SSRF 차단).
 */
final class SsrfGuard
{
    /**
     * @return array{ok:bool, reason?:string}
     */
    public static function check(string $url): array
    {
        $p = parse_url($url);
        if (!$p || empty($p['host']) || empty($p['scheme'])) {
            return ['ok' => false, 'reason' => 'URL 형식이 잘못되었습니다'];
        }
        if (!in_array(strtolower($p['scheme']), ['http', 'https'], true)) {
            return ['ok' => false, 'reason' => 'http(s) 만 허용됩니다'];
        }
        if (strtolower($p['scheme']) === 'http') {
            // production 에선 https 강제. 개발 편의 위해 http 도 허용 가능 — 정책 결정.
            return ['ok' => false, 'reason' => 'https 만 허용됩니다'];
        }

        $host = $p['host'];
        // 호스트 자체가 IP면 직접, 아니면 DNS resolve
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (!$ips) {
            return ['ok' => false, 'reason' => '호스트를 찾을 수 없습니다'];
        }

        foreach ($ips as $ip) {
            if (self::isPrivate($ip)) {
                return ['ok' => false, 'reason' => '내부/사설/loopback IP 는 허용되지 않습니다'];
            }
        }
        return ['ok' => true];
    }

    private static function isPrivate(string $ip): bool
    {
        // FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE 으로 한방에 검사
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
