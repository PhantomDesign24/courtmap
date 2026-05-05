<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;

final class AuthController extends Controller
{
    // ─── Register ────────────────────────────────────────────
    public function showRegister(): void
    {
        if (Auth::check()) $this->redirect('/me');
        $this->view('auth/register', ['title' => '회원가입']);
    }

    public function register(): void
    {
        if (Auth::check()) $this->redirect('/me');

        $email   = trim((string) $this->input('email', ''));
        $phone   = self::normalizePhone((string) $this->input('phone', ''));
        $name    = trim((string) $this->input('name', ''));
        $pass    = (string) $this->input('password', '');
        $bank    = trim((string) $this->input('refund_bank_name', ''));
        $acct    = trim((string) $this->input('refund_bank_account', ''));
        $holder  = trim((string) $this->input('refund_bank_holder', ''));
        // 운영자 권한은 공개 가입에서 부여하지 않음. 관리자가 /admin/users 에서 변경.

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))                $errors[] = '이메일 형식이 올바르지 않습니다.';
        if (!preg_match('/^010-\d{3,4}-\d{4}$/', $phone))              $errors[] = '전화번호 형식이 올바르지 않습니다 (010-XXXX-XXXX).';
        if (mb_strlen($name) < 2)                                       $errors[] = '이름은 2자 이상이어야 합니다.';
        if (strlen($pass) < 8)                                          $errors[] = '비밀번호는 8자 이상이어야 합니다.';
        if ($bank === '' || $acct === '' || $holder === '')             $errors[] = '환불계좌 정보(은행·계좌번호·예금주)는 모두 필수입니다.';

        if (!$errors) {
            if (Db::fetch('SELECT id FROM users WHERE email = ?', [$email])) $errors[] = '이미 가입된 이메일입니다.';
            if (Db::fetch('SELECT id FROM users WHERE phone = ?', [$phone])) $errors[] = '이미 가입된 전화번호입니다.';
        }

        if ($errors) {
            $this->view('auth/register', [
                'title'  => '회원가입',
                'errors' => $errors,
                'old'    => compact('email', 'phone', 'name', 'bank', 'acct', 'holder'),
            ]);
            return;
        }

        $userId = Db::insert('users', [
            'email'               => $email,
            'phone'               => $phone,
            'name'                => $name,
            'password_hash'       => password_hash($pass, PASSWORD_BCRYPT),
            'role'                => 'user',
            'depositor_name'      => $name,
            'refund_bank_name'    => $bank,
            'refund_bank_account' => $acct,
            'refund_bank_holder'  => $holder,
        ]);

        Auth::login($userId);
        $this->redirect('/me');
    }

    // ─── Login ───────────────────────────────────────────────
    public function showLogin(): void
    {
        if (Auth::check()) $this->redirect('/me');
        $this->view('auth/login', ['title' => '로그인']);
    }

    public function login(): void
    {
        if (Auth::check()) $this->redirect('/me');

        $email = trim((string) $this->input('email', ''));
        $pass  = (string) $this->input('password', '');
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '';

        // brute force 방지 — IP+email 기준 최근 15분 실패 5회 이상이면 잠금
        $key = hash('sha256', $ip . '|' . strtolower($email));
        $bucket = $_SESSION['login_attempts'][$key] ?? ['count' => 0, 'until' => 0];
        if ($bucket['until'] > time()) {
            $remaining = (int) ($bucket['until'] - time());
            $this->view('auth/login', [
                'title'  => '로그인',
                'errors' => ['시도가 너무 많습니다. ' . ceil($remaining / 60) . '분 뒤에 다시 시도해주세요.'],
                'old'    => ['email' => $email],
            ]);
            return;
        }

        // 이메일이 아니면 name 으로도 로그인 허용 (예: admin / operator 같은 짧은 ID)
        $user = str_contains($email, '@')
            ? Db::fetch('SELECT id, password_hash, status FROM users WHERE email = ?', [$email])
            : Db::fetch('SELECT id, password_hash, status FROM users WHERE name = ? OR email = ?', [$email, $email]);
        $ok   = $user && $user['status'] === 'active' && password_verify($pass, $user['password_hash'] ?? '');

        if (!$ok) {
            // 실패 카운트 증가 (지수 backoff: 5/10/30/60분)
            $bucket['count']++;
            $bucket['until'] = $bucket['count'] >= 5 ? time() + min(3600, 300 * (1 << ($bucket['count'] - 5))) : 0;
            $_SESSION['login_attempts'][$key] = $bucket;
            $this->view('auth/login', [
                'title'  => '로그인',
                'errors' => ['이메일 또는 비밀번호가 올바르지 않습니다.'],
                'old'    => ['email' => $email],
            ]);
            return;
        }
        // 성공 시 카운트 초기화
        unset($_SESSION['login_attempts'][$key]);

        Auth::login((int) $user['id']);
        $next = (string) ($_GET['next'] ?? '/me');
        $this->redirect(str_starts_with($next, '/') ? $next : '/me');
    }

    // ─── Logout ──────────────────────────────────────────────
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/');
    }

    // ─── Kakao OAuth (placeholder) ───────────────────────────
    public function kakaoRedirect(): void
    {
        if (!\App\Services\KakaoOAuth::isConfigured()) {
            $this->view('auth/login', [
                'title'  => '로그인',
                'errors' => ['카카오 OAuth 가 설정되지 않았습니다 (.env 의 KAKAO_CLIENT_ID).'],
            ]);
            return;
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['kakao_state'] = $state;
        $this->redirect(\App\Services\KakaoOAuth::authorizeUrl($state));
    }

    public function kakaoCallback(): void
    {
        $code  = (string) ($_GET['code']  ?? '');
        $state = (string) ($_GET['state'] ?? '');
        $expected = $_SESSION['kakao_state'] ?? '';
        unset($_SESSION['kakao_state']);

        if ($code === '' || $state === '' || !hash_equals($expected, $state)) {
            $this->view('auth/login', ['title' => '로그인', 'errors' => ['카카오 인증 정보가 올바르지 않습니다.']]);
            return;
        }

        try {
            $token   = \App\Services\KakaoOAuth::exchangeCode($code);
            $profile = \App\Services\KakaoOAuth::fetchProfile($token['access_token']);
        } catch (\Throwable $e) {
            // 상세 사유는 서버 로그에만 기록, 사용자에겐 일반 메시지
            error_log('KakaoOAuth error: ' . $e->getMessage());
            $this->view('auth/login', ['title' => '로그인', 'errors' => ['카카오 로그인에 실패했습니다. 잠시 후 다시 시도해주세요.']]);
            return;
        }

        $kakaoId = (string) $profile['id'];
        $existing = \App\Core\Db::fetch('SELECT id, status FROM users WHERE kakao_id = ?', [$kakaoId]);
        if ($existing) {
            if ($existing['status'] !== 'active') {
                $this->view('auth/login', ['title' => '로그인', 'errors' => ['계정이 정지되었습니다.']]);
                return;
            }
            Auth::login((int) $existing['id']);
            $this->redirect('/me');
        }

        // 신규 — 프로필 완료 단계로 (phone, 환불계좌 받아야 INSERT 가능)
        $_SESSION['kakao_pending'] = [
            'kakao_id' => $kakaoId,
            'email'    => $profile['email']    ?? '',
            'nickname' => $profile['nickname'] ?? '',
        ];
        $this->redirect('/auth/kakao/complete');
    }

    public function kakaoCompleteForm(): void
    {
        $pending = $_SESSION['kakao_pending'] ?? null;
        if (!$pending) $this->redirect('/login');
        $this->view('auth/kakao_complete', ['title' => '추가 정보 입력', 'pending' => $pending]);
    }

    public function kakaoCompleteSubmit(): void
    {
        $pending = $_SESSION['kakao_pending'] ?? null;
        if (!$pending) $this->redirect('/login');

        $email  = trim((string) $this->input('email', $pending['email'] ?? ''));
        $phone  = self::normalizePhone((string) $this->input('phone', ''));
        $name   = trim((string) $this->input('name', $pending['nickname'] ?? ''));
        $bank   = trim((string) $this->input('refund_bank_name', ''));
        $acct   = trim((string) $this->input('refund_bank_account', ''));
        $holder = trim((string) $this->input('refund_bank_holder', ''));

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = '이메일 형식이 올바르지 않습니다.';
        if (!preg_match('/^010-\d{3,4}-\d{4}$/', $phone))  $errors[] = '전화번호 형식이 올바르지 않습니다.';
        if (mb_strlen($name) < 2)                           $errors[] = '이름은 2자 이상.';
        if ($bank === '' || $acct === '' || $holder === '') $errors[] = '환불계좌 정보 필수.';
        if (!$errors) {
            if (\App\Core\Db::fetch('SELECT id FROM users WHERE email = ?', [$email])) $errors[] = '이미 가입된 이메일입니다.';
            if (\App\Core\Db::fetch('SELECT id FROM users WHERE phone = ?', [$phone])) $errors[] = '이미 가입된 전화번호입니다.';
        }
        if ($errors) {
            $this->view('auth/kakao_complete', ['title' => '추가 정보 입력', 'pending' => $pending, 'errors' => $errors]);
            return;
        }

        $userId = \App\Core\Db::insert('users', [
            'email'               => $email,
            'phone'               => $phone,
            'name'                => $name,
            'kakao_id'            => $pending['kakao_id'],
            'role'                => 'user',
            'depositor_name'      => $name,
            'refund_bank_name'    => $bank,
            'refund_bank_account' => $acct,
            'refund_bank_holder'  => $holder,
        ]);
        unset($_SESSION['kakao_pending']);
        Auth::login($userId);
        $this->redirect('/me');
    }

    // ─── helpers ─────────────────────────────────────────────
    private static function normalizePhone(string $p): string
    {
        $digits = preg_replace('/\D+/', '', $p) ?? '';
        if (preg_match('/^010(\d{3,4})(\d{4})$/', $digits, $m)) {
            return '010-' . $m[1] . '-' . $m[2];
        }
        return $p;
    }
}
