<?php
declare(strict_types=1);

/**
 * Kiruvchi ma’lumotlarni tekshirish va tozalash yordamchilari.
 */

/** Matnni tozalaydi: boshidagi/oxiridagi bo‘shliq, boshqaruv belgilari; uzunlik chegarasi. */
function v_str(mixed $value, int $maxLength, bool $required = false): ?string
{
    if ($value === null || is_array($value) || is_object($value)) {
        return null;
    }
    $clean = trim((string) $value);
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean) ?? '';
    if ($clean === '') {
        return $required ? null : '';
    }
    if (mb_strlen($clean, 'UTF-8') > $maxLength) {
        return null;
    }
    return $clean;
}

function v_int(mixed $value, int $min, int $max, ?int $default = null): ?int
{
    if (is_int($value)) {
        $int = $value;
    } elseif (is_string($value) && preg_match('/^-?\d{1,12}$/', trim($value))) {
        $int = (int) trim($value);
    } elseif (is_float($value) && floor($value) === $value) {
        $int = (int) $value;
    } else {
        return $default;
    }
    return ($int < $min || $int > $max) ? $default : $int;
}

function v_bool(mixed $value): bool
{
    return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
}

/** O‘zbekiston telefoni: +998XXXXXXXXX formatiga keltiradi. Noto‘g‘ri bo‘lsa null. */
function v_phone(mixed $raw): ?string
{
    if (!is_string($raw) && !is_int($raw)) {
        return null;
    }
    $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';
    if (strlen($digits) === 9) {
        $digits = '998' . $digits;
    } elseif (strlen($digits) === 12 && str_starts_with($digits, '998')) {
        // ok
    } else {
        return null;
    }
    return '+' . $digits;
}

function v_time(mixed $value): ?string
{
    return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : null;
}

/** Oddiy slug (lotin harflar, raqam, tire). Kirill/o‘zbek belgilari lotinga o‘tkazilmaydi — bo‘sh bo‘lsa unikal qiymat. */
function v_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    return $slug !== '' ? substr($slug, 0, 120) : 'cat-' . bin2hex(random_bytes(3));
}

/** ISO hafta kuni (1=Dushanba ... 7=Yakshanba) ro‘yxatini CSV dan massivga. */
function v_weekdays_csv(mixed $value): array
{
    $days = [];
    foreach (explode(',', (string) $value) as $part) {
        $n = (int) trim($part);
        if ($n >= 1 && $n <= 7 && !in_array($n, $days, true)) {
            $days[] = $n;
        }
    }
    sort($days);
    return $days;
}
