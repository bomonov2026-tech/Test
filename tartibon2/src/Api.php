<?php
declare(strict_types=1);

/** JSON API. Har bir so'rovda initData imzosi qaytadan tekshiriladi. */
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
                case 'me':             self::ok(self::me($user)); return;
                case 'wallet':         self::ok(['wallet' => App::wallet($user)]); return;
                case 'topup_create':   self::ok(self::topupCreate($user, $data)); return;
                case 'service_toggle': self::ok(self::serviceToggle($user, $data)); return;
                default:               self::fail('Noma\'lum amal: ' . $action, 404);
            }
        } catch (RuntimeException $e) {
            self::fail($e->getMessage(), 400);          // foydalanuvchiga ko'rsatiladi
        } catch (Throwable $e) {
            error_log('[tartibon] ' . $e->getMessage());
            self::fail('Server xatosi', 500);
        }
    }

    /** @param array<string,mixed> $user @return array<string,mixed> */
    private static function me(array $user): array
    {
        $state   = App::serviceState((int)$user['id']);
        $catalog = Catalog::build(App::hiddenServices(), $state);
        return [
            'user'    => $user,
            'stats'   => App::stats(),
            'wallet'  => App::wallet($user),
            'catalog' => $catalog,
            'counts'  => App::perCategory($catalog),
            'running' => App::runningCount($state),
            'next'    => App::nextEvent((int)$user['id']),
            'recent'  => App::recent((int)$user['id']),
            'summary' => App::summary((int)$user['id']),
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function serviceToggle(array $user, array $data): array
    {
        $id = (string)($data['id'] ?? '');
        $on = (bool)($data['on'] ?? false);
        App::toggleService((int)$user['id'], $id, $on);

        $state = App::serviceState((int)$user['id']);
        if (Config::isDemo()) {
            $state[$id] = ['on' => $on, 'value' => $on ? 'Yoniq' : "O'chiq"];
        }
        return [
            'id'      => $id,
            'on'      => $on,
            'running' => App::runningCount($state),
            'message' => $on ? 'Yoqildi' : "O'chirildi",
        ];
    }

    /**
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
                "Summa %s – %s so'm oralig'ida bo'lishi kerak",
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
