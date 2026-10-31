// 推理手帳のプログラムの流れ解説 docs/flow.html を、docs/flow-src/NN-*.html（章ごとの断片）から作る。手本は Baker Street English の同じ道具。
//   node tools/docs-html/flow.mjs          作る（断片がまだ1つもなければ、何も作らずに知らせるだけ）
//   node tools/docs-html/flow.mjs --hash   「元の断片・画像の指紋」と「住所の表の指紋」を出す（bin/check が作り直し忘れを見つけるのに使う）
// 住所の表は src/routes/web.php（読み込むだけ）と src/routes/web/*.php（機能ごとの中身）の全部。
// 外のファイルを読まない自己完結の HTML：CSS と JavaScript は中に書き、画像（data-img="ファイル名"）は docs/images から埋め込む。
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const SRC = path.join(ROOT, 'docs/flow-src');
const IMAGES = path.join(ROOT, 'docs/images');
const ROUTES = path.join(ROOT, 'src/routes');
const OUT = path.join(ROOT, 'docs/flow.html');

/** フォルダの中のファイル名の一覧（フォルダがまだなければ空） */
const list = dir => (fs.existsSync(dir) ? fs.readdirSync(dir) : []);
const parts = list(SRC).filter(f => /^\d\d-.*\.html$/.test(f)).sort();

// 住所の表：web.php と、機能ごとのファイル routes/web/*.php（プロジェクト直下からのパス）
const webFiles = () => [
  ...(fs.existsSync(path.join(ROUTES, 'web.php')) ? ['src/routes/web.php'] : []),
  ...list(path.join(ROUTES, 'web')).filter(f => f.endsWith('.php')).sort().map(f => `src/routes/web/${f}`),
];

// 住所の表の指紋は別に持つ：住所の表を変えたのに解説を直していないことを bin/check が見つけるため
// コメントと空行は除いて数える（コメントを足しただけなら、住所の表は変わっていない）。ファイル名も入れる（ファイルを足す・消すと変わる）
const webHash = () => {
  const h = createHash('sha256');
  for (const f of webFiles()) {
    h.update(f).update(fs.readFileSync(path.join(ROOT, f), 'utf8')
      .replace(/\/\*[\s\S]*?\*\//g, '')
      .split('\n').map(l => l.replace(/\s+\/\/\s.*$/, '').replace(/^\s*\/\/.*$/, '').trim()).filter(Boolean).join('\n'));
  }
  return h.digest('hex').slice(0, 16);
};
const fingerprint = () => {
  const h = createHash('sha256');
  for (const f of parts) h.update(f).update(fs.readFileSync(path.join(SRC, f)));
  for (const f of list(IMAGES).sort()) h.update(f).update(fs.readFileSync(path.join(IMAGES, f)));
  h.update(fs.readFileSync(fileURLToPath(import.meta.url)));
  return h.digest('hex').slice(0, 16);
};
if (process.argv.includes('--hash')) {
  console.log(`${fingerprint()} ${webHash()}`);
  process.exit(0);
}

// 断片がまだ1つもなければ、空の HTML は作らない（書き始めたら作る）
if (!parts.length) {
  console.log('docs/flow-src/NN-*.html の断片がまだありません。断片を書いてから node tools/docs-html/flow.mjs を動かしてください（docs/flow.html は作りませんでした）');
  process.exit(0);
}

// 画像を埋め込む（PNG・JPG は横幅 1400px までの WebP に縮める）
const cacheDir = path.join(os.tmpdir(), 'suiri-docs-html');
fs.mkdirSync(cacheDir, { recursive: true });
const cache = new Map();
const imageUri = name => {
  const abs = path.join(IMAGES, path.basename(name));
  if (!fs.existsSync(abs)) throw new Error(`画像がありません：docs/images/${name}`);
  if (cache.has(abs)) return cache.get(abs);
  let uri;
  if (abs.endsWith('.svg')) {
    uri = 'data:image/svg+xml;base64,' + fs.readFileSync(abs).toString('base64');
  } else {
    const key = createHash('sha1').update(fs.readFileSync(abs)).digest('hex');
    const webp = path.join(cacheDir, `flow-${key}.webp`);
    if (!fs.existsSync(webp)) execFileSync('ffmpeg', ['-loglevel', 'error', '-y', '-i', abs, '-vf', "scale='min(1400,iw)':-2", '-c:v', 'libwebp', '-q:v', '80', webp]);
    uri = 'data:image/webp;base64,' + fs.readFileSync(webp).toString('base64');
  }
  cache.set(abs, uri);
  return uri;
};

const strip = s => s.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
const chapters = parts.map(f => {
  const html = fs.readFileSync(path.join(SRC, f), 'utf8');
  const id = html.match(/<section class="story" id="([^"]+)"/)?.[1];
  if (!id) throw new Error(`${f}：<section class="story" id="…"> がありません`);
  const h2 = html.match(/<h2>([\s\S]*?)<\/h2>/)?.[1] ?? f;
  const no = h2.match(/<span class="no">([^<]*)<\/span>/)?.[1] ?? '';
  const title = strip(h2.replace(/<span class="no">[^<]*<\/span>/, ''));
  const story = strip(html.match(/<p class="userstory">([\s\S]*?)<\/p>/)?.[1] ?? '');
  // 小見出し（h3）に id がなければ付けて、目次から飛べるようにする
  let n = 0;
  const body = html
    .replace(/<h3(?![^>]*\bid=)([^>]*)>/g, (m, rest) => `<h3 id="${id}-h${++n}"${rest}>`)
    .replace(/<img([^>]*?)\sdata-img="([^"]+)"/g, (m, before, name) => `<img${before} src="${imageUri(name)}"`);
  const subs = [...body.matchAll(/<h3 id="([^"]+)"[^>]*>([\s\S]*?)<\/h3>/g)].map(([, hid, t]) => [hid, strip(t)]);
  const steps = (body.match(/<li class="step">/g) || []).length;
  return { id, no, title, story, body, subs, steps };
});

