<?php
declare(strict_types=1);

/**
 * Bo'limlar va xizmatlar — ilovaning yagona manbasi.
 *
 * kind   — rang va ma'no:
 *          'manage'  ko'k    — siz boshqarasiz
 *          'auto'    yashil  — ilova o'zi bajaradi (tumbler bor)
 *          'digital' binafsha — sotib olinadi
 *          'help'    ko'k    — yordam
 * toggle — true bo'lsa xizmat ro'yxatda tumbler bilan chiqadi
 * icon   — assets/icons.js dagi kalit
 */
final class Catalog
{
    private const DATA = [
        [
            'id' => 'manage', 'title' => 'Boshqaruv', 'subtitle' => 'Kanal va guruh boshqaruvi',
            'icon' => 'manage', 'kind' => 'manage',
            'items' => [
                ['id' => 'my-channel', 'title' => 'Kanallarim',     'icon' => 'channel',  'toggle' => false, 'sub' => 'Ulangan kanallar'],
                ['id' => 'my-group',   'title' => 'Guruhlarim',     'icon' => 'group',    'toggle' => false, 'sub' => 'Ulangan guruhlar'],
                ['id' => 'shield',     'title' => 'Guruh himoyasi', 'icon' => 'shield',   'toggle' => true,  'sub' => 'Spam va reklamaga qarshi'],
                ['id' => 'schedule',   'title' => 'Avto Post',      'icon' => 'calendar', 'toggle' => true,  'sub' => 'Rejalashtirilgan postlar'],
                ['id' => 'stats',      'title' => 'Statistika',     'icon' => 'chart',    'toggle' => false, 'sub' => 'Obunachilar dinamikasi'],
                ['id' => 'tests',      'title' => 'Testlar',        'icon' => 'test',     'toggle' => false, 'sub' => 'Guruhdagi test o\'yinlari'],
                ['id' => 'paid',       'title' => 'Pullik kanallar','icon' => 'paid',     'toggle' => false, 'sub' => 'Obuna asosidagi kanal'],
                ['id' => 'contest',    'title' => 'Konkurs',        'icon' => 'trophy',   'toggle' => false, 'sub' => 'Sovrinli tanlovlar'],
            ],
        ],
        [
            'id' => 'daily', 'title' => 'Avtomatik xabarlar', 'subtitle' => 'Har kunlik xabar va eslatmalar',
            'icon' => 'daily', 'kind' => 'auto',
            'items' => [
                ['id' => 'prayer',   'title' => 'Namoz vaqtlari',    'icon' => 'prayer',   'toggle' => true, 'sub' => 'Kunlik namoz jadvali'],
                ['id' => 'currency', 'title' => 'Dollar kursi',      'icon' => 'currency', 'toggle' => true, 'sub' => 'Markaziy bank kursi'],
                ['id' => 'weather',  'title' => 'Ob-havo',           'icon' => 'weather',  'toggle' => true, 'sub' => 'Viloyatlar bo\'yicha'],
                ['id' => 'morning',  'title' => 'Tongi xabar',       'icon' => 'morning',  'toggle' => true, 'sub' => 'Ertalabki avtomatik post'],
                ['id' => 'night',    'title' => 'Tungi xabar',       'icon' => 'night',    'toggle' => true, 'sub' => 'Kechki avtomatik post'],
                ['id' => 'gift',     'title' => 'Bayram tabriklari', 'icon' => 'gift',     'toggle' => true, 'sub' => 'Bayramlarda avtomatik'],
                ['id' => 'ramadan',  'title' => 'Ramazon taqvimi',   'icon' => 'ramadan',  'toggle' => true, 'sub' => 'Saharlik va iftorlik'],
                ['id' => 'rates',    'title' => 'Valyutalar',        'icon' => 'currency', 'toggle' => true, 'sub' => 'Banklar kursi'],
            ],
        ],
        [
            'id' => 'digital', 'title' => 'Raqamli xizmatlar', 'subtitle' => 'Nakrutka, eSIM, Premium va Stars',
            'icon' => 'digital', 'kind' => 'digital',
            'items' => [
                ['id' => 'growth',  'title' => 'Nakrutka',         'icon' => 'digital', 'toggle' => false, 'sub' => 'Obunachi va ko\'rish'],
                ['id' => 'esim',    'title' => 'eSIM',             'icon' => 'esim',    'toggle' => false, 'sub' => 'Xorijiy raqamlar'],
                ['id' => 'premium', 'title' => 'Telegram Premium', 'icon' => 'premium', 'toggle' => false, 'sub' => 'Rasmiy obuna'],
                ['id' => 'stars',   'title' => 'Stars',            'icon' => 'stars',   'toggle' => false, 'sub' => 'Telegram yulduzlari'],
            ],
        ],
        [
            'id' => 'help', 'title' => 'Xizmatlar va yordam', 'subtitle' => 'Hamkorlik, yordam va qo\'shimcha xizmatlar',
            'icon' => 'help', 'kind' => 'help',
            'items' => [
                ['id' => 'bot',      'title' => 'Bot yaratish',      'icon' => 'help',      'toggle' => false, 'sub' => 'O\'z botingizni oching'],
                ['id' => 'referral', 'title' => 'Taklif havolalari', 'icon' => 'group',     'toggle' => false, 'sub' => 'Do\'st taklif qilish'],
                ['id' => 'partner',  'title' => 'Hamkor dasturi',    'icon' => 'job',       'toggle' => false, 'sub' => 'Daromad ulushi'],
                ['id' => 'prava',    'title' => 'Prava testlari',    'icon' => 'education', 'toggle' => false, 'sub' => 'Yo\'l harakati qoidalari'],
                ['id' => 'donate',   'title' => 'Qo\'llab-quvvatlash','icon' => 'gift',     'toggle' => false, 'sub' => 'Loyihaga hissa'],
                ['id' => 'botdev',   'title' => 'Bot rivoji',        'icon' => 'brand',     'toggle' => false, 'sub' => 'Yangiliklar'],
                ['id' => 'support',  'title' => 'Savol va takliflar','icon' => 'question',  'toggle' => false, 'sub' => 'Adminga yozish'],
                ['id' => 'about',    'title' => 'Ilova haqida',      'icon' => 'info',      'toggle' => false, 'sub' => 'Versiya va shartlar'],
            ],
        ],
    ];

