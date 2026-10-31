---
paths:
  - "docs/**"
  - "README.md"
  - "tools/docs-html/**"
---

# ドキュメント

## 置き場所

| 読む人 | 置き場所 | 中身 |
|---|---|---|
| 人間（初心者） | `README.md`・`docs/README.md`（目次）・`docs/guide/NN-*.md`（解体新書） | 図とスクリーンショットつき。どの章も「この章でわかること」→ 節 →「次に読む章」 |
| 作る人・AI | `docs/design/`（`requirements.md`・`basic-design.md`・`data-model.md`・`words.md`・`components.md`） | 何を作るか・画面と層・表・言葉・部品説明書。状態と日付を先頭に |
| AI | `CLAUDE.md`・`.claude/rules/*.md`・`docs/ai/codemap.md` | 道案内・分野ごとの決まり・「機能 → ファイル → 関数」の索引（行番号は書かず、シンボル名で引けるようにする） |
| 調査 | `docs/research/`（入口は `README.md`） | 作る前の調査 |
| 人間（ブラウザで） | `docs/guide.html`・`docs/flow.html` | 下の道具で作る HTML 版（直接直さない） |
| 画像 | `docs/images/` | SVG の図・スクリーンショット（横幅を縮めて必要な所だけ） |

## 書き方

- 読み手はプログラミングの初心者。節は**役目を一言で → たとえると → 中身**の順に、平易な日本語で。難しい言葉は最初に出たところで言い換える。
- `docs/design/words.md` の「使わない言葉」に載っている言葉（オーナーの決まりの1語を含む）は書かない。決まりを説明するときも、その言葉を書かずに words.md を指す（`bin/check` が止める。words.md の表そのものは例外）。
- 運営者の体験や気持ちを作って書かない。運営者の氏名・連絡先は運営者が書く。
- 手順に記録が消えるコマンド（`migrate:fresh` など）を書かない（`bin/check` が止める）。
- 言葉の表は2か所：人間向け `docs/design/words.md` と AI 向け `.claude/rules/domain.md`。**片方を直したら、もう片方も直す。**
- アプリのコード（`src/`）の決まりを変えたら、`docs/ai/codemap.md` と、関係する `.claude/rules/` も直す。部品を足したら `docs/design/components.md` に書き足す。
- 画面を変えたら、その画面のスクリーンショット（スマホとパソコン）と説明を合わせて直す。リンク切れと、使っていない画像を残さない（`bin/check docs` が止める）。

## HTML 版の作り直し（`tools/docs-html/`）

- `docs/guide.html`：`README.md`・`docs/README.md`・`docs/guide/NN-*.md`・設計書（requirements・basic-design・data-model・words・components）・`docs/research/README.md` と画像を1つにまとめた HTML（画像も中に埋め込み、ブラウザで開くだけで読める）。docs を変えたら **`node tools/docs-html/build.mjs`** で作り直し、一緒にコミットする（作り直し忘れは `bin/check` が指紋で止める）。並べる文書を足すときは `build.mjs` の `CHAPTERS`。
- `docs/flow.html`：プログラムの流れ解説（利用者の行動ごとに、住所の表 `src/routes/web.php`・`src/routes/web/*.php` から1歩ずつ追う）。元は `docs/flow-src/NN-*.html`（書き方は Baker Street English の `docs/flow-src/README.md` と同じ形。書き始めるときにこのリポジトリにも置く）で、**`node tools/docs-html/flow.mjs`** で作る。住所の表を変えたら、その流れを断片に書き足して作り直す（`bin/check` が住所の表の変更・作り直し忘れ・コードの抜き出しの食い違いを止める）。コードのコメントを直して抜き出しが食い違ったら、行末のコメントを足しただけなら `python3 tools/docs-html/refresh_excerpts.py`、手前の行に移した・書き直したなら `python3 tools/docs-html/sync_excerpts.py`。
- 道具を入れる（クローンしたら1回）：`(cd tools/docs-html && npm ci)`。画像を縮めるのに ffmpeg を使う。
