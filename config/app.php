<?php
return [
    'name'     => $_ENV['APP_NAME']     ?? '코트맵',
    'env'      => $_ENV['APP_ENV']      ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL']      ?? 'https://bad.mvc.kr',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Seoul',
    'session'  => [
        'name'     => $_ENV['SESSION_NAME']     ?? 'courtmap_session',
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 86400),
    ],
    'kakao' => [
        'client_id'     => $_ENV['KAKAO_CLIENT_ID']     ?? '',
        'client_secret' => $_ENV['KAKAO_CLIENT_SECRET'] ?? '',
        'redirect_uri'  => $_ENV['KAKAO_REDIRECT_URI']  ?? '',
    ],
];
