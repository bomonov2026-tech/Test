<?php
declare(strict_types=1);

/** HTML qobig'i. Butun sahifa shu yerda chiqadi. */
final class View
{
    /** @param array<string,mixed> $boot */
    public static function shell(array $boot): void
    {
        $json = json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $v    = self::assetVersion();
        header('Content-Type: text/html; charset=utf-8');
        ?><!doctype html>
<html lang="uz" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#E7F0FD">
<title>TartibOn</title>
<link rel="preload" href="assets/app.css?v=<?= $v ?>" as="style">
<link rel="stylesheet" href="assets/app.css?v=<?= $v ?>">
<!-- Shrift sahifani to'sib qo'ymasligi uchun keyin yuklanadi -->
<link rel="stylesheet" media="print" onload="this.media='all';this.onload=null"
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@800;900&amp;display=swap">
<!-- SDK yuklanmasa ham ilova ishlaydi (app.js ichida zaxira obyekt bor) -->
<script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body>

<header class="hdr">
  <button type="button" class="hdr__btn" data-close-app aria-label="Ilovani yopish">
    <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
  </button>
  <div class="hdr__brand"><b>TartibOn</b><span>mini-ilova</span></div>
  <span class="hdr__btn hdr__btn--end" aria-hidden="true">
    <svg viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 4.5-3.2 8.3-8 9-4.8-.7-8-4.5-8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
  </span>
</header>

<main class="wrap" id="view"><!-- sahifalar app.js tomonidan chiziladi --></main>

<nav class="nav" id="nav" aria-label="Asosiy navigatsiya">
  <button type="button" data-tab="home">
    <svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg><span>Asosiy</span></button>
  <button type="button" data-tab="tools">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.7"/><rect x="14" y="3" width="7" height="7" rx="1.7"/><rect x="3" y="14" width="7" height="7" rx="1.7"/><rect x="14" y="14" width="7" height="7" rx="1.7"/></svg><span>Xizmatlar</span></button>
  <button type="button" data-tab="bal">
    <svg viewBox="0 0 24 24"><path d="M4 7V5a2 2 0 0 1 2-2h11v4"/><rect x="3" y="7" width="18" height="14" rx="3"/><path d="M21 12h-6a2 2 0 0 0 0 4h6M16 14h.01"/></svg><span>Balans</span></button>
  <button type="button" data-tab="me">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 6 0 0 1 16 0v2z"/></svg><span>Profil</span></button>
</nav>

<script>window.TO_BOOT = <?= $json ?>;</script>
<script src="assets/icons.js?v=<?= $v ?>"></script>
<script src="assets/app.js?v=<?= $v ?>"></script>
</body>
</html>
<?php
    }

    /** Fayl o'zgarsa brauzer keshi yangilansin. */
    private static function assetVersion(): string
    {
        $js = __DIR__ . '/../public/assets/app.js';
        return is_file($js) ? (string)filemtime($js) : '1';
    }

    public static function error(string $title, string $text): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $x = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<style>body{margin:0;display:flex;flex-direction:column;align-items:center;justify-content:center;'
           . 'min-height:100vh;gap:8px;padding:24px;text-align:center;background:#F2F3F7;color:#10131B;'
           . "font:600 15px/1.45 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif}"
           . 'b{font-size:19px}p{margin:0;max-width:320px;color:#77758C;font-weight:500}</style>'
           . '<b>' . $t . '</b><p>' . $x . '</p>';
    }
}
