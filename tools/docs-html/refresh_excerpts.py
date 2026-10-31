#!/usr/bin/env python3
"""
流れ解説の断片（docs/flow-src/*.html）のコードの抜き出しを、元のファイルの今の行に合わせて直す。
元のファイルの行に「行末コメント」が足されたとき（コードの部分は同じ）に使う：抜き出しの行を、コメントつきの今の行に置き換える。
コードそのものが変わった行は直さない（bin/check が「コードの抜き出しが本物と違う」で止めるので、断片を手で直す）。

  python3 tools/docs-html/refresh_excerpts.py      直す（直した行の数を出す）
"""
import html
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent.parent
TRAILING = re.compile(r'\s+//\s.*$')  # 行末コメント（前に空白があり、// のあとに空白）


def code_part(line):
    return TRAILING.sub('', line).rstrip()


def main():
    changed = 0
    for frag in sorted((ROOT / 'docs/flow-src').glob('*.html')):
        text = frag.read_text(encoding='utf-8')
        last = None
        out = []
        pos = 0
        for m in re.finditer(r'(<div class="file">(.*?)</div>\s*<pre><code>)(.*?)(</code></pre>)', text, flags=re.S):
            label = html.unescape(re.sub(r'<[^>]+>', '', m.group(2)))
            pm = re.search(r'((?:src|infra|docs)/[\w./@-]+|docker-compose\.yml)', label)
            path = pm.group(1) if pm else (last if '同じファイル' in label else None)
            out.append(text[pos:m.start(3)])
            pos = m.end(3)
            code = m.group(3)
            if not path or not (ROOT / path).is_file():
                out.append(code)
                continue
            last = path
            src_lines = [l.strip() for l in (ROOT / path).read_text(encoding='utf-8', errors='replace').split('\n')]
            present = set(src_lines)
            by_code = {}
            for l in src_lines:
                base = code_part(l)
                if base != l and base:
                    by_code.setdefault(base, l)
            new_lines = []
            for raw in code.split('\n'):
                plain = html.unescape(raw)
                stripped = plain.strip()
                if stripped and stripped not in present and code_part(stripped) in by_code:
                    indent = plain[: len(plain) - len(plain.lstrip())]
                    new_lines.append(html.escape(indent + by_code[code_part(stripped)], quote=False))
                    changed += 1
                else:
                    new_lines.append(raw)
            out.append('\n'.join(new_lines))
        out.append(text[pos:])
        new_text = ''.join(out)
        if new_text != text:
            frag.write_text(new_text, encoding='utf-8')
    print(f'抜き出しの行を {changed} 行、今の元のファイル（コメントつき）に合わせました')


if __name__ == '__main__':
    main()