    /**
     * Bo'limlar, yashirilganlari olib tashlangan holda.
     * $state — xizmat id => ['on' => bool, 'value' => string]
     *
     * @param list<string>              $hidden
     * @param array<string,array<string,mixed>> $state
     * @return list<array<string,mixed>>
     */
    public static function build(array $hidden = [], array $state = []): array
    {
        $out = [];
        foreach (self::DATA as $cat) {
            $items = [];
            foreach ($cat['items'] as $item) {
                if (in_array($item['id'], $hidden, true)) {
                    continue;
                }
                $st = $state[$item['id']] ?? [];
                $item['on']    = (bool)($st['on'] ?? false);
                $item['value'] = (string)($st['value'] ?? '');
                $items[] = $item;
            }
            if ($items !== []) {
                $cat['items'] = $items;
                $out[] = $cat;
            }
        }
        return $out;
    }

    /** Tumbler bilan boshqariladigan xizmat id'lari. @return list<string> */
    public static function toggleable(): array
    {
        $ids = [];
        foreach (self::DATA as $cat) {
            foreach ($cat['items'] as $item) {
                if ($item['toggle']) {
                    $ids[] = $item['id'];
                }
            }
        }
        return $ids;
    }

    /** @return array<string,mixed>|null */
    public static function service(string $id): ?array
    {
        foreach (self::DATA as $cat) {
            foreach ($cat['items'] as $item) {
                if ($item['id'] === $id) {
                    return $item + ['category' => $cat['id'], 'kind' => $cat['kind']];
                }
            }
        }
        return null;
    }
}
