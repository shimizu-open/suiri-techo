# プログラムの流れ解説（docs/flow.html）の元

`docs/flow.html` は、利用者の行動（ユーザーストーリー）ごとに、住所の表（`src/routes/web.php` と、それが読み込む `src/routes/web/{機能}.php`）から始まる処理の流れを1歩ずつ追う解説です。このフォルダの章ごとの HTML の断片（`NN-名前.html`）を `node tools/docs-html/flow.mjs` が1つにまとめ、CSS と画像を埋め込んで `docs/flow.html` を作ります。`docs/flow.html` を直接直さず、ここの断片を直して作り直してください。

`bin/check docs` が止めるもの：

- 断片を変えたのに `docs/flow.html` を作り直していない（元の断片・画像・道具の指紋）
- 住所の表（`src/routes/web.php`・`src/routes/web/*.php`）を変えたのに解説を直していない（住所の表の指紋。コメントと空行だけの変更は数えない）
- コードの抜き出しが本物のファイルと食い違っている（下の「コードの抜き出し」）

手本は Baker Street English の同じフォルダ（`docs/flow-src/`）です。

## 章の一覧

| ファイル | id | 行動 |
|---|---|---|
| `00-map.html` | `s0` | 地図の読み方：お願いと返事・4つの箱・見張り役・住所の表・層の分担。どの住所がどの章か |
| `01-login.html` | `s1` | Google でログインする（登録〔招待制のときは招待の確認〕・同意へ） |
| `02-today.html` | `s2` | きょうの画面ができるまで（今日とタスクを1つにまとめた画面。Query と部品、たたんだ枠、カレンダーの予定の窓口） |
| `03-task.html` | `s3` | タスクを足す・済ませる・直す（1行の欄の3つのボタン → TaskObserver がカレンダーへの書き出しを予約する所まで。前の一覧の住所は移すだけ） |
| `04-case-create.html` | `s4` | 事件を作る（練習メニュー・「気になる」から来た場合も） |
| `05-clue-hypothesis.html` | `s5` | 手がかりを足す → ワトソンのひとこと、見立てと ○× |
| `06-prediction.html` | `s6` | もしそうなら：予想を立てる → 確かめるタスク → 文と確かさを書き換えさせない仕組み |
| `07-resolve.html` | `s7` | 答え合わせ（4択 → 確かめるタスクが済む → 見立ての直しを勧める） |
| `08-partner.html` | `s8` | 相棒の見立て（隠して書いて見せ合う） |
| `09-calendar.html` | `s9` | カレンダーとつなぐ → 書き出しの Job → 15分ごとの取り込み |
| `10-review.html` | `s10` | 週のふりかえり（集計と確かさの答え合わせ）・ノーベリ帳・屋根裏の棚 |
| `11-safeguards.html` | `s11` | 守りの仕組み（Policy・回数制限・CSRF・セキュリティのヘッダ・ログ・退会） |
| `12-guide.html` | `s12` | 手引き：名推理カードを書いて裏返す・練習メニュー |
| `13-ai-connect.html` | `s13` | ChatGPT・Claude とつなぐ読むだけの窓口（発見用の住所 → 自己登録 → 同意の画面 → 通行証 → `/mcp` の読むだけの道具）・写すボタン |

住所の表に住所を足したら、その住所を `00-map.html` の対応表に足し、流れを追う章に書き足してから作り直します。

## 書き方の決まり（断片を書く人・AI 向け）