const toc = chapters.map(c => `<li><a class="toc-chapter" href="#${c.id}"><span>${c.no}</span>${c.title}</a>${c.subs.length ? `<ul>${c.subs.map(([hid, t]) => `<li><a href="#${hid}">${t}</a></li>`).join('')}</ul>` : ''}</li>`).join('\n');
const overview = chapters.map(c => `<tr><td class="ov-no">${c.no}</td><td><a href="#${c.id}">${c.title}</a><div class="ov-story">${c.story}</div></td><td class="ov-steps">${c.steps}</td></tr>`).join('\n');
const totalSteps = chapters.reduce((a, c) => a + c.steps, 0);

const html = `<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="generator" content="tools/docs-html/flow.mjs">
<meta name="source-fingerprint" content="${fingerprint()}">
<meta name="web-php-fingerprint" content="${webHash()}">
<title>プログラムの流れ解説 | 推理手帳</title>
<style>
:root {
  --bg: #f1ece1; --paper: #fbf8f1; --ink: #1c2540; --sub: #5f6475; --line: #ddd5c4;
  --navy: #1c2a4f; --gold: #9a6b2f; --red: #8e1c2b; --code: #ece5d6; --codeink: #1f2436; --link: #22427e;
  --browser: #1f6f8b; --server: #7a4a12; --db: #3c6e3c; --js: #6a3d8f; --mw: #8a2432;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) { --bg: #0a0f1f; --paper: #121a2e; --ink: #ece4d2; --sub: #a9a08c; --line: #2a3350; --code: #0d1426; --codeink: #e6dcc6; --link: #9cb8f0; --gold: #d4ab55;
    --browser: #5fb3d0; --server: #d8a35c; --db: #7fc07f; --js: #b48ae0; --mw: #e07a88; }
}
:root[data-theme="dark"] { --bg: #0a0f1f; --paper: #121a2e; --ink: #ece4d2; --sub: #a9a08c; --line: #2a3350; --code: #0d1426; --codeink: #e6dcc6; --link: #9cb8f0; --gold: #d4ab55;
  --browser: #5fb3d0; --server: #d8a35c; --db: #7fc07f; --js: #b48ae0; --mw: #e07a88; }
* { box-sizing: border-box; }
html { scroll-behavior: smooth; scroll-padding-top: 70px; }
body { margin: 0; background: var(--bg); color: var(--ink); font: 16px/1.85 "Hiragino Sans", "Yu Gothic UI", "Noto Sans JP", system-ui, sans-serif; }
header.top { position: sticky; top: 0; z-index: 10; height: 56px; display: flex; align-items: center; gap: 12px; padding: 0 16px; background: var(--navy); color: #fff; border-bottom: 3px solid var(--gold); }
header.top b { font-family: Georgia, "Hiragino Mincho ProN", "Yu Mincho", serif; font-size: 1.05rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
header.top button { flex-shrink: 0; white-space: nowrap; border: 1px solid #c9a24c; background: transparent; color: #fff; border-radius: 999px; padding: 4px 12px; font: inherit; font-size: .85rem; cursor: pointer; }
header.top #theme { margin-left: auto; }
.layout { display: grid; grid-template-columns: 300px minmax(0, 1fr); max-width: 1320px; margin: 0 auto; }
nav.toc { position: sticky; top: 56px; height: calc(100vh - 56px); overflow-y: auto; padding: 18px 16px 40px; border-right: 1px solid var(--line); font-size: .88rem; line-height: 1.5; }
nav.toc ul { list-style: none; margin: 0; padding: 0; }
nav.toc > ul > li { margin-bottom: 10px; }
nav.toc ul ul { margin: 4px 0 0 10px; padding-left: 10px; border-left: 1px solid var(--line); }
nav.toc ul ul a { display: block; padding: 2px 0; color: var(--sub); }
nav.toc a { text-decoration: none; color: var(--ink); }
nav.toc a:hover, nav.toc a.current { color: var(--red); }
.toc-chapter { display: flex; gap: 8px; font-weight: 700; }
.toc-chapter span { flex-shrink: 0; min-width: 1.6em; color: var(--gold); font-family: "Courier New", monospace; }
main { min-width: 0; padding: 24px 40px 120px; }
.intro, .story { background: var(--paper); border: 1px solid var(--line); border-radius: 10px; padding: 28px 40px 36px; margin-bottom: 32px; min-width: 0; }
h1 { font-family: Georgia, "Hiragino Mincho ProN", "Yu Mincho", serif; font-size: 2rem; line-height: 1.35; margin: .1em 0 .5em; }
h2 { font-family: Georgia, "Hiragino Mincho ProN", "Yu Mincho", serif; font-size: 1.6rem; line-height: 1.4; margin: 0 0 .6em; display: flex; gap: .5em; align-items: baseline; }
h2 .no { display: inline-grid; place-items: center; min-width: 1.8em; height: 1.8em; border-radius: 50%; background: var(--navy); color: #fff; font-size: .8em; flex-shrink: 0; }
h3 { font-family: Georgia, "Hiragino Mincho ProN", "Yu Mincho", serif; font-size: 1.3rem; margin: 2em 0 .6em; padding-left: .6em; border-left: 4px solid var(--red); }
h4 { font-size: 1.08rem; margin: 0 0 .4em; line-height: 1.5; }
a { color: var(--link); }
p, li, td { overflow-wrap: anywhere; }
.userstory { margin: 0 0 .8em; padding: 12px 16px; border-radius: 8px; background: color-mix(in srgb, var(--gold) 14%, transparent); border-left: 4px solid var(--gold); }
.userstory::before { content: "ユーザーストーリー"; display: block; font-size: .75rem; font-weight: 700; color: var(--gold); letter-spacing: .1em; }
.summary { margin: 0 0 1.4em; padding: 10px 14px; border: 1px dashed var(--line); border-radius: 8px; font-size: .95rem; }
ol.steps { list-style: none; counter-reset: step; margin: 0; padding: 0; }
li.step { counter-increment: step; position: relative; margin: 0 0 22px; padding: 16px 18px 14px 58px; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); }
li.step::before { content: counter(step); position: absolute; left: 14px; top: 14px; width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: var(--paper); border: 2px solid var(--gold); font-weight: 700; font-family: "Courier New", monospace; }
.who { display: inline-block; margin-bottom: 4px; padding: 1px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; color: #fff; background: var(--server); }
.who.browser { background: var(--browser); } .who.server { background: var(--server); } .who.db { background: var(--db); } .who.js { background: var(--js); } .who.mw { background: var(--mw); }
:root[data-theme="dark"] .who, :root:not([data-theme="light"]) .who { color: #0a0f1f; }
@media (prefers-color-scheme: light) { :root:not([data-theme="dark"]) .who { color: #fff; } }
:root[data-theme="light"] .who { color: #fff; }
.code { margin: .8em 0; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; background: var(--code); }
.code .file { padding: 5px 12px; font: 700 .78rem/1.4 "SFMono-Regular", Consolas, "Courier New", monospace; color: var(--sub); border-bottom: 1px solid var(--line); background: color-mix(in srgb, var(--navy) 6%, var(--code)); overflow-wrap: anywhere; }
pre { margin: 0; padding: 12px 14px; overflow-x: auto; font-size: .84rem; line-height: 1.6; }
pre code { font: 1em/1.6 "SFMono-Regular", Consolas, "Courier New", monospace; color: var(--codeink); background: none; padding: 0; }
code { background: var(--code); border-radius: 4px; padding: .05em .35em; font: .88em/1.5 "SFMono-Regular", Consolas, "Courier New", monospace; overflow-wrap: anywhere; }
.data, .analogy, .point, .recap { margin: .8em 0; padding: 10px 14px; border-radius: 8px; }
.data { background: color-mix(in srgb, var(--db) 9%, transparent); border-left: 4px solid var(--db); }
.analogy { background: color-mix(in srgb, var(--browser) 9%, transparent); border-left: 4px solid var(--browser); }
.point { background: color-mix(in srgb, var(--red) 8%, transparent); border-left: 4px solid var(--red); }
.recap { background: color-mix(in srgb, var(--gold) 12%, transparent); border: 1px solid var(--gold); margin-top: 1.6em; }
.data > b, .analogy > b, .point > b, .recap > b { display: block; font-size: .85rem; margin-bottom: .2em; }
.data ul, .recap ol { margin: .2em 0 0; padding-left: 1.3em; }
dfn { font-style: normal; font-weight: 700; border-bottom: 2px solid var(--gold); }
figure.shot { margin: 1em 0 .4em; text-align: center; }
figure.shot img { max-width: min(100%, 720px); height: auto; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
figcaption { font-size: .82rem; color: var(--sub); }
table { border-collapse: collapse; width: 100%; font-size: .93rem; }
th, td { border: 1px solid var(--line); padding: 8px 10px; text-align: left; vertical-align: top; }
.table-wrap { overflow-x: auto; }
.ov-no { width: 3em; text-align: center; font-weight: 700; color: var(--gold); font-family: "Courier New", monospace; }
.ov-story { font-size: .85rem; color: var(--sub); }
.ov-steps { width: 4.5em; text-align: right; color: var(--sub); }
.legend { display: flex; flex-wrap: wrap; gap: 8px; margin: .6em 0 1em; }
#totop { position: fixed; right: 16px; bottom: 16px; width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--gold); background: var(--paper); color: var(--ink); font-size: 1.2rem; cursor: pointer; }
@media (min-width: 901px) {
  body.toc-closed .layout { grid-template-columns: minmax(0, 1fr); max-width: 1080px; }
  body.toc-closed nav.toc { display: none; }
}
@media (max-width: 900px) {
  .layout { grid-template-columns: minmax(0, 1fr); }
  nav.toc { position: fixed; top: 56px; left: 0; width: min(320px, 85vw); z-index: 9; background: var(--bg); transform: translateX(-100%); transition: transform .2s; }
  body.toc-open nav.toc { transform: none; box-shadow: 0 0 0 100vmax rgb(0 0 0 / .35); }
  main { padding: 16px 12px 100px; }
  .intro, .story { padding: 20px 14px 28px; }
  li.step { padding: 50px 12px 12px; }
  li.step::before { top: 12px; left: 12px; }
  li.step > .who { position: absolute; top: 18px; left: 54px; }
  h1 { font-size: 1.55rem; } h2 { font-size: 1.3rem; }
}
@media print { header.top, nav.toc, #totop { display: none; } .layout { display: block; } .story { break-before: page; border: 0; } }
</style>
</head>
<body>
<header class="top">
  <button id="menu" type="button" aria-label="目次を閉じる・開く" aria-expanded="true">☰ 目次</button>
  <b>プログラムの流れ解説</b>
  <button id="theme" type="button" title="明るい表示・暗い表示を切り替える">表示切替</button>
</header>
<div class="layout">
  <nav class="toc" aria-label="目次"><ul>
<li><a class="toc-chapter" href="#intro"><span>●</span>この資料の使い方</a></li>
${toc}
  </ul></nav>
  <main>
    <section class="intro" id="intro">
      <h1>推理手帳<br>プログラムの流れ解説</h1>
      <p>利用者が画面で何かをしたとき、プログラムの中で<b>どのファイルの、どの部分が、どの順番で動くのか</b>を、利用者の行動（ユーザーストーリー）ごとに1歩ずつ追う資料です。出発点はいつも住所の表です（<code>src/routes/web.php</code> は機能ごとのファイルを読み込むだけで、中身は <code>src/routes/web/{機能}.php</code>）。そこから見張り役（ミドルウェア）・受付（コントローラー）・形の検証（FormRequest）・権限（Policy）・用事（Action）・外とのやりとり（Service）・データベースの表・画面（Blade と部品）・画面の中のプログラム（JavaScript）へと、本物のコードを見せながらたどります。</p>
      <p>はじめての人は最初の章から読んでください。最初の章に、ほかの章すべての土台（お願いと返事・見張り役・ログインの見分け方）を置きます。そのあとは、知りたい行動の章から読んで大丈夫です。</p>
      <div class="legend"><span class="who browser">ブラウザ</span><span class="who mw">見張り役（ミドルウェア）</span><span class="who server">サーバー（Laravel）</span><span class="who db">データベース</span><span class="who js">画面のプログラム（JavaScript）</span></div>
      <p>各ステップの左上の色の札は「どこで起きていることか」を表します。コードの欄の上には、そのコードがあるファイルの場所を書いています（<code>src/</code> がアプリ本体、<code>src/vendor/laravel/…</code> は Laravel 本体の部品）。コードは本物のファイルからそのまま写したもので、省いた所は <code>// …（省略）…</code> と書いています。</p>
      <div class="table-wrap"><table>
        <thead><tr><th>章</th><th>行動（ユーザーストーリー）</th><th>ステップ</th></tr></thead>
        <tbody>${overview}</tbody>
      </table></div>
      <p style="font-size:.85rem;color:var(--sub)">全 ${chapters.length} 章・${totalSteps} ステップ。この HTML は docs/flow-src の断片から tools/docs-html/flow.mjs で作っています（直すときは断片を直して作り直す）。</p>
    </section>
${chapters.map(c => c.body).join('\n')}
  </main>
</div>
<button id="totop" type="button" aria-label="いちばん上へ" title="いちばん上へ">↑</button>
<script>
(() => {
  const body = document.body;
  const root = document.documentElement;
  // 目次を閉じる・開くと本文の幅が変わり、文の折り返しが変わってページの高さも変わる。
  // そのままだと読んでいた場所がずれる（下の方へ飛ぶ）ので、画面のいちばん上にあった要素を覚えておき、切り替えたあとも同じ高さに戻す
  const keepPlace = change => {
    const top = 70;
    const el = [...document.querySelectorAll('main h1, main h2, main h3, main h4, main p, main li, main figure, main pre, main table')]
      .find(e => e.getBoundingClientRect().bottom > top);
    const before = el ? el.getBoundingClientRect().top : 0;
    change();
    if (el) window.scrollTo({ top: scrollY + el.getBoundingClientRect().top - before, behavior: 'instant' });
  };
  const menu = document.getElementById('menu');
  const wide = () => matchMedia('(min-width: 901px)').matches;
  const sync = () => menu.setAttribute('aria-expanded', String(wide() ? !body.classList.contains('toc-closed') : body.classList.contains('toc-open')));
  try { if (localStorage.getItem('suiri-flow-toc') === 'closed') body.classList.add('toc-closed'); const t = localStorage.getItem('suiri-flow-theme'); if (t) root.dataset.theme = t; } catch {}
  menu.addEventListener('click', () => {
    if (wide()) { keepPlace(() => body.classList.toggle('toc-closed')); try { localStorage.setItem('suiri-flow-toc', body.classList.contains('toc-closed') ? 'closed' : 'open'); } catch {} }
    else body.classList.toggle('toc-open');
    sync();
  });
  document.querySelectorAll('nav.toc a').forEach(a => a.addEventListener('click', () => { body.classList.remove('toc-open'); sync(); }));
  sync();
  document.getElementById('theme').addEventListener('click', () => {
    const dark = root.dataset.theme ? root.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    root.dataset.theme = dark ? 'light' : 'dark';
    try { localStorage.setItem('suiri-flow-theme', root.dataset.theme); } catch {}
  });
  document.getElementById('totop').addEventListener('click', () => scrollTo({ top: 0 }));
  const links = new Map([...document.querySelectorAll('nav.toc a')].map(a => [a.getAttribute('href').slice(1), a]));
  const obs = new IntersectionObserver(entries => entries.forEach(en => {
    if (!en.isIntersecting) return;
    links.forEach(a => a.classList.remove('current'));
    const a = links.get(en.target.id);
    if (a) {
      a.classList.add('current');
      // 目次の欄の中だけを動かす（scrollIntoView はページ全体も動かしてしまい、目次から飛んだ途中の移動を止めてしまう）
      const nav = a.closest('nav.toc'), r = a.getBoundingClientRect(), nr = nav.getBoundingClientRect();
      if (r.top < nr.top || r.bottom > nr.bottom) nav.scrollTop += r.top - nr.top - nr.height / 3;
    }
  }), { rootMargin: '-60px 0px -75% 0px' });
  document.querySelectorAll('section.story, section.intro, h3[id]').forEach(h => obs.observe(h));
  addEventListener('load', () => { if (location.hash) document.getElementById(decodeURIComponent(location.hash.slice(1)))?.scrollIntoView(); });
})();
</script>
</body>
</html>
`;

fs.writeFileSync(OUT, html);
console.log(`docs/flow.html を作りました（${(fs.statSync(OUT).size / 1024 / 1024).toFixed(1)} MB・${chapters.length} 章・${totalSteps} ステップ・画像 ${cache.size} 枚）`);
