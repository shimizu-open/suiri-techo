#!/usr/bin/env python3
"""
流れ解説の断片（docs/flow-src/*.html）のコードの抜き出しを、元のファイルの今のコメントに合わせる。
行末のコメントを手前の行に移したときや、関数・分岐の手前のコメントを書き直したときに使う（コードの行そのものは変えない前提）。
  - 行末のコメントが消えた行：今の行（コメントなし）と、その手前のコメントを抜き出しに入れる
  - 抜き出しの中のコメントの行が元のファイルにもうない：消して、次のコードの行の手前にある今のコメントを入れる
行末のコメントを書き足しただけのときは refresh_excerpts.py を使う。

  python3 tools/docs-html/sync_excerpts.py      直す（直した抜き出しの数と、合わせられなかった行を出す）
"""
import html, re, sys
from pathlib import Path
ROOT = Path(__file__).resolve().parent.parent.parent
TRAIL = re.compile(r'\s+//\s.*$')
def is_comment(s):
    return s.startswith(('//', '/*', '*', '{{--', '#')) and not s.startswith('#[') or s.endswith(('*/', '--}}'))
def lead(s): return len(s) - len(s.lstrip(' '))
def comment_block(S, i):
    j = i - 1; out = []
    while j >= 0 and S[j].strip() and is_comment(S[j].strip()):
        out.insert(0, S[j]); j -= 1
    return out
changed = 0; unresolved = []
for frag in sorted((ROOT / 'docs/flow-src').glob('*.html')):
    text = frag.read_text(encoding='utf-8'); last = None
    def fix(m):
        global changed, last
        label = html.unescape(re.sub(r'<[^>]+>', '', m.group(2)))
        pm = re.search(r'((?:src|infra|docs)/[\w./@-]+|docker-compose\.yml)', label)
        path = pm.group(1) if pm else (last if '同じファイル' in label else None)
        if not path or not (ROOT / path).is_file():
            return m.group(0)
        last = path
        S = (ROOT / path).read_text(encoding='utf-8').split('\n')
        Sst = [l.strip() for l in S]
        joined = '\n'.join(Sst)
        lines = html.unescape(m.group(3)).split('\n')
        out = []; last_i = -1; pending = False; touched = False
        def find(cp):
            cands = [k for k, l in enumerate(Sst) if l == cp or TRAIL.sub('', l) == cp]
            after = [k for k in cands if k > last_i]
            return (after or cands or [None])[0]
        def emit_with_comments(i, e):
            nonlocal touched
            delta = lead(e) - lead(S[i])
            block = comment_block(S, i)
            # すでに直前に同じコメントが出ていれば足さない
            while out and out[-1].strip() and is_comment(out[-1].strip()) and out[-1].strip() not in joined:
                out.pop()
            for c in block:
                if not out or out[-1].strip() != c.strip():
                    out.append(' ' * max(0, lead(c) + delta) + c.strip())
            out.append(' ' * max(0, lead(S[i]) + delta) + S[i].strip())
            touched = True
        for e in lines:
            es = e.strip()
            if not es or '…（省略）…' in es or es in ('...', '…'):
                out.append(e); continue
            if es in joined:
                if pending and not is_comment(es):
                    i = find(es)
                    if i is not None:
                        emit_with_comments(i, e); last_i = i; pending = False; continue
                    pending = False
                out.append(e)
                if not is_comment(es):
                    i = find(es)
                    if i is not None: last_i = i
                continue
            if is_comment(es):
                pending = True; touched = True; continue
            cp = TRAIL.sub('', es)
            i = find(cp)
            if i is None:
                unresolved.append(f'{frag.name}: {path}: {es[:80]}'); out.append(e); continue
            emit_with_comments(i, e); last_i = i; pending = False
        if not touched:
            return m.group(0)
        changed += 1
        return m.group(1) + html.escape('\n'.join(out), quote=False) + m.group(4)
    new = re.sub(r'(<div class="file">(.*?)</div>\s*<pre><code>)(.*?)(</code></pre>)', fix, text, flags=re.S)
    if new != text:
        frag.write_text(new, encoding='utf-8')
print('blocks changed', changed)
print('\n'.join(unresolved))
