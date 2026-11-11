<?php
declare(strict_types=1);

/**
 * Bo'limlar va xizmatlar ro'yxati — ilovaning yagona manbasi.
 * Frontend ham, bot ham shu ro'yxatdan foydalanadi.
 * icon — assets/icons.js dagi kalit.
 */
final class Catalog
{
    private const DATA = [
        [
            'id' => 'manage', 'title' => 'Boshqaruv', 'subtitle' => 'Kanal va guruh boshqaruvi', 'icon' => 'manage',
            'items' => [
                ['id' => 'my-channel', 'title' => 'Kanallarim',    'icon' => 'channel',  'sub' => 'Ulangan kanallar'],
                ['id' => 'my-group',   'title' => 'Guruhlarim',    'icon' => 'group',    'sub' => 'Ulangan guruhlar'],
                ['id' => 'shield',     'title' => 'Guruh himoyasi','icon' => 'shield',   'sub' => 'Spam va reklamaga qarshi'],
                ['id' => 'schedule',   'title' => 'Avto Post',     'icon' => 'calendar', 'sub' => 'Rejalashtirilgan postlar'],
                ['id' => 'stats',      'title' => 'Statistika',    'icon' => 'chart',    'sub' => 'Obunachilar dinamikasi'],
                ['id' => 'tests',      'title' => 'Testlar',       'icon' => 'test',     'sub' => 'Guruhdagi test o\'yinlari'],
                ['id' => 'paid',       'title' => 'Pullik kanallar','icon' => 'paid',    'sub' => 'Obuna asosidagi kanal'],
                ['id' => 'contest',    'title' => 'Konkurs',       'icon' => 'trophy',   'sub' => 'Sovrinli tanlovlar'],
            ],
        ],
        [
            'id' => 'daily', 'title' => 'Avtomatik xabarlar', 'subtitle' => 'Har kunlik xabar va eslatmalar', 'icon' => 'daily',
            'items' => [
                ['id' => 'prayer',   'title' => 'Namoz vaqtlari',   'icon' => 'prayer',   'sub' => 'Kunlik namoz jadvali'],
                ['id' => 'currency', 'title' => 'Dollar kursi',     'icon' => 'currency', 'sub' => 'Markaziy bank kursi'],
                ['id' => 'weather',  'title' => 'Ob-havo',          'icon' => 'weather',  'sub' => 'Viloyatlar bo\'yicha'],
                ['id' => 'morning',  'title' => 'Tongi xabar',      'icon' => 'morning',  'sub' => 'Ertalabki avtomatik post'],
                ['id' => 'night',    'title' => 'Tungi xabar',      'icon' => 'night',    'sub' => 'Kechki avtomatik post'],
                ['id' => 'gift',     'title' => 'Bayram tabriklari','icon' => 'gift',     'sub' => 'Bayramlarda avtomatik'],
                ['id' => 'ramadan',  'title' => 'Ramazon taqvimi',  'icon' => 'ramadan',  'sub' => 'Saharlik va iftorlik'],
                ['id' => 'rates',    'title' => 'Valyutalar',       'icon' => 'currency', 'sub' => 'Banklar kursi'],
            ],
        ],
        [
            'id' => 'digital', 'title' => 'Raqamli xizmatlar', 'subtitle' => 'Nakrutka, eSIM, Premium va Stars', 'icon' => 'digital',
            'items' => [
                ['id' => 'growth',  'title' => 'Nakrutka',         'icon' => 'digital',  'sub' => 'Obunachi va ko\'rish'],
                ['id' => 'esim',    'title' => 'eSIM',             'icon' => 'esim',     'sub' => 'Xorijiy raqamlar'],
                ['id' => 'premium', 'title' => 'Telegram Premium', 'icon' => 'premium',  'sub' => 'Rasmiy obuna'],
                ['id' => 'stars',   'title' => 'Stars',            'icon' => 'stars',    'sub' => 'Telegram yulduzlari'],
            ],
        ],
        [
            'id' => 'help', 'title' => 'Xizmatlar va yordam', 'subtitle' => 'Hamkorlik, yordam va qo\'shimcha xizmatlar', 'icon' => 'help',
            'items' => [
                ['id' => 'bot',      'title' => 'Bot yaratish',      'icon' => 'help',      'sub' => 'O\'z botingizni oching'],
                ['id' => 'referral', 'title' => 'Taklif havolalari', 'icon' => 'group',     'sub' => 'Do\'st taklif qilish'],
                ['id' => 'partner',  'title' => 'Hamkor dasturi',    'icon' => 'job',       'sub' => 'Daromad ulushi'],
                ['id' => 'prava',    'title' => 'Prava testlari',    'icon' => 'education', 'sub' => 'Yo\'l harakati qoidalari'],
                ['id' => 'donate',   'title' => 'Qo\'llab-quvvatlash','icon' => 'gift',     'sub' => 'Loyihaga hissa'],
                ['id' => 'botdev',   'title' => 'Bot rivoji',        'icon' => 'brand',     'sub' => 'Yangiliklar'],
                ['id' => 'support',  'title' => 'Savol va takliflar','icon' => 'question',  'sub' => 'Adminga yozish'],
                ['id' => 'about',    'title' => 'Ilova haqida',      'icon' => 'info',      'sub' => 'Versiya va shartlar'],
            ],
        ],
    ];

    /**
     * Yashirilgan xizmat id'lari olib tashlangan ro'yxat.
     * @param list<string> $hidden
     * @return list<array<string,mixed>>
     */
    public static function build(array $hidden = []): array
    {
        $out = [];
        foreach (self::DATA as $cat) {
            $cat['items'] = array_values(array_filter(
                $cat['items'],
                static fn(array $i): bool => !in_array($i['id'], $hidden, true)
            ));
            if ($cat['items'] !== []) {
                $out[] = $cat;
            }
        }
        return $out;
    }

    /** Bitta xizmat id bo'yicha. @return array<string,mixed>|null */
    public static function service(string $id): ?array
    {
        foreach (self::DATA as $cat) {
            foreach ($cat['items'] as $item) {
                if ($item['id'] === $id) {
                    return $item + ['category' => $cat['id']];
                }
            }
        }
        return null;
    }
}
