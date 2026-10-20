<?php
/**
 * Telegram bot: sizning akkauntingiz nomidan kanal yoki guruh ochadi
 * va siz ko'rsatgan foydalanuvchini admin qiladi.
 *
 * Bot API o'zi kanal/guruh yarata olmaydi, shuning uchun yaratish
 * MadelineProto (MTProto) orqali SIZNING akkauntingizdan bajariladi,
 * bot esa faqat buyruq qabul qilish uchun ishlatiladi.
 *
 * Buyruqlar (botga yozing):
 *   /kanal Kanal nomi | @username
 *   /guruh Guruh nomi | @username
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;

$config = require __DIR__ . '/config.php';

// ---------- Bot API yordamchi funksiyalari ----------

function botApi(string $method, array $params = [], int $timeout = 35): array
{
    global $config;
    $ch = curl_init("https://api.telegram.org/bot{$config['bot_token']}/{$method}");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? (json_decode($res, true) ?? []) : [];
}

function reply(int $chatId, string $text): void
{
    botApi('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'disable_web_page_preview' => 'true']);
}

// ---------- Sizning akkauntingizga ulanish (MTProto) ----------

$settings = (new Settings)->setAppInfo(
    (new AppInfo)->setApiId($config['api_id'])->setApiHash($config['api_hash'])
);

$user = new API(__DIR__ . '/user.madeline', $settings);
// Birinchi ishga tushirishda telefon raqam va SMS kodni so'raydi.
$user->start();

$me = $user->getSelf();
echo "Akkauntga ulandi: {$me['first_name']} (id {$me['id']})\n";

// ---------- Asosiy ish: kanal/guruh yaratish va admin qilish ----------

/**
 * @return string Foydalanuvchiga yuboriladigan natija matni
 */
function createAndPromote(API $user, bool $isChannel, string $title, string|int $target): string
{
    // 1. Kanal yoki superguruh yaratish
    $updates = $user->channels->createChannel(
        broadcast: $isChannel,
        megagroup: !$isChannel,
        title: $title,
        about: ''
    );
    $chat   = $updates['chats'][0];
    $chatId = $user->getId($chat);

    // 2. Kerakli foydalanuvchini topish
    $userId = $user->getId($target);

    // 3. Superguruhga avval qo'shamiz (kanalga editAdmin o'zi qo'shadi)
    if (!$isChannel) {
        try {
            $user->channels->inviteToChannel(channel: $chatId, users: [$userId]);
        } catch (\Throwable $e) {
            // Ba'zan maxfiylik sozlamalari to'sadi; editAdmin baribir urinib ko'radi
        }
    }

    // 4. Admin huquqlarini berish
    $rights = [
        '_'               => 'chatAdminRights',
        'change_info'     => true,
        'delete_messages' => true,
        'ban_users'       => true,
        'invite_users'    => true,
        'pin_messages'    => true,
        'manage_call'     => true,
        'other'           => true,
        'add_admins'      => false, // true qilsangiz, u ham admin tayinlay oladi
    ];
    if ($isChannel) {
        $rights['post_messages'] = true;
        $rights['edit_messages'] = true;
    }

    $user->channels->editAdmin(
        channel: $chatId,
        user_id: $userId,
        admin_rights: $rights,
        rank: 'Admin'
    );

    // 5. Taklif havolasi
    $link = $user->messages->exportChatInvite(peer: $chatId)['link'] ?? '-';

    $type = $isChannel ? 'Kanal' : 'Guruh';
    return "✅ {$type} yaratildi: {$title}\n👤 Admin: {$target}\n🔗 {$link}";
}

// ---------- Bot: buyruqlarni kutish (long polling) ----------

$offsetFile = __DIR__ . '/offset.txt';
$offset = is_file($offsetFile) ? (int) file_get_contents($offsetFile) : 0;

echo "Bot ishga tushdi. Buyruqlarni kutyapman...\n";

while (true) {
    $resp = botApi('getUpdates', ['offset' => $offset, 'timeout' => 25]);

    foreach ($resp['result'] ?? [] as $upd) {
        $offset = $upd['update_id'] + 1;
        file_put_contents($offsetFile, (string) $offset);

        $msg = $upd['message'] ?? null;
        if (!$msg || !isset($msg['text'])) {
            continue;
        }

        $chatId = $msg['chat']['id'];
        $fromId = $msg['from']['id'];
        $text   = trim($msg['text']);

        // Faqat egasi buyruq bera oladi
        if ($fromId !== (int) $config['owner_id']) {
            reply($chatId, "⛔ Bu bot faqat egasi uchun.");
            continue;
        }

        if ($text === '/start' || $text === '/help') {
            reply($chatId,
                "Salom! Buyruqlar:\n\n" .
                "/kanal Kanal nomi | @username\n" .
                "/guruh Guruh nomi | @username\n\n" .
                "Kanal/guruh sizning akkauntingiz nomidan ochiladi va ko'rsatilgan user admin qilinadi."
            );
            continue;
        }

        if (!preg_match('~^/(kanal|guruh)(?:@\w+)?\s+(.+?)\s*\|\s*(@?\w{3,}|\d+)\s*$~u', $text, $m)) {
            if (str_starts_with($text, '/kanal') || str_starts_with($text, '/guruh')) {
                reply($chatId, "Format: /kanal Nomi | @username");
            }
            continue;
        }

        [, $cmd, $title, $target] = $m;
        if (ctype_digit($target)) {
            $target = (int) $target;
        }

        reply($chatId, "⏳ Yaratilmoqda...");
        try {
            reply($chatId, createAndPromote($user, $cmd === 'kanal', $title, $target));
        } catch (\Throwable $e) {
            reply($chatId, "❌ Xatolik: " . $e->getMessage());
        }
    }
}
