<?php
declare(strict_types=1);

/** JSON API. Har bir so'rov initData bilan keladi va qaytadan tekshiriladi. */
final class Api
{
    public static function handle(string $action): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $body = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($body) ? $body : [];
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        try {
            $user = App::user((string)($body['initData'] ?? ''));
            if ($user === null) {
                self::fail('Avtorizatsiya amalga oshmadi', 401);
                return;
            }
            switch ($action) {
                case 'me':           self::ok(self::me($user)); return;
                case 'wallet':       self::ok(['wallet' => App::wallet($user)]); return;
                case 'topup_create': self::ok(self::topupCreate($user, $data)); return;
                default:             self::fail('Noma\'lum amal: ' . $action, 404);
            }
        } catch (RuntimeException $e) {
            // foydalanuvchiga ko'rsatiladigan xabar
            self::fail($e->getMessage(), 400);
        } catch (Throwable $e) {
            error_log('[tartibon] ' . $e->getMessage());
            self::fail('Server xatosi', 500);
        }
    }

    /** @param array<string,mixed> $user @return array<string,mixed> */
    private static function me(array $user): array
    {
        return [
            'user'    => $user,
            'stats'   => App::stats(),
            'wallet'  => App::wallet($user),
            'catalog' => Catalog::build(App::hiddenServices()),
        ];
    }

    /**
     * Balansni to'ldirish arizasi.
     * @param array<string,mixed> $user
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function topupCreate(array $user, array $data): array
    {
        $w      = App::wallet($user);
        $amount = (int)($data['amount'] ?? 0);

        if ($amount < $w['min'] || $amount > $w['max']) {
            throw new RuntimeException(sprintf(
                "Summa %s \u{2013} %s so'm oralig'ida bo'lishi kerak",
                number_format($w['min'], 0, '.', ' '),
                number_format($w['max'], 0, '.', ' ')
            ));
        }

        if (!Config::isDemo()) {
            Db::run(
                "INSERT INTO topups (tg_id, amount, status, created_at) VALUES (?, ?, 'pending', NOW())",
                [(int)$user['id'], $amount]
            );
        }

        return [
            'message' => 'Ariza qabul qilindi. To\'lov tasdiqlangach balans yangilanadi.',
            'wallet'  => $w,
        ];
    }

    /** @param array<string,mixed> $payload */
    private static function ok(array $payload): void
    {
        echo json_encode(['ok' => true] + $payload, JSON_UNESCAPED_UNICODE);
    }

    private static function fail(string $message, int $code): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    }
}
