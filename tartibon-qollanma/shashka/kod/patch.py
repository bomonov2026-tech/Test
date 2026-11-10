import io, os, re, sys
B = os.path.dirname(os.path.abspath(__file__))
SRC = sys.argv[1]; DST = sys.argv[2]
s = open(SRC, encoding='utf-8').read()
def rd(n): return open(os.path.join(B, n), encoding='utf-8').read()
def once(anchor, ins, before=True, label=''):
    global s
    n = s.count(anchor)
    assert n == 1, 'ANCHOR %s: %d marta topildi' % (label or anchor[:40], n)
    s = s.replace(anchor, (ins + anchor) if before else (anchor + ins))

assert 'sh_replay' not in s, 'SHASHKA allaqachon qo\'shilgan'

# 1) migratsiya
once("    if (setting('mig_ts1', '') !== '1') {", rd('part_mig.txt'), True, 'mig')

# 2) PHP funksiyalari + simulyatsiya
sim = rd('sh_sim.php')
sim = sim[sim.index('/* ---'):]                      # <?php sarlavhasini olib tashlaymiz
php = rd('part_php.txt') + sim + "\n"
once("/* ---------- Telegram Premium va Stars sotish ---------- */", php, True, 'php-funksiyalar')

# 3) o'yin modulini beruvchi funksiya + API
js = rd('sh-game.js')
assert '\r' not in js and '\nSHJS;' not in js
shjs = ("function sh_js() {\n    return <<<'SHJS'\n" + js + "\nSHJS;\n}\n"
  "function sh_js_out() {\n"
  "    $js = sh_js(); $et = '\"' . md5($js) . '\"';\n"
  "    header('Content-Type: text/javascript; charset=utf-8');\n"
  "    header('Cache-Control: public, max-age=31536000, immutable');\n"
  "    header('X-Content-Type-Options: nosniff');\n"
  "    header('ETag: ' . $et);\n"
  "    $m = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim((string)$_SERVER['HTTP_IF_NONE_MATCH']) : '';\n"
  "    if ($m === $et) { http_response_code(304); exit; }\n"
  "    $ae = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? (string)$_SERVER['HTTP_ACCEPT_ENCODING'] : '';\n"
  "    header('Vary: Accept-Encoding');\n"
  "    if (strpos($ae, 'gzip') !== false && function_exists('gzencode')) { header('Content-Encoding: gzip'); echo gzencode($js, 6); exit; }\n"
  "    echo $js; exit;\n}\n")
once("/* ---------- Telegram Premium va Stars sotish ---------- */", shjs, True, 'sh_js')

once("    if ($a === 'ts_get') {", rd('part_api.txt'), True, 'api')

# 4) ?shjs= — HTML chiqishidan oldin
once("if (isset($_GET['api'])) {",
     "if (isset($_GET['shjs'])) { sh_js_out(); }\n", True, 'shjs-route')

# 5) sahifa + uslublar
once('<div id="p-ts" class="pg">', rd('part_page.txt') + rd('part_css.txt').replace('\n', ''), True, 'sahifa')

# 6) bosh sahifadagi katak
once("""<button class="tq-s" onclick="go('gm')">""", rd('part_tile.txt'), True, 'katak')

# 7) JS blok
once("function go(p,w){if(p!=='ov')ovLeave();", rd('part_js.txt'), True, 'js-blok')

# 8) router
once("if(p==='gm')gml();else if(window.GM&&GM.g)GM.g.stop();",
     "if(p==='sh')shl();else shLeave();", False, 'router')

# 9) chuqur havola: ?startapp=s_sh
old = "var SP_PAGES='"
i = s.index(old); j = s.index("'", i + len(old))
pages = s[i + len(old):j]
assert ' sh ' not in (' ' + pages + ' '), 'sh allaqachon bor'
s = s[:j] + ' sh' + s[j:]

open(DST, 'w', encoding='utf-8').write(s)
print('yozildi:', DST, len(s), 'bayt (avval %d, +%d)' % (os.path.getsize(SRC), len(s.encode()) - os.path.getsize(SRC)))
