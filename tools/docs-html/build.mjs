// 推理手帳の docs（解体新書と設計書）を、1つで開ける HTML ファイル docs/guide.html にまとめる。手本は Baker Street English の同じ道具。
//   node tools/docs-html/build.mjs            作る（README・docs の .md か画像を変えたら実行する）
//   node tools/docs-html/build.mjs --hash     元の文書と画像から作った指紋だけを出す（bin/check が「作り直し忘れ」を見つけるのに使う）
// 外のファイルを読まない自己完結の HTML：画像は WebP（ffmpeg で縮める）・SVG を中に埋め込み、CSS も中に書く。
// 章どうしのリンク（words.md#…）は、HTML の中の見出しへのリンクに付け替える。
// まだない文書（docs/guide が空・components.md がまだ、など）は飛ばすので、書きかけの段階でも動く。
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Marked } from 'marked';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const DOCS = path.join(ROOT, 'docs');
const OUT = path.join(DOCS, 'guide.html');

/** フォルダの中のファイル名の一覧（フォルダがまだなければ空。git は空のフォルダを持たないので、クローン直後はないこともある） */
const list = dir => (fs.existsSync(dir) ? fs.readdirSync(dir) : []);

// 並べる順番：はじめに（README）→ 目次 → 解体新書 01〜 → 設計書（付録）→ 調査。パスはプロジェクト直下から
const CHAPTERS = [
  ['README.md', '00', 'はじめに'],
  ['docs/README.md', '0', '目次'],
  ...list(path.join(DOCS, 'guide')).filter(f => /^\d\d-.*\.md$/.test(f)).sort().map(f => [`docs/guide/${f}`, f.slice(0, 2), null]),
  ['docs/design/requirements.md', 'A', '付録A'],
  ['docs/design/basic-design.md', 'B', '付録B'],
  ['docs/design/data-model.md', 'C', '付録C'],
  ['docs/design/words.md', 'D', '付録D'],
  ['docs/design/components.md', 'E', '付録E'],
  ['docs/research/README.md', 'F', '付録F'],
].filter(([file]) => fs.existsSync(path.join(ROOT, file)));

const files = CHAPTERS.map(([file]) => file);
const imageFiles = list(path.join(DOCS, 'images')).filter(f => /\.(png|jpe?g|webp|svg)$/i.test(f)).sort();

/** 元の文書と画像（と、この作り方そのもの）の指紋。どれかが変われば変わる */
const fingerprint = () => {
  const h = createHash('sha256');
  for (const f of files) h.update(f).update(fs.readFileSync(path.join(ROOT, f)));
  for (const f of imageFiles) h.update(f).update(fs.readFileSync(path.join(DOCS, 'images', f)));
  h.update(fs.readFileSync(fileURLToPath(import.meta.url)));
  return h.digest('hex').slice(0, 16);
};

if (process.argv.includes('--hash')) {
  console.log(fingerprint());
  process.exit(0);
}

