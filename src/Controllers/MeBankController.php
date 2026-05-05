<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;

final class MeBankController extends Controller
{
    public function form(): void
    {
        $user = $this->requireAuth();
        $u = Db::fetch('SELECT name, email, refund_bank_name, refund_bank_account, refund_bank_holder FROM users WHERE id = ?', [(int) $user['id']]);
        $this->view('me_bank', [
            'title'   => '환불계좌 관리 — 코트맵',
            'noindex' => true,
            'u'       => $u,
        ]);
    }

    public function update(): void
    {
        $user = $this->requireAuth();
        $bank   = trim((string) ($_POST['refund_bank_name'] ?? ''));
        $acct   = trim((string) ($_POST['refund_bank_account'] ?? ''));
        $holder = trim((string) ($_POST['refund_bank_holder'] ?? ''));

        $errors = [];
        if ($bank === '' || $acct === '' || $holder === '') $errors[] = '모든 필드를 입력해주세요.';

        if (!$errors) {
            // 비밀번호 재확인 (계좌 변경은 민감 작업)
            $pw = (string) ($_POST['password'] ?? '');
            $row = Db::fetch('SELECT password_hash FROM users WHERE id = ?', [(int) $user['id']]);
            if (!$row || !password_verify($pw, $row['password_hash'] ?? '')) {
                $errors[] = '비밀번호가 일치하지 않습니다.';
            }
        }

        if ($errors) {
            $u = Db::fetch('SELECT name, email, refund_bank_name, refund_bank_account, refund_bank_holder FROM users WHERE id = ?', [(int) $user['id']]);
            $this->view('me_bank', [
                'title'   => '환불계좌 관리 — 코트맵',
                'noindex' => true,
                'u'       => $u,
                'errors'  => $errors,
            ]);
            return;
        }

        Db::query(
            'UPDATE users SET refund_bank_name = ?, refund_bank_account = ?, refund_bank_holder = ? WHERE id = ?',
            [$bank, $acct, $holder, (int) $user['id']]
        );
        Db::insert('notifications', [
            'user_id' => (int) $user['id'],
            'type'    => 'system',
            'title'   => '환불계좌가 변경되었습니다',
            'body'    => $bank . ' · ' . self::mask($acct),
        ]);
        $this->redirect('/me?bank=updated');
    }

    private static function mask(string $acct): string
    {
        $digits = preg_replace('/\D+/', '', $acct) ?? '';
        return $digits === '' ? '***' : '***-' . substr($digits, -4);
    }
}
