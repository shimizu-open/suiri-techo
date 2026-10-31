#!/usr/bin/env python3
"""
コードのコメントの決まり（.claude/rules/code-comments.md）のうち、機械で判定できるものを確かめる。
推理手帳の bin/check から呼ばれる（Baker Street English の同じ道具を、このアプリのパスに合わせたもの）。手で動かすと、抜けている所を一覧にする。

  python3 bin/code_comments.py [ファイル...]   抜けている所を出す（ファイルを渡さなければ、git で管理している物とまだ入れていない新しい物の全部）

確かめること（どれも「その行のすぐ上の行がコメント」かどうか）：
  - クラス・関数（メソッド）の宣言の手前に、何をするものかのコメント
  - if・for・foreach・while・switch・match・try・do の手前に、何を確かめてどう分かれるか（try は何を試して、失敗したらどうするか）のコメント
  - Blade の @if・@unless・@foreach・@forelse・@for・@while・@switch・@isset・@auth・@guest の手前に {{-- --}} のコメント
"""
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

# 対象のファイル（設定ファイル config/ は配列だけなので、行ごとのコメントで足りる。対象にしない）
TARGET = re.compile(
    r'^src/(app/.*\.php|routes/.*\.php|bootstrap/app\.php|database/(migrations|seeders|factories)/.*\.php|tests/.*\.php'
    r'|resources/js/.*\.js|resources/views/.*\.blade\.php)$'
)

# ---------- 「すぐ上の行がコメントか」 ----------
PHP_JS_COMMENT = re.compile(r'^\s*(//|/\*|\*|#(?!\[))|\*/\s*$')
BLADE_COMMENT = re.compile(r'--\}\}\s*$|-->\s*$')
PHP_ATTRIBUTE = re.compile(r'^\s*#\[')  # PHP の属性（#[Fillable] など）は宣言の一部なので、その上を見る

# ---------- コメントが要る行 ----------
PHP_DECL = [
    ('クラス', re.compile(r'^\s*((final|abstract|readonly)\s+)*(class|interface|trait|enum)\s+\w+')),
    ('クラス', re.compile(r'^\s*return\s+new\s+class\b')),  # 移行（migration）の無名クラス
    ('関数', re.compile(r'^\s*((public|protected|private|static|abstract|final)\s+)*function\s+&?\w+\s*\(')),
]
JS_DECL = [
    ('クラス', re.compile(r'^\s*(export\s+)?(default\s+)?class\s+\w+')),
    ('関数', re.compile(r'^\s*(export\s+)?(default\s+)?(async\s+)?function\s*\*?\s*\w+\s*\(')),
    ('関数', re.compile(r'^\s*(export\s+)?(const|let|var)\s+\w+\s*=\s*(async\s+)?(function\b|(\([^)]*\)|\w+)\s*=>)')),
    # クラスやオブジェクトの中のメソッド（name(...) { で終わる行。if などの制御の文は除く）
    ('関数', re.compile(r'^\s+(static\s+|async\s+|get\s+|set\s+)*#?(?!(if|for|while|switch|catch|function|return)\b)\w+\s*\([^()]*(\([^()]*\)[^()]*)*\)\s*\{\s*$')),
]
CONTROL = re.compile(r'^\s*(if|for|foreach|while|switch|try|do)\b\s*[({]|^\s*(return\s+|\$\w+\s*=\s*)?match\s*\(')
BLADE_CONTROL = re.compile(r'^\s*@(if|unless|foreach|forelse|for|while|switch|isset|auth|guest)\b')


def prev_line(lines, i, skip_attributes=False):
    """i 行目（0 始まり）のすぐ上の、空でない行を返す（PHP の属性の行は飛ばせる）。なければ空文字"""
    j = i - 1
    # 空の行（と、飛ばすときは属性の行）をさかのぼる
    while j >= 0:
        # 空の行は飛ばす
        if not lines[j].strip():
            j -= 1
            continue
        # 1行の属性（#[Fillable([...])]）は飛ばす
        if skip_attributes and PHP_ATTRIBUTE.match(lines[j]):
            j -= 1
            continue
        # 複数行の属性（#[Fillable([ … ])] が何行にもわたる）は、始まりの #[ の行まで飛ばす
        if skip_attributes and lines[j].rstrip().endswith(']'):
            k = j
            while k >= 0 and not PHP_ATTRIBUTE.match(lines[k]):
                k -= 1
            if k >= 0 and k < j:
                j = k - 1
                continue
        break
    return lines[j] if j >= 0 else ''


def problems_in(path, text):
    """1つのファイルの中で、コメントが抜けている所を [(行番号, 種類, その行)] で返す"""
    lines = text.split('\n')
    found = []
    in_block_comment = False
    # Blade の型紙：分岐・くり返しの手前に {{-- --}} があるか
    if path.endswith('.blade.php'):
        for i, line in enumerate(lines):
            # 分岐・くり返しの始まりで、すぐ上がコメントでなければ抜け
            if BLADE_CONTROL.match(line) and not BLADE_COMMENT.search(prev_line(lines, i)):
                found.append((i + 1, '分岐・くり返し', line.strip()))
        return found

    decls = PHP_DECL if path.endswith('.php') else JS_DECL
    for i, line in enumerate(lines):
        stripped = line.strip()
        # 複数行のコメント（/* … */）の中は、コードではないので見ない
        if in_block_comment:
            in_block_comment = '*/' not in stripped
            continue
        if stripped.startswith('/*') and '*/' not in stripped:
            in_block_comment = True
            continue
        # 宣言（クラス・関数）：すぐ上（属性の行は飛ばす）がコメントか
        for kind, pat in decls:
            if pat.match(line):
                if not PHP_JS_COMMENT.search(prev_line(lines, i, skip_attributes=True)):
                    found.append((i + 1, kind, stripped))
                break
        else:
            # 制御の文（if・for・try など）：すぐ上がコメントか
            if CONTROL.match(line) and not PHP_JS_COMMENT.search(prev_line(lines, i)):
                found.append((i + 1, '分岐・くり返し・try', stripped))
    return found


def check(files):
    """bin/check から呼ぶ：対象のファイルだけを見て、抜けを「直し方つき」の文の一覧で返す"""
    out = []
    for f in files:
        # 対象外のファイル（設定・画像など）は見ない
        if not TARGET.match(f) or not (ROOT / f).is_file():
            continue
        for no, kind, line in problems_in(f, (ROOT / f).read_text(encoding='utf-8')):
            out.append(f'{f}:{no}  【コメントが手前にない（{kind}）】{line[:100]}\n'
                       '    → すぐ上の行に、何をするか（分岐なら何を確かめてどう分かれるか、try なら何を試して失敗したらどうするか）を'
                       'コメントで書く（.claude/rules/code-comments.md）')
    return out


def main():
    files = sys.argv[1:] or subprocess.run(['git', 'ls-files', '--cached', '--others', '--exclude-standard'],
                                           cwd=ROOT, capture_output=True, text=True).stdout.split()
    problems = check(files)
    print('\n'.join(problems) if problems else 'コメントの抜け：なし')
    print(f'\n合計 {len(problems)} か所', file=sys.stderr)
    return 1 if problems else 0


if __name__ == '__main__':
    sys.exit(main())