// GitHub と同じ決まりで見出しの id を作る（英小文字・記号を消す・空白は -）
const slug = text => text.toLowerCase().trim()
  .replace(/<[^>]+>/g, '')
  .replace(/[ -⁯⸀-⹿\\'!"#$%&()*+,./:;<=>?@[\]^`{|}~（）「」『』【】、。・：；？！]/g, '')
  .replace(/\s/g, '-');

// 画像を埋め込み用の data: URI に（PNG・JPG は横幅 1600px までの WebP に縮める。作った物は一時フォルダに取っておく）
const cacheDir = path.join(os.tmpdir(), 'suiri-docs-html');
fs.mkdirSync(cacheDir, { recursive: true });
const dataUri = new Map();
const imageUri = rel => {
  // docs の中の画像のほか、アプリの絵（src/public/images）を指していることもある（rel はプロジェクト直下からのパス）
  let abs = path.join(ROOT, rel);
  if (!fs.existsSync(abs)) abs = path.join(DOCS, 'images', path.basename(rel));
  if (!fs.existsSync(abs)) return null;
  if (dataUri.has(abs)) return dataUri.get(abs);
  let uri;
  if (abs.endsWith('.svg')) {
    uri = 'data:image/svg+xml;base64,' + fs.readFileSync(abs).toString('base64');
  } else {
    const key = createHash('sha1').update(fs.readFileSync(abs)).digest('hex');
    const webp = path.join(cacheDir, key + '.webp');
    if (!fs.existsSync(webp)) {
      execFileSync('ffmpeg', ['-loglevel', 'error', '-y', '-i', abs, '-vf', "scale='min(1600,iw)':-2", '-c:v', 'libwebp', '-q:v', '80', webp]);
    }
    uri = 'data:image/webp;base64,' + fs.readFileSync(webp).toString('base64');
  }
  dataUri.set(abs, uri);
  return uri;
};

// 文書の中のリンクを付け替える：画像 → 埋め込み、別の章（.md）→ その章の見出し、外のサイト → 新しいタブ
// （fromFile も行き先も、プロジェクト直下からのパスで比べる）
const chapterOf = new Map(CHAPTERS.map(([file, id]) => [file, id]));
const rewriteHref = (href, fromFile) => {
  if (/^(https?:|mailto:|#)/.test(href)) return href.startsWith('#') ? `#c${chapterOf.get(fromFile)}-${href.slice(1)}` : href;
  const [p, hash] = href.split('#');
  const target = path.normalize(path.join(path.dirname(fromFile), p));
  if (/\.(png|jpe?g|webp|svg)$/i.test(p)) return imageUri(target) ?? href;
  if (chapterOf.has(target)) return hash ? `#c${chapterOf.get(target)}-${decodeURIComponent(hash)}` : `#c${chapterOf.get(target)}`;
  return null; // 文書の外（ソースコードなど）へのリンクは、ただの文字にする
};

const toc = [];
const sections = CHAPTERS.map(([file, id, label]) => {
  const md = fs.readFileSync(path.join(ROOT, file), 'utf8');
  const marked = new Marked({ gfm: true });
  let title = null;
  const heads = [];
  marked.use({
    renderer: {
      heading({ tokens, depth, text }) {
        const inner = this.parser.parseInline(tokens);
        const hid = `c${id}-${slug(text)}`;
        if (depth === 1 && !title) title = inner.replace(/<[^>]+>/g, '');
        if (depth === 2) heads.push([hid, inner.replace(/<[^>]+>/g, '')]);
        return `<h${depth} id="${hid}"><a class="anchor" href="#${hid}" aria-hidden="true">#</a>${inner}</h${depth}>\n`;
      },
      link({ href, title: t, tokens }) {
        const inner = this.parser.parseInline(tokens);
        const to = rewriteHref(href, file);
        if (to === null) return `<span class="file-ref">${inner}</span>`;
        const ext = /^https?:/.test(to) ? ' target="_blank" rel="noopener"' : '';
        return `<a href="${to}"${t ? ` title="${t}"` : ''}${ext}>${inner}</a>`;
      },
      image({ href, text }) {
        const to = rewriteHref(href, file);
        return `<figure><img src="${to}" alt="${text}"><figcaption>${text}</figcaption></figure>`;
      },
      html({ text }) {
        // 文書の中に直接書いた <img src="images/…"> も埋め込みにする
        return text.replace(/(<img[^>]+src=")([^"]+)(")/g, (m, a, src, b) => {
          const to = rewriteHref(src, file);
          return to ? a + to + b : m;
        });
      },
      table(token) {
        return `<div class="table-wrap">${marked.Renderer.prototype.table.call(this, token)}</div>`;
      },
    },
  });
  const body = marked.parse(md);
  const name = title ?? file;
  toc.push({ id: `c${id}`, label: label ?? `${id}`, named: Boolean(label), name, heads });
  return `<section class="chapter" id="c${id}"><p class="chapter-no">${label ?? 'Chapter ' + id}</p>${body}</section>`;
});

const tocHtml = toc.map(c => `
  <li><a class="toc-chapter" href="#${c.id}">${c.named ? `<span>${c.label}</span>` : ''}${c.name}</a>
    <ul>${c.heads.map(([hid, h]) => `<li><a href="#${hid}">${h}</a></li>`).join('')}</ul></li>`).join('');

const html = `<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="generator" content="tools/docs-html/build.mjs">
<meta name="source-fingerprint" content="${fingerprint()}">
<title>推理手帳 解体新書</title>
<style>
:root {
  --bg: #f1ece1; --paper: #fbf8f1; --ink: #1c2540; --sub: #5f6475; --line: #ddd5c4;
  --navy: #1c2a4f; --gold: #9a6b2f; --red: #8e1c2b; --code: #ece5d6; --link: #22427e;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) { --bg: #0a0f1f; --paper: #121a2e; --ink: #ece4d2; --sub: #a9a08c; --line: #2a3350; --code: #1b2440; --link: #9cb8f0; --gold: #d4ab55; }
}
:root[data-theme="dark"] { --bg: #0a0f1f; --paper: #121a2e; --ink: #ece4d2; --sub: #a9a08c; --line: #2a3350; --code: #1b2440; --link: #9cb8f0; --gold: #d4ab55; }
* { box-sizing: border-box; }
html { scroll-behavior: smooth; scroll-padding-top: 72px; }
body { margin: 0; background: var(--bg); color: var(--ink); font: 16px/1.85 "Hiragino Sans", "Yu Gothic UI", "Noto Sans JP", system-ui, sans-serif; }
header.top { position: sticky; top: 0; z-index: 10; height: 56px; display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: var(--navy); color: #fff; border-bottom: 3px solid var(--gold); }
header.top span { line-height: 1.3; }
header.top b { font-family: Georgia, "Hiragino Mincho ProN", "Yu Mincho", serif; font-size: 1.1rem; letter-spacing: .02em; }
header.top small { color: #e7c873; font-family: "Courier New", monospace; letter-spacing: .2em; font-size: .62rem; }
header.top button { white-space: nowrap; flex-shrink: 0; margin-left: auto; border: 1px solid #c9a24c; background: transparent; color: #fff; border-radius: 999px; padding: 4px 12px; font: inherit; font-size: .85rem; cursor: pointer; }
.layout { display: grid; grid-template-columns: 300px minmax(0, 1fr); max-width: 1320px; margin: 0 auto; }
nav.toc { position: sticky; top: 56px; height: calc(100vh - 56px); overflow-y: auto; padding: 20px 16px 40px; border-right: 1px solid var(--line); font-size: .88rem; line-height: 1.5; }
nav.toc input { width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 8px; background: var(--paper); color: var(--ink); font: inherit; margin-bottom: 12px; }
nav.toc ul { list-style: none; margin: 0; padding: 0; }
nav.toc > ul > li { margin-bottom: 10px; }
nav.toc ul ul { margin: 4px 0 0 10px; padding-left: 10px; border-left: 1px solid var(--line); }
nav.toc ul ul a { display: block; padding: 2px 0; color: var(--sub); }
nav.toc a { text-decoration: none; color: var(--ink); }
nav.toc a:hover { color: var(--red); }
nav.toc a.current { color: var(--red); font-weight: 700; }
.toc-chapter { display: block; font-weight: 700; }
.toc-chapter span { display: inline-block; min-width: 2.6em; margin-right: 6px; color: var(--gold); font-family: "Courier New", monospace; font-size: .8rem; }
main { min-width: 0; max-width: 100vw; padding: 24px 40px 120px; }
.chapter { min-width: 0; overflow-wrap: anywhere; }
.chapter { background: var(--paper); border: 1px solid var(--line); border-radius: 10px; padding: 28px 40px 40px; margin-bottom: 32px; }
.chapter-no { margin: 0; color: var(--gold); font-family: "Courier New", monospace; letter-spacing: .25em; font-size: .75rem; text-transform: uppercase; }
h1, h2, h3, h4 { font-family: Georgia, "Hiragino Mincho ProN", "Yu Mincho", serif; line-height: 1.4; position: relative; }
h1 { font-size: 2rem; margin: .2em 0 .8em; padding-bottom: .4em; border-bottom: 2px solid var(--line); }
h2 { font-size: 1.5rem; margin: 2em 0 .7em; padding-left: .6em; border-left: 4px solid var(--red); }
h3 { font-size: 1.2rem; margin: 1.6em 0 .5em; }
h4 { font-size: 1.05rem; margin: 1.3em 0 .4em; }
.anchor { position: absolute; left: -1.1em; color: var(--line); text-decoration: none; opacity: 0; }
h1:hover .anchor, h2:hover .anchor, h3:hover .anchor { opacity: 1; }
a { color: var(--link); }
p, li { overflow-wrap: anywhere; }
code { background: var(--code); border-radius: 4px; padding: .1em .35em; font: .88em/1.5 "SFMono-Regular", Consolas, "Courier New", monospace; }
pre { background: var(--code); border: 1px solid var(--line); border-radius: 8px; padding: 14px 16px; overflow-x: auto; }
pre code { background: none; padding: 0; font-size: .85rem; }
blockquote { margin: 1em 0; padding: .6em 1em; border-left: 4px solid var(--gold); background: color-mix(in srgb, var(--gold) 10%, transparent); border-radius: 0 8px 8px 0; }
blockquote p { margin: .3em 0; }
.table-wrap { overflow-x: auto; margin: 1em 0; }
/* 文書の中に直接 HTML で書いた表も、狭い画面では横にずらして読む */
.chapter :not(.table-wrap) > table { display: block; max-width: 100%; overflow-x: auto; }
table { border-collapse: collapse; width: 100%; font-size: .92rem; }
th, td { border: 1px solid var(--line); padding: 8px 10px; vertical-align: top; text-align: left; }
th { background: color-mix(in srgb, var(--navy) 8%, var(--paper)); white-space: nowrap; }
figure { margin: 1.2em 0; text-align: center; }
img { max-width: 100%; height: auto; border-radius: 6px; border: 1px solid var(--line); background: #fff; }
figcaption { font-size: .82rem; color: var(--sub); margin-top: .3em; }
hr { border: 0; border-top: 1px dashed var(--line); margin: 2em 0; }
.file-ref { color: var(--sub); border-bottom: 1px dotted var(--sub); }
#totop { position: fixed; right: 16px; bottom: 16px; width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--gold); background: var(--paper); color: var(--ink); font-size: 1.2rem; cursor: pointer; }
@media (max-width: 900px) {
  header.top { min-height: 56px; padding: 8px 12px; }
  header.top b { font-size: 1rem; }
  header.top small { display: none; }
  .layout { grid-template-columns: minmax(0, 1fr); }
  nav.toc { position: fixed; top: 56px; left: 0; width: min(320px, 85vw); z-index: 9; background: var(--bg); transform: translateX(-100%); transition: transform .2s; }
  body.toc-open nav.toc { transform: none; box-shadow: 0 0 0 100vmax rgb(0 0 0 / .35); }
  main { padding: 16px 16px 100px; }
  .chapter { padding: 20px 16px 28px; }
  h1 { font-size: 1.6rem; }
}
/* パソコン：「目次」ボタンで横の目次を閉じる・開く（閉じると本文が広がる） */
@media (min-width: 901px) {
  body.toc-closed .layout { grid-template-columns: minmax(0, 1fr); max-width: 1080px; }
  body.toc-closed nav.toc { display: none; }
}
@media print {
  header.top, nav.toc, #totop { display: none; }
  .layout { display: block; }
  .chapter { break-before: page; border: 0; }
}
</style>
</head>
<body>
<header class="top">
  <button id="menu" type="button" aria-label="目次を閉じる・開く" aria-expanded="true" style="margin-left:0">☰ 目次</button>
  <span><b>推理手帳 解体新書</b><br><small>DEDUCTION NOTEBOOK · ANATOMY</small></span>
  <button id="theme" type="button" title="明るい表示・暗い表示を切り替える">表示切替</button>
</header>
<div class="layout">
  <nav class="toc" aria-label="目次">
    <input type="search" id="toc-filter" placeholder="目次を絞りこむ" aria-label="目次を絞りこむ">
    <ul>${tocHtml}</ul>
  </nav>
  <main>
${sections.join('\n')}
    ${sections.length ? '' : '<section class="chapter"><p>まだ文書がありません。README.md や docs/ の Markdown を書いてから作り直してください。</p></section>'}
    <p style="text-align:center;color:var(--sub);font-size:.8rem">この HTML は README.md と docs/ の Markdown から tools/docs-html/build.mjs で作っています（直すときは Markdown を直して作り直す）。</p>
  </main>
</div>
<button id="totop" type="button" aria-label="いちばん上へ" title="いちばん上へ">↑</button>
<script>
(() => {
  const body = document.body;
  // 目次の開け閉め：スマホは上に重ねて開く（toc-open）、パソコンは横の目次を閉じる（toc-closed。この端末に覚える）
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
  try { if (localStorage.getItem('suiri-guide-toc') === 'closed') body.classList.add('toc-closed'); } catch {}
  menu.addEventListener('click', () => {
    if (wide()) {
      keepPlace(() => body.classList.toggle('toc-closed'));
      try { localStorage.setItem('suiri-guide-toc', body.classList.contains('toc-closed') ? 'closed' : 'open'); } catch {}
    } else {
      body.classList.toggle('toc-open');
    }
    sync();
  });
  document.querySelectorAll('nav.toc a').forEach(a => a.addEventListener('click', () => { body.classList.remove('toc-open'); sync(); }));
  sync();
  document.getElementById('totop').addEventListener('click', () => scrollTo({ top: 0 }));
  // 明るい・暗い表示（この端末に覚える）
  const root = document.documentElement;
  try { const t = localStorage.getItem('suiri-guide-theme'); if (t) root.dataset.theme = t; } catch {}
  document.getElementById('theme').addEventListener('click', () => {
    const dark = root.dataset.theme ? root.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    root.dataset.theme = dark ? 'light' : 'dark';
    try { localStorage.setItem('suiri-guide-theme', root.dataset.theme); } catch {}
  });
  // 目次の絞りこみ
  document.getElementById('toc-filter').addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('nav.toc > ul > li').forEach(li => {
      let any = false;
      li.querySelectorAll('ul li').forEach(s => { const hit = !q || s.textContent.toLowerCase().includes(q); s.hidden = !hit; any ||= hit; });
      li.hidden = !(any || li.querySelector('.toc-chapter').textContent.toLowerCase().includes(q));
    });
  });
  // いま読んでいる節を目次で目立たせる
  const links = new Map([...document.querySelectorAll('nav.toc ul ul a')].map(a => [a.getAttribute('href').slice(1), a]));
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
  document.querySelectorAll('h2[id]').forEach(h => obs.observe(h));
  // URL に #見出し が付いて開いたとき：画像を読み終えて高さが決まってから、もう一度その見出しへ
  addEventListener('load', () => { if (location.hash) document.getElementById(decodeURIComponent(location.hash.slice(1)))?.scrollIntoView(); });
})();
</script>
</body>
</html>
`;

fs.writeFileSync(OUT, html);
console.log(`docs/guide.html を作りました（${(fs.statSync(OUT).size / 1024 / 1024).toFixed(1)} MB・${CHAPTERS.length} 章・画像 ${dataUri.size} 枚）`);
