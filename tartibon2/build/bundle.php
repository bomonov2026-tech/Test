<?php
/**
 * src/ va assets/ ni bitta `dist/index.php` ga yig'adi.
 * Ishlatish:  php build/bundle.php
 * Natija hostingga bitta fayl bo'lib tashlanadi (config.php yonida turadi).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$out  = $root . '/dist/index.php';

$strip = static function (string $file): string {
    $s = (string)file_get_contents($file);
    $s = preg_replace('~^<\?php\s*~', '', $s);          // ochuvchi teg
    $s = preg_replace('~^declare\(strict_types=1\);\s*~m', '', $s);
    return trim($s);
};

$classes = ['Config', 'Db', 'Telegram', 'Catalog', 'App', 'Api', 'View'];
$parts   = [];
foreach ($classes as $c) {
    $parts[] = $strip($root . '/src/' . $c . '.php');
}

// qobiqdagi tashqi fayllar o'rniga ichkariga joylanadi
$view = array_pop($parts);
$asset = static fn(string $n): string => (string)file_get_contents($root . '/public/assets/' . $n);
$view = str_replace(
    [
        '<link rel="preload" href="assets/app.css?v=<?= $v ?>" as="style">' . "\n",
        '<link rel="stylesheet" href="assets/app.css?v=<?= $v ?>">',
        '<script src="assets/icons.js?v=<?= $v ?>"></script>',
        '<script src="assets/app.js?v=<?= $v ?>"></script>',
    ],
    [
        '',
        '<style>' . $asset('app.css') . '</style>',
        '<script>' . $asset('icons.js') . '</script>',
        '<script>' . $asset('app.js') . '</script>',
    ],
    $view
);
$parts[] = $view;

$front = $strip($root . '/public/index.php');
// bitta faylda autoload kerak emas
$front = preg_replace('~spl_autoload_register\(.*?\n\}\);\n~s', '', $front);

$code = "<?php\n"
      . "declare(strict_types=1);\n"
      . "/* TartibOn — build/bundle.php tomonidan yig'ilgan. Tahrirlamang: src/ ni tahrirlang. */\n"
      . "/* Yig'ilgan vaqt: " . date('Y-m-d H:i') . " */\n\n"
      . implode("\n\n", $parts) . "\n\n"
      . $front . "\n";

if (!is_dir(dirname($out))) {
    mkdir(dirname($out), 0775, true);
}
file_put_contents($out, $code);
printf("%s — %.0f KB\n", $out, strlen($code) / 1024);
