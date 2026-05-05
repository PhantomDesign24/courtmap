<?php
declare(strict_types=1);

namespace App\Services;

final class KakaoOAuth
{
    private const AUTH_URL  = 'https://kauth.kakao.com/oauth/authorize';
    private const TOKEN_URL = 'https://kauth.kakao.com/oauth/token';
    private const ME_URL    = 'https://kapi.kakao.com/v2/user/me';

    public static function isConfigured(): bool
    {
        return !empty($_ENV['KAKAO_CLIENT_ID']) && !empty($_ENV['KAKAO_REDIRECT_URI']);
    }

    public static function authorizeUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => $_ENV['KAKAO_CLIENT_ID'],
            'redirect_uri'  => $_ENV['KAKAO_REDIRECT_URI'],
            'response_type' => 'code',
            'state'         => $state,
            'scope'         => 'profile_nickname account_email',
        ]);
    }

    /** @return array{access_token:string, ...} */
    public static function exchangeCode(string $code): array
    {
        $body = http_build_query([
            'grant_type'    => 'authorization_code',
            'client_id'     => $_ENV['KAKAO_CLIENT_ID'],
            'client_secret' => $_ENV['KAKAO_CLIENT_SECRET'] ?? '',
            'redirect_uri'  => $_ENV['KAKAO_REDIRECT_URI'],
            'code'          => $code,
        ]);
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => 'Content-Type: application/x-www-form-urlencoded',
            'content'       => $body,
            'ignore_errors' => true,
            'timeout'       => 10,
        ]]);
        $res = file_get_contents(self::TOKEN_URL, false, $ctx);
        if ($res === false) throw new \RuntimeException('카카오 토큰 교환 실패');
        $data = json_decode($res, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new \RuntimeException('토큰 응답 비정상: ' . substr((string) $res, 0, 200));
        }
        return $data;
    }

    /** @return array{id:int, email:?string, nickname:?string} */
    public static function fetchProfile(string $accessToken): array
    {
        $ctx = stream_context_create(['http' => [
            'method'        => 'GET',
            'header'        => "Authorization: Bearer $accessToken",
            'ignore_errors' => true,
            'timeout'       => 10,
        ]]);
        $res = file_get_contents(self::ME_URL, false, $ctx);
        if ($res === false) throw new \RuntimeException('카카오 프로필 조회 실패');
        $j = json_decode($res, true);
        if (!is_array($j) || empty($j['id'])) {
            throw new \RuntimeException('프로필 응답 비정상');
        }
        return [
            'id'       => (int) $j['id'],
            'email'    => $j['kakao_account']['email']    ?? null,
            'nickname' => $j['kakao_account']['profile']['nickname'] ?? ($j['properties']['nickname'] ?? null),
        ];
    }
}