- 読み手はプログラミングと Laravel を学び始めた人。専門用語は初めて出たところで `<dfn>` を付け、「役目 → たとえ → 中身」の順に説明する。省略しない。「〜を参照」で済ませず、その場で分かるように書く（くわしい説明が別の章にあるときは、一言で言い換えてからリンクする）。
- 本当のコードだけを書く。動きを確かめていないことは書かない（必要なら `docker compose exec -T app php artisan tinker --execute=...` で確かめる。見張り役の並ぶ順もこれで出せる）。
- 行番号は書かない（変わるので）。ファイルの場所と関数名で示す。
- 書かない言葉は `.claude/rules/docs.md` と `docs/design/words.md` の「3. 使わない言葉」。コードを写すとき、その言葉を含む行は省略にする。予想の結果は「当たり・外れ」。
- 運営者の体験や気持ちを作って書かない。
- 画像は `docs/images/` にあって、docs の Markdown（`docs/guide/*.md` など）でも使っているものだけを使う（流れ解説だけで使う画像は「使っていない画像」として `bin/check` に止められる）。

## コードの抜き出し

```html
<div class="code"><div class="file">src/app/Actions/Cases/AddClue.php</div><pre><code>（本物のコード。&lt; &gt; &amp; はエスケープする）</code></pre></div>
```

- `<div class="file">` には、プロジェクト直下からのパス（`src/…`・`infra/…`・`docker-compose.yml`）を書く。`bin/check` はこのパスのファイルを開き、抜き出しの1行ずつ（前後の空白を除いたもの）が、そのファイルの中にそのままあるかを確かめる。
- 長いときは関係する行だけを写し、省いた所は `// …（省略）…`（Blade なら `{{-- …（省略）… --}}`）の1行にする。この印の行は確かめから外れる。
- Laravel 本体のコードは `src/vendor/laravel/framework/…` から写す（Laravel を上げたら食い違いを確かめる）。
- コードのコメントを直して抜き出しが食い違ったら：行末のコメントを足しただけなら `python3 tools/docs-html/refresh_excerpts.py`、コメントを手前の行に移した・書き直したなら `python3 tools/docs-html/sync_excerpts.py`。それでも残る食い違いは手で直す。

## 断片の形（使える部品）

断片は `<section class="story" id="s番号">` で始めて `</section>` で終わる。CSS は `tools/docs-html/flow.mjs` が持っているので、`style` 属性や `<style>` は書かない。

```html
<section class="story" id="s5">
  <h2><span class="no">5</span>手がかりを足し、見立てと ○× をつける</h2>
  <p class="userstory">事件を調べている人として、見たこと・聞いたことを忘れないうちに残したい。だから、事件の画面で手がかりを足す。</p>
  <p class="summary">この章で追う道筋：<code>POST /cases/12/clues</code> → … → 事件の画面</p>

  <h3>小見出し（あれば。目次に出る）</h3>
  <ol class="steps">
    <li class="step">
      <span class="who browser">ブラウザ</span>   <!-- who の種類：browser / mw（見張り役）/ server / db / js -->
      <h4>「これでOK」を押す</h4>
      <p>説明の文…</p>
      <div class="code"><div class="file">src/resources/views/components/case-step/clue-card.blade.php</div><pre><code>…</code></pre></div>
      <div class="data"><b>このとき動くデータ</b><ul><li>…</li></ul></div>
      <div class="analogy"><b>たとえると</b>…</div>
      <div class="point"><b>ここが大事</b>…</div>
      <figure class="shot"><img data-img="screen-case-step-clue.png" alt="事件の画面（手がかりのカード）"><figcaption>事件の画面（手がかりのカード）</figcaption></figure>
    </li>
  </ol>

  <div class="recap"><b>この章のまとめ</b><ol><li>…</li></ol></div>
</section>
```

- 画像は `docs/images/` のファイル名を `data-img` に書く（作るときに埋め込まれる。PNG は WebP に縮める）。
- 用語は `<dfn>用語</dfn>` で初めて出たところを目立たせる。
- 章どうしのリンクは `<a href="#s5">5章</a>` のように書く。
- `<h3>` には id を書かなくてよい（目次から飛べるよう、作るときに付く）。

## 作り直す

```bash
node tools/docs-html/flow.mjs   # docs/flow.html を作る（初回は (cd tools/docs-html && npm ci)。画像を縮めるのに ffmpeg を使う）
bin/check docs                  # 作り直し忘れ・抜き出しの食い違い・リンク切れ
```
