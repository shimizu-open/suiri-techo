# 部品説明書（Blade コンポーネント）

画面を組み立てる部品の一覧です。部品の役目・受け取る値（props）・使っている画面を書きます。部品を足したら、ここに書き足します。置き場所と分け方は [基本設計](basic-design.md) の 4章。

- 状態：作り直しの途中。見た目の共通部品（2章 `ui`）・今日（3章）・タスク（4章）・事件（5章）・手引き（6章）は、作り直しのあとの実物（各部品の `@props`）に合わせて書き直した。骨組み（1章）・ふりかえり（7章）・設定（8章）・ログイン（9章）は作り直しの前の版のままで、それぞれの作り直しが終わったら書き直す
- 更新：2026-10-31

## 表の読み方

- **部品**：画面の中で書くときの形。部品のファイルは `src/resources/views/components/{分類}/{名前}.blade.php`（例 `<x-case-step.clue-card>` → `components/case-step/clue-card.blade.php`）。ファイルの先頭のコメントに、使い方の例があります。
- **受け取る値**：部品の `@props([...])` に書いてある値です。
  - `名前=既定`：渡さなくてもよい値（渡さないと既定の値になる）。名前だけのものは**必ず渡す**値です。
  - PHP の値は `:名前="$値"` で渡します。`@props` では `partnerName` のように書き、画面では `:partner-name="…"` のようにつなぎ線の形で渡します。
  - 「中身」はタグの間に入れるもの（`<x-ui.button>保存する</x-ui.button>` の「保存する」）。「スロット `actions`」は `<x-slot:actions>…</x-slot:actions>` で渡す、名前つきの中身です。
  - 表にない属性（`class` など）は、部品のいちばん外側の要素に付きます。
- **kid**：子ども向けの言葉を出すかどうか。画面が `$user->prefersKidWords()`（ふだんの場面が親子の人なら真）を渡します。
- **使っている画面**：画面のファイル `resources/views/{機能}/{動作}.blade.php` の日本語名（一覧は [基本設計](basic-design.md) の 2章）。部品の中から使っているときは「`<x-…>` の中」と書きます。
- 部品はデータを取りに行きません。受付（コントローラー）が Query などで用意した値を、画面から渡します。

## 1. 骨組み（`components/layouts/`）

ログイン後の画面はすべて `app` で、ログイン前の画面と公開ページは `guest` で包みます。ナビとフッターは骨組みが置くので、画面から直接は使いません。

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-layouts.app>` | ログイン後の骨組み。上の帯に画面の名前と右に設定、スマホは下の固定ナビ、パソコンは左の縦ナビ。中身の上にお知らせ、下にフッター | `title`（帯と `<title>` に出る）・スロット `actions`（帯の右、設定の左に置くボタン）・中身 | ログイン後のすべての画面（今日・タスクの一覧・タスクをくわしく作る・タスクを直す・事件の一覧・事件を作る・事件の詳細・答え合わせ・手引きの入口・技能・名推理カードの一覧・名推理カード（表・裏）・練習メニュー・演繹・帰納・アブダクション・週のふりかえり・ノーベリ帳・屋根裏の棚・設定・カレンダーとつなぐ前の説明） |
| `<x-layouts.guest>` | ログイン前と公開ページの骨組み。上に題字、中身の上にお知らせ、下にフッター。ログインして同意した人には右上に「手帳に戻る」 | `title=null`（`<title>` に足す）・`brand=true`（`false` で上の題字の帯を出さない。トップのように中身の一番上で題字を大きく見せる画面）・中身 | トップ・同意・このアプリについて・プライバシーポリシー・利用規約 |
| `<x-layouts.nav>` | ナビ（今日・タスク・事件・手引き・ふりかえり）。項目は `config('suiri.nav')`。画面（ルート）がまだない項目はリンクにせず薄く出す（`Route::has()`） | `variant=bottom`（`bottom`＝スマホの下の固定ナビ・`side`＝パソコンの左の縦ナビ） | `<x-layouts.app>` の中 |
| `<x-layouts.footer>` | このアプリについて・プライバシーポリシー・利用規約へのリンクと、「医療機器ではありません」の一文（`config('suiri.notes.footer')`） | なし（`class` は外側に付く） | `<x-layouts.app>`・`<x-layouts.guest>` の中 |

## 2. 共通（`components/ui/`）

どの画面・どの部品でも使う部品です。機能の言葉を持たないので、機能ごとの部品はこれを組み合わせて作ります。作り直しで、3つの原則（1画面に「いまやること」は1つ・やわらかい話し言葉・子どもと一緒に使える）を部品の形で守るための10個（2-1）を足しました。形と色の見本は [部品の見本（UI キット）](ui-kit.md) にあります。

- **段の色 `tone`**：多くの部品が、段ごとの色を `tone` で受け取ります。`clue`（見たこと＝手がかり・紺）・`hypothesis`（どうして？＝見立て・からし色）・`prediction`（もしそうなら・深い緑）・`check`（確かめる日・予想と同じ緑）・`answer`（答え合わせ・深い赤）・`plain`（ふつう・紺のインク）。部品の中では、段ごとのクラスの組み合わせ（`bg-clue-soft text-clue` など）を PHP の配列で選びます（`app.css` に独自のクラスを足さない）。知らない値は `plain` として扱います。
- **使っている画面の欄**：作り直しが終わった今日・タスク・事件・手引きの分だけを書いています。ふりかえり・設定・ログインの画面での使い方は、その節を書き直すときに足します。

### 2-1. いまやることを組む部品（作り直しで足した）

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-ui.step-card>` | いまやることのカード（1画面に1枚）。捜査ファイルの形：左上に段の色のつまみ（絵と「ステップ 2 / 5」）、その下に段の淡い色の見出しの面（問いと例）、白い面に中身と主なボタン。「あとでできること」はカードの外の下に小さく1行。フォームにするときは、このカードを `<form>` で包む | `title`（問いの見出し）・`step=null`・`total=null`（つまみの「ステップ 2 / 5」。どちらか省くと数を出さない。文は `config('suiri.ui.step')`）・`icon=null`（つまみに出す `<x-ui.step-icon>` の名前。数も絵もなければ、つまみを出さずに角の丸いカードだけにする）・`tone=plain`・`hint=null`（例や一言の説明。問いのすぐ下にやわらかい色で）・中身（入力欄など）・スロット `action`（主なボタン。カードの一番下）・スロット `later`（あとでできることの小さなリンク） | `<x-today.next-step>` の中（今日のいちばん上）／事件：`case-new` の4つの問い（`<x-case-new.step>` の中）・`case-step` のいまやることのカード（clue-card・hypothesis-card・fit-card・predict-card・wait-card・resolve-card・close-card・closed-card・edit-case-card・edit-clue-card・edit-hyps-card）・`case-partner`（write-card・mark-card・together・reveal）・`case`（resolve-form・resolve-result） |
| `<x-ui.progress>` | 進み具合の丸の列。済んだ段＝紺で塗った丸にチェック、いまの段＝段の色の大きな枠、まだの段＝うすい小さな丸。丸の間は線でつなぎ（済んだ所は濃い線）、上に「ステップ 2 / 5」。段が6つ以上でスマホの幅のときは、いまの段の名前だけを見せる（ほかは読み上げだけ） | `steps=[]`（1段：`label`・`done`・`current`。`tone` を入れると、その段がいまのときの色）・`tone=plain`（いまの段の色）・`count=true`（`false` で「ステップ n / m」を出さない。`step-card` のつまみに数を出すとき） | 事件：`<x-case-new.step>`（丸4つ）・`<x-case-step.stages>`（丸5つ）・`<x-case-partner.relay-progress>`（丸3つ）の中 |
| `<x-ui.big-button>` | 横いっぱいの主なボタン（高さ 56px・文字 18px。子どもの指でも押しやすい）。1画面に1つ、いまやることの決め手に使う。`href` があればリンク（`<a>`）、なければ `<button>` | `href=null`・`type=submit`・`icon=null`（段の絵の名前なら `<x-ui.step-icon>`、ほかの名前（`plus` など）なら `<x-ui.icon>` を文字の前に出す）・`tone=primary`（`primary` 紺／`clue`・`hypothesis`・`prediction`・`check`・`answer` 段の色で塗る／`secondary` 枠だけの控えめな2つ目）・中身（ボタンの文字）。`name`・`value`・`disabled` などはそのままボタンに付く | 名推理カードの一覧（まだのカードで遊ぶ）・名推理カードの裏（次のカードへ）／部品の中：today（next-step）・task（form）・guide（play-card・practice-item・scene-front）・ui（empty-state・handoff）／事件：事件の一覧（新しい事件を作る）・答え合わせ（取り下げたとき）／部品の中：case-new（next・confirm）・case-step（各カード）・case-partner（turn・write-card・mark-card・together）・case（resolve-form・resolve-result） |
| `<x-ui.step-icon>` | 段ごとの絵（手で描いた線の inline SVG。外の画像やアイコンの道具は使わない）。色は文字の色（`text-clue` などで段の色にする）。ナビや設定などの道具の絵は `<x-ui.icon>` | `name`（`eye` 見たこと・`question` どうして？・`arrow` もしそうなら・`calendar` 確かめる日・`check` 答え合わせ・`book` 手引き・`attic` 屋根裏の棚・`norbury` ノーベリ帳・`partner` 相棒・`sparkle` ひらめき・ワトソンのひとこと。知らない名前なら小さな丸）。`class` を省くと `size-6` | 今日（下の「新しく気になったことを書く」）／部品の中：today（practice-suggestion）・task（done-followup）・guide（entry-list・flow-steps・play-card・rain-analogy・scene-back・scene-front・section）・ui（big-button・done-summary・empty-state・handoff・hint・step-card）／事件：事件の一覧（つまみの中が空のとき）／部品の中：case-new（partner-toggle）・case-step（clue-pile・more・stages・desk）・case-partner（reveal）・case（summary・prediction-original・ai-copy） |
| `<x-ui.hint>` | やわらかい説明の吹き出し。言葉の一言の説明（「見立て＝『どうしてだろう？』の答えの案」）や、ワトソンのひとことを同じ見た目で出す。左に話し手の丸の絵 | `label=null`（吹き出しの上の小さな名前。「ワトソンのひとこと」など）・`icon=sparkle`（`:icon="null"` で絵を出さない）・`tone=plain`（絵の丸と吹き出しの面の色）・中身・スロット `actions`（吹き出しの右下に置く小さなボタン。閉じるなど） | やることを直す（確かめるタスクの知らせと「答え合わせ」）・3つの考え方（いちばん下の注意）／部品の中：task（similar-history）・case-new（clue・hypothesis）・case-step（bubble）・case-partner（reveal）・case（resolve-result） |
| `<x-ui.done-summary>` | 済んだことを1行にたたんだ要約。押すと一覧が開く（JavaScript なしの `<details>`）。カードで囲まず、下に細い線を引いた1行にする（何行か重ねると、済んだ段の目次になる）。いまやることのカードの上に置く | `title`（たたんだときの1行。「見たこと 3つ」）・`items=[]`（開いたときの一覧。文字の配列か `['text' => …]` の形）・`editHref=null`・`editLabel=null`（一覧の下の「直す」のリンク。文字を省くと `config('suiri.ui.summary_edit')`）・`icon=check`・`tone=plain`・`open=false`（開いて出す） | 事件：`<x-case-step.stages>`（どうして？・もしそうなら の要約）・`<x-case-step.clue-pile>`（同じ形を自前で）・`<x-case-step.closed-card>`・`<x-case-partner.mark-card>` の中 |
| `<x-ui.empty-state>` | まだ何もないときの画面。大きな絵・一言・主なボタン1つで「まず何をすればいいか」を見せる。枠で囲まず、左にそろえる | `title`（大きめの一言）・`icon=sparkle`・`actionHref=null`・`actionLabel=null`（両方あるときだけ主なボタン `<x-ui.big-button>` を出す）・`actionIcon=plus`・`tone=plain`（絵の丸とボタンの色）・中身（短い説明）・スロット `action`（ボタンの代わりにフォームを置くとき） | 事件の一覧（事件がまだ1つもないとき） |
| `<x-ui.evidence-tag>` | 証拠品の荷札。手がかり1つ＝1枚の札。左の角を落とした形とひもの穴で「集めた証拠」だとわかるようにする。札は段の淡い色で塗り、枠は付けない | `no=null`（札の番号。「No.3」。形は `config('suiri.ui.tag_no')`）・`label=null`（種類の言葉：見た・聞いた・なかった。子ども向け・仕事の場面の言葉は画面から渡す）・`tone=clue`・中身（手がかりの文）・スロット `actions`（札の右の小さなボタン。○×・直す など） | 事件：`<x-case-new.confirm>`・`<x-case-step.clue-pile>`・`<x-case-step.fit-card>`・`<x-case-partner.write-card>`・`mark-card`・`reveal` の中 |
| `<x-ui.stamp>` | 答え合わせの「確かめた」の丸いはんこ。結果ごとに色と線の形を変え、文字を読まなくてもわかるようにする（当たり＝赤い二重の輪・外れ＝紺の二重の輪・どちらとも言えない＝からし色で内側が点線・前提が崩れた＝灰色の点線の輪）。少し傾けて押した形 | `result`（`hit`・`miss`・`unclear`・`void`。`PredictionResult` の列挙のままでもよい）・`label=null`（はんこの中の言葉。省くと `config('suiri.labels.prediction_result')`。子ども向けの言葉は画面から渡す）・`size=md`（`md` 直径 112px・上に「確かめた」／`sm` 直径 72px・言葉だけ。一覧の行に） | 事件：`<x-case.prediction-original>`（済んだ予想のはんこ）・`<x-case.resolve-form>`（4つのボタンの輪の形）の中 |
| `<x-ui.handoff>` | 相棒モードで1台のスマホを回すときの「スマホを渡して」の大きな付箋。次に書く人と「見ないでね」をはっきり見せる（見せ合うまで互いの見立てを隠す決まりを、画面でも支える）。からし色の紙をテープで留めた形 | `to`（次に書く人の呼び名。「長男にスマホを渡してね」になる。文は `config('suiri.ui.handoff_title')`）・`href=null`（受け取った人が押すボタンの行き先）・`label=null`（そのボタンの文字。省くと `config('suiri.ui.handoff_button')`）・中身（ひとこと「お父さんは見ないでね」など）・スロット `action`（リンクではなくフォームで進むときのボタン） | 事件：`<x-case-partner.turn>` の中（「長男さんにスマホを渡してね」） |

### 2-2. 土台の部品

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-ui.button>` | ふつうのボタン。`href` があればリンク（`<a>`）、なければ `<button>`。1画面の主な操作には `<x-ui.big-button>` を使い、こちらは2つ目以降の操作に使う | `variant=primary`（`primary` 紺で塗る／`secondary` 枠だけ／`ghost` 枠なし／`danger` 深い赤・消す）・`size=md`（`md` 高さ 48px／`sm` 高さ 40px）・`block=false`（横いっぱい）・`type=submit`・`href=null`・中身（ボタンの文字） | やることを直す（確かめるタスクの「答え合わせ」）／部品の中：guide（scene-front の「メモして裏返す」）・ui（confirm） |
| `<x-ui.field>` | 1行の入力欄（ラベル 16px・入力・補足・エラー）。入力欄は高さ 48px 以上。値は送り直したときの入力（`old()`）を優先 | `name`・`label`・`type=text`・`value=null`・`hint=null`（補足）・`required=false`（ラベルに「必須」の印）。ほかの属性（`maxlength`・`placeholder`・`list`・`autocomplete` など）は入力欄に付く | 部品の中：task（form の「いつやる？」「何時から？」） |
| `<x-ui.textarea>` | 複数行の入力欄（ラベル・入力・補足・エラー）。値は `old()` を優先 | `name`・`label`・`value=null`・`hint=null`・`required=false`・`rows=3`。ほかの属性は入力欄に付く | 部品の中：task（form のメモ）・guide（scene-front の「思いついたこと」） |
| `<x-ui.select>` | 選ぶ欄（プルダウン） | `name`・`label`・`options=[]`（「値 => 言葉」。列挙の `options()` をそのまま渡せる）・`selected=null`（列挙でも文字でもよい）・`hint=null`・`required=false`・`placeholder=null`（渡すと先頭に「選ばない」の行） | （今日・タスク・手引きでは使っていない） |
| `<x-ui.choices>` | 横に並んだ選択の札（ラジオ）。2〜5個をひと目で見せたいとき。札は高さ 48px 以上で、選んだものは紺で塗る | `name`・`label`・`options=[]`（「値 => 言葉」）・`selected=null`（列挙でも文字でもよい）・`hint=null` | （今日・タスク・手引きでは使っていない） |
| `<x-ui.badge>` | 状態や種類の小さな札 | `tone=plain`（`plain` 枠だけ／`ink` 淡い紺／`accent` 深い赤：注意・外れ／`ok` 緑：当たり・済み）・中身（札の文字） | 部品の中：guide（practice-item の「準備中」・reasoning-item の手帳の段） |
| `<x-ui.confirm>` | 消す前の確認。押すと確認の文と「消す」ボタンが開く（JavaScript なしの `<details>`） | `action`（送り先）・`method=DELETE`・`label=消す`（最初に見せる文字）・`message=消すと元に戻せません。消しますか？`・`confirmLabel=消す`（本当に消すボタンの文字）・中身（一緒に送る hidden の項目） | やることを直す（「このやることを消す」・確かめるタスクなら「予想を取り下げる」） |
| `<x-ui.flash>` | お知らせ。受付で `->with('status', '…')`（うまくいった・緑）／`->with('error', '…')`（うまくいかなかった・赤）と渡すと出る | なし（骨組みが置く） | `<x-layouts.app>`・`<x-layouts.guest>` の中 |
| `<x-ui.privacy-note>` | 入力欄の下にいつも小さく出す個人情報の注意書き（`config('suiri.notes')`）。仕事の場面では施術者向けの一文も足す | `scene=null`（`Scene` か `'work'` などの文字） | 部品の中：task（form）・guide（scene-front のメモ） |
| `<x-ui.icon>` | 線で描いた小さな道具の絵（ナビ・設定・足す など）。段の絵は `<x-ui.step-icon>` | `name`（`sun` 今日・`check` タスク・`search` 事件・`book` 手引き・`loop` ふりかえり・`settings`・`plus`・`calendar`・`logout`・`folder`。知らない名前なら小さな丸）。`class` を省くと `size-6` | 部品の中：layouts（app・nav）・today（agenda・agenda-event・open-cases）・task（check・curious・quick-add）・guide（scene-front）・ui（big-button） |

## 3. 今日（`components/today/`）

今日の画面（`today/index`）の部品です。開いた瞬間に「今日やること」が1つだけ大きく見えるよう、上から「（やることを済ませた直後だけ）`<x-task.done-followup>` → いまやることのカード `<x-today.next-step>` → きょうの予定とやること `<x-today.agenda>`（中に、すばやく書く欄 `<x-task.quick-add>`）→ きょうの練習 `<x-today.practice-suggestion>`・捜査中の事件 `<x-today.open-cases>`（パソコンでは左右に並ぶ）」の順に置きます。答え合わせのカードを出している日は、ページの下に「新しく気になったことを書く」の小さなリンクを残します（画面のファイルの中）。

```text
今日（today/index）
├─ task.done-followup（やることを済ませた直後だけ）─ task.minutes・task.curious
├─ today.next-step ─ ui.step-card ─┬─ today.due-prediction（答え合わせの日が来た予想を1件）
│                                   └─ today.due-predictions ─ today.due-prediction（compact）
├─ today.agenda ─┬─ task.quick-add（「＋ 書く」で開く）
│                ├─ task.item ─ task.check・task.curious
│                └─ today.agenda-event ─ task.curious
├─ today.practice-suggestion
└─ today.open-cases ─ case.summary（compact）
```

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-today.next-step>` | 今日のいちばん上の「いまやることのカード」（1枚だけ）。答え合わせの日が来た予想があれば「答え合わせしよう」（いちばん古い1件と「答え合わせする」の大きなボタン。ほかは下に「ほかにも◯件あるよ」でたたむ）。なければ「気になったこと、ある？」（記録のきっかけの一文と「気になったことを書こう」。きっかけを決めていなければ、カードの下に設定へのさそい）。言葉は `config('suiri.today.answer')`・`config('suiri.today.record')` | `predictions=[]`（`Queries/Today/DuePredictionsQuery` が返す予想。事件 `caseFile` を読み込み済み・古い順）・`cue=null`（記録のきっかけ `users.cue`）。`class` などは中の `<x-ui.step-card>` に付く | 今日 |
| `<x-today.due-prediction>` | 答え合わせの日が来た予想1件の中身：事件のなぞ・「あなたの予想」の文（書いたときのまま）・書いた日と自信・確かめる日（今日なら「きょうが確かめる日」、過ぎていれば「確かめる日は◯月◯日だったよ」を赤で）と時刻 | `prediction`（事件 `caseFile` を読み込み済み）・`compact=false`（たたんだ一覧向けの小さな形。「あなたの予想」の見出しを省き、文を小さく） | `<x-today.next-step>`・`<x-today.due-predictions>` の中 |
| `<x-today.due-predictions>` | いちばん上のカードに出した1件のほかに、答え合わせを待っている予想の一覧。ふだんはたたみ（`<details>`）、開くと1件ずつ小さなカードで並び、押すとその予想の答え合わせへ | `predictions=[]`・`label`（たたんだときの一言。「ほかにも2件あるよ」） | `<x-today.next-step>` の中（カードの下の `later`） |
| `<x-today.agenda>` | 「きょうの予定とやること」。Google カレンダーの今日の予定と、今日が期日のやることを1つの並びにし、左に時刻（終日・「いつでも」は上）。見出しの横の「＋ 書く」で、すばやく書く欄が開く（最初は閉じておく `<details>`。入力にまちがいがあれば開いたまま）。予定もやることもなければ一言だけ。期日を過ぎたやることがあれば、下に「前からのやり残しが n 件あるよ」（一覧の「きょう」へ）。言葉は `config('suiri.today.agenda')` | `items=[]`（`Queries/Today/AgendaQuery` の並び。1件：`type`（`event`・`task`）・`title`・`starts_at`・`ends_at`・`all_day`・`task`・`just_ended`）・`overdue=0`（期日を過ぎた、まだ済んでいないやることの数）・中身（「＋ 書く」で開く欄＝`<x-task.quick-add>`） | 今日 |
| `<x-today.agenda-event>` | 予定とやることの並びの、Google カレンダーの予定の1行（読むだけ）。左にカレンダーの絵、題名と「予定・〜終わりの時刻」、右に「気になる」。終わって少したった予定（`just_ended`。提案 S-6）には「さっき終わった予定：気になったことは？」（`config('suiri.suggest.just_ended_label')`）を添え、「気になる」を赤い枠で目立たせる | `item`（`AgendaQuery` の1件のうち `type` が `event` のもの） | `<x-today.agenda>` の中 |
| `<x-today.practice-suggestion>` | きょうの練習。日替わりで1つ選んだ練習メニューの名前・短い説明（2行まで）・「やってみる」「くわしく」と「気が向いたときだけでOK」。やらなくてもよいことが伝わるよう、カードにせず下に小さく出す（急かす言葉は使わない） | `practice=null`（`Services/Guide/DailyPractice` が選んだ `practices.json` の1件。null なら何も出さない）・`kid=false`（子ども向けの説明 `kid_description` を出す） | 今日 |
| `<x-today.open-cases>` | 捜査中の事件（最近さわった順に数件）。見出しの下に「事件＝気になって、いま調べていること」の一言、右上に「ぜんぶ見る」。カードにせず下に小さく出す。1件の見た目は `<x-case.summary compact>`。事件がなければ一言だけ（書くボタンは上のカードにある） | `cases=[]`（`Queries/Cases/OpenCasesQuery::get($user)` を渡すと、手がかり・見立て・答え合わせ待ちの数も出る。件数は `config('suiri.today.open_cases')`） | 今日 |

## 4. タスク（`components/task/`）

タスクの一覧・やることを書く・やることを直すの画面と、今日の画面の「きょうの予定とやること」の行を受け持ちます。画面の言葉では「やること」と呼びます（ナビと一覧の上の帯は「タスク」）。予想から作った確かめるタスクは、丸を押して済ませられず、答え合わせで済みになります（判断は `Task::answeredByPrediction()` の1か所）。

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-task.item>` | やることの1行（高さ 64px 以上）。左の丸を1回押すと済み、題名を押すと直す画面へ。題名の下に、日にち（過ぎたものは赤で「10/30（金）までだったよ」、明日なら「明日」）・時刻・「30分の予定 → 45分かかった」を小さく1行。確かめるタスクは左に点線の丸と虫めがね、右に「答え合わせ」 | `task`・`date=true`（日にちを出す。今日のものは出さない）・`time=true`（何時からを出す）・`curious=false`（済んだふつうのやることの右に「気になる」を出す。まだのやることには出さない＝行の操作は丸の1つだけ）。今日の画面では `:date="false" :time="false" curious` | タスクの一覧・`<x-today.agenda>` の中 |
| `<x-task.check>` | 行の左の丸（済んだ・まだの切り替え）。押せる所は 48px 四方で、押すとその場でフォームを送る（JavaScript なし。済んだものを戻すときは DELETE）。確かめるタスクは押せない印（点線の丸と虫めがね。済めば緑のチェック）だけ | `task` | `<x-task.item>` の中 |
| `<x-task.curious>` | 「気になる」のボタン。予定・やることを出発点にした事件を作る画面へ進む。題名を URL に載せない（アクセスの記録に残さない）よう、POST `/cases/draft`（ルート名 `cases.draft`）に `origin`・`label` を隠し項目で送る小さなフォーム（JavaScript なし）。`cases.draft` がなければ何も出さない | `origin`（`task`＝やること・`event`＝予定）・`label`（事件のきっかけとして写す題名と時刻。長さは `config('suiri.limits.origin_label')` で切る）・`strong=false`（目立たせる。さっき終わった予定への声かけ S-6 で赤い枠にする） | `<x-task.item>`・`<x-today.agenda-event>`・`<x-task.done-followup>` の中 |
| `<x-task.done-followup>` | やることを済ませた直後に出す緑の枠（「『牛乳を買う』、できたね！」）。「実際は何分だった？」は、よく使う分（15分・30分・1時間）のボタンを1回押すだけで書ける（ボタンごとの小さなフォーム）か、ほかの分を欄に書く。書いたあとは「30分の予定で、45分かかったね」。下に「気になる」。どちらも任意で、何もしなくてよい。言葉は `config('suiri.tasks.done_followup')` | `task=null`（済ませたばかりの `Task`。`Queries/Tasks/TaskListQuery::justDone()`。null なら何も出さない） | 今日・タスクの一覧 |
| `<x-task.quick-add>` | すばやく書く欄。「何をする？」の1つだけで、Enter か「＋ 書く」で作る（期日は渡された日。空なら「いつか」）。下の「日にちや時間も決める」で、書いた題名を持ってやることを書く画面へ（POST `/tasks/draft`。同じフォームのボタンの `formaction` で送り先を替える） | `due=null`（期日 `'Y-m-d'`。null なら空＝いつか）・`back=null`（`'today'` で作ったあと今日の画面へ戻す） | タスクの一覧（いちばん上）・`<x-today.agenda>` の中（「＋ 書く」で開く） |
| `<x-task.form>` | やることを書く・直す画面の入力のまとまり。まず「何をする？」（題名）だけを大きく出し、いつやる？・何時から？・何分かかりそう？・メモは「くわしく決める」を開いたときに出す（`<details>`。閉じているときは、決めた日にち・時刻・予定の分を見出しの下に並べる）。「何分かかりそう？」のすぐ下に、題名の似た前のやることの実績。済んだものを直すときは「実際は何分だった？」も出す。確かめるタスクは題名（予想の文の写し）を直せず、答え合わせ待ちの予想のものは期日が必須（予想の確かめる日も一緒に変わる）。下に注意書きと横いっぱいの送るボタン | `task`（新しく作るときは保存前の `Task`）・`similar=[]`（`SimilarTasksQuery` の結果）・`action`（送り先）・`method=POST`・`submit`（送るボタンの文字。「これで書いておく」「これで直す」）・`back=null`（`'today'` なら作ったあと今日の画面へ戻す）・`open=false`（「くわしく決める」を最初から開く。入力にまちがいがあれば開く） | やることを書く・やることを直す |
| `<x-task.minutes-field>` | 「何分かかりそう？」「実際は何分だった？」の欄。よく使う分（`config('suiri.tasks.minute_choices')`）のボタンと、ほかの分を書く欄を横に並べる。ボタンは押すと欄にその数を入れて塗るだけ（`resources/js/parts/minute-chips.js`）。JavaScript が動かないときはボタンが隠れたままで、欄に書けば送れる | `name`（`duration_minutes` か `actual_minutes`）・`label`（問いの見出し）・`value=null`（送り直したときの入力 `old()` を優先） | `<x-task.form>` の中 |
| `<x-task.similar-history>` | 題名の似た前のやることの実績を一言で（「前の『買い物』は 30分の予定で、45分かかったよ（15分長かった）」）。予想時間の欄のすぐ下に置き、楽観しすぎる見積もりを防ぐ。見た目は緑の `<x-ui.hint>`。空なら何も出さない | `tasks`（`Queries/Tasks/SimilarTasksQuery` が返す、かかった時間のある済んだタスク。先頭がいちばん新しい＝「前の」） | `<x-task.form>` の中 |
| `<x-task.minutes>` | 分の数を読みやすい言葉にする（45 → 45分、90 → 1時間30分、60 → 1時間） | `value`（分の数） | `<x-task.item>`・`<x-task.done-followup>`・`<x-task.form>`・`<x-task.minutes-field>`・`<x-task.similar-history>` の中 |
| `<x-task.tabs>` | タスクの一覧のタブ（きょう／これから／いつか／済んだ。言葉と順番は `config('suiri.tasks.views')`）。いまのタブは太い下線と濃い文字、件数は小さな丸の札 | `current`（いま開いている一覧の種類）・`counts=[]`（種類ごとのまだ済んでいない件数。済んだものは数えない） | タスクの一覧 |

## 5. 事件（`components/case/`・`case-new/`・`case-step/`・`case-partner/`）

事件の一覧・事件を作る・事件の画面・答え合わせの4画面を受け持ちます。作り直しで、事件を作る画面は **1問ずつ**（`case-new`）、事件の画面は **いまやることのカードを1枚だけ** 出す形（`case-step`）、相棒モードは **1台のスマホを渡し合う** 形（`case-partner`）の部品に分けました。一覧と答え合わせは `case` の部品を作り直しています。`case` にはほかに、事件の画面の中で使う入力のまわりの小さな部品（5-6）があります。

```text
事件の一覧（cases/index）
├─ ui.empty-state（事件がまだ1つもないとき）
├─ ui.big-button（新しい事件を作る）
└─ case.list-filter（状態のつまみ・場面でしぼる）─ 中身：case.summary（1件ずつ）

事件を作る（cases/create-steps。1つのフォームに4つの問い。動きは resources/js/parts/stepper.js）
├─ case-new.mystery ────┐
├─ case-new.clue ───────┤ どれも case-new.step（丸の列）の中に ui.step-card を1枚
├─ case-new.hypothesis ─┤ 中で case-new.field・case-new.pick（─ case-new.pic）・case-new.work-note・
└─ case-new.confirm ────┘ case-new.partner-toggle・case.suggest-chips・case-new.next・case-new.moves・case-new.goto

事件の画面（cases/board）
├─ case-step.head（なぞ）
└─ case-step.desk（どのカードを出すかを決める）
   ├─ case-step.stages（進み具合）─ ui.progress・case-step.clue-pile・ui.done-summary
   ├─ case-step.now（いまやることのカードを1枚）
   │  ├─ case-step.clue-card・hypothesis-card・fit-card（─ fit-buttons）・predict-card（─ confidence・check-date）
   │  ├─ case-step.wait-card・resolve-card・close-card・closed-card
   │  ├─ case-step.edit-case-card・edit-clue-card・edit-hyps-card（─ hyp-row）
   │  ├─ case-partner.flow（相棒モードで見せ合う前）
   │  └─ どのカードも中に case-step.bubble（言葉の説明かワトソンのひとこと）
   ├─ case-step.more（ほかにできること）─ case.ai-copy
   └─ case-partner.flow（見せ合ったあとの見せ合い）

相棒の渡し合い（case-partner.flow）
├─ 見せ合う前：case-partner.turn ─┬─ case-partner.relay-progress・ui.handoff（渡す付箋）
│                                 ├─ case-partner.write-card（書く）
│                                 ├─ case-partner.mark-card（○ を付ける）─ case-partner.fit-buttons
│                                 └─ case-partner.together（いっしょに見せ合おう）─ case-partner.who
└─ 見せ合ったあと：case-partner.reveal ─ case-partner.side・case-partner.marks（─ case-partner.who）

答え合わせ（predictions/show）
├─ case.prediction-original（前に書いた予想・はんこ）
├─ case.resolve-form（答え合わせ待ち）
└─ case.resolve-result（済んだあと）
```

### 5-1. 事件の一覧

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-case.list-filter>` | 事件の一覧の **引き出しの枠**。上に状態のつまみ（捜査中・解決・おあずけ。件数つき。選んだつまみは下の紙とつながって白い）、下の紙に、その状態の一言（「おあずけ＝迷宮入り。…」など）と **中身（slot）** として事件の並びを入れる。紙のいちばん下に「場面でしぼる」（ふだんはたたむ。しぼっているときは開き、紙の上に「『親子』の事件だけ出しているよ」と「しぼるのをやめる」）。つまみも場面もリンクで、もう片方の選び方は残す | `status`（いまの状態。列挙 `CaseStatus`）・`scene=null`（いまの場面。null は「すべて」）・`counts=[]`（状態ごとの件数）・`kid=false`（子ども向けの言葉：つまみ 調べ中・わかった・おあずけ、場面 ふだん・おやこ・しごと）・中身（事件の並び `<ul>` と、空のときの一言・ページ送り） | 事件の一覧 |
| `<x-case.summary>` | 事件の一覧の1件（`<li>`）。上に「事件 No.12」と場面（相棒モードなら「相棒と」の絵）、大きくなぞ、下に段ごとの進み具合の小さな丸（なぞ → 見たこと → どうして？ → もしそうなら → 答え合わせ。済み＝塗る・いま＝段の色の枠・まだ＝うすい）と「次は『どうして？』」の一言（待っているとき「確かめる日を待っているよ」、閉じた事件は閉じた日）。確かめる日が来た予想があれば右上に赤い「答え合わせの日」の札。押すと事件の画面へ | `case`（数は `CaseListQuery::withCounts()` で数えたもの：`clues_count`・`hypotheses_count`・`pending_predictions_count`・`due_predictions_count`、あれば `resolved_predictions_count`）・`compact=false`（場面と日付を省いて短くする）・`kid=false`（場面を子ども向けの呼び名で出す） | 事件の一覧・`<x-today.open-cases>` の中（compact） |

### 5-2. 事件を作る（`components/case-new/`）

1つのフォーム（`cases.store`）の中に4つの問いを持ち、`resources/js/parts/stepper.js` がいまの問いだけを見せます。JavaScript が動かないときは全部の問いが縦に並び、そのまま送れます（「つぎへ」「もどる」と丸の列は、フォームに `data-stepping` が付いたときだけ見える）。場面「仕事」・相棒モードの切り替えは CSS の `:has`（フォームに `class="group/new"`）。`draft` は `Queries/Cases/NewCaseDraft::for()` の戻り値です。

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-case-new.mystery>` | 1問目「何が気になった？」：なぞ（必須）・どんなときのこと？（日常・親子・仕事の絵の札）・相棒モード（相棒の名前がある人だけ）。タスク・予定の「気になる」から来たら、きっかけを小さく出す。練習メニューから来たら、例の行で問いかけが入っていることを知らせ、カードの下に「練習のしかたを読む」 | `draft`・`kid=false`（場面を子ども向けの呼び名に） | 事件を作る |
| `<x-case-new.clue>` | 2問目「見たこと・聞いたことを1つ教えて」：吹き出し（手がかり＝…）・どうやって知った？（見た・聞いた・なかった の絵の札）・書き出しのヒント（提案 S-1。たたむ）・欄。書かずに進める。場面「仕事」を選ぶと、札と言い方・ヒントが仕事向けに替わり、吹き出しの場所に施術者向けの注意を出す | `draft`・`kid=false`（札の説明を子ども向けに） | 事件を作る |
| `<x-case-new.hypothesis>` | 3問目「どうしてだと思う？」：上に2問目の見たことを写す・吹き出し（見立て＝…）・考えるヒント（別の説明の型。提案 S-2。たたむ）・欄。書かずに進める。相棒モードを入れると、欄の代わりに「事件を作ったあとに書くよ」の説明 | `draft`・`kid=false`（見立てを「かも」と呼ぶ） | 事件を作る |
| `<x-case-new.confirm>` | 4問目「これで事件を作るよ」：なぞ（事件の題）と場面、見たこと・見立ての荷札（中身は stepper.js が写す）、札ごとの「直す」、「＋ 事件を作る」。JavaScript がないときは札を出さずボタンだけ | `draft`・`kid=false` | 事件を作る |
| `<x-case-new.step>` | 1問の入れ物。上に進み具合の丸4つ（なぞ・見たこと・どうして？・作る。名前は `config('suiri.case_new.steps')`。JavaScript で1問ずつのときだけ見せる）、下に中身。`data-step` の印を持つ | `n`（何番目の問いか。1〜4）・中身（`<x-ui.step-card>`） | `<x-case-new.mystery>`・`clue`・`hypothesis`・`confirm` の中 |
| `<x-case-new.field>` | 作る画面の入力欄（見出し・欄・下の一言・エラー）。`rows` があれば複数行。id は `f-new-{name}`（書き出しのボタンの入れ先）。エラーには `data-error`（stepper.js がその問いから見せる） | `name`・`value=null`・`rows=null`・`tip=null`（欄の下の一言）・`required=false`・`quiet=false`（見出しを読み上げだけにする）・中身（見出し）・スロット `above`（欄の上に置くもの。書き出しのボタン）。ほかの属性は欄に付く | `<x-case-new.mystery>`・`clue`・`hypothesis` の中 |
| `<x-case-new.pick>` | 絵つきの大きな選ぶ札（ラジオ1つ。高さ 96px）。選ぶと枠と面が段の色になり、右上にチェックの丸。`data-label` に言葉を持たせ、確かめの札に写す | `name`・`value`・`checked=false`・`pic`（`<x-case-new.pic>` の名前）・`label`・`desc=null`（一言の説明）・`tone=plain`（`plain`・`clue`）・`workLabel=null`（場面「仕事」を選んだときの言い方） | `<x-case-new.mystery>`（場面）・`<x-case-new.clue>`（手がかりの種類）の中 |
| `<x-case-new.pic>` | 選ぶ札の絵（手で描いた線の SVG）。日常＝家・親子＝大人と子ども・仕事＝かばん・見た＝目・聞いた＝耳・なかった＝点線の箱 | `name`（`daily`・`family`・`work`・`seen`・`heard`・`absent`） | `<x-case-new.pick>` の中 |
| `<x-case-new.partner-toggle>` | 「長男といっしょに考える？」の切り替え（チェック1つ。送る名前は `partner_mode`）。行のどこを押しても切り替わる。入れると3問目の欄が説明に替わる（目印 `data-partner`） | `partner`（設定の相棒の名前） | `<x-case-new.mystery>` の中 |
| `<x-case-new.work-note>` | 場面「仕事」を選んだときだけ出す、やさしい一言の注意（お客さまの名前や病名を書かない）。初めて仕事の場面で作る人には約束の全文、2回目からは短い注意 | `first=false`（仕事の場面の事件がまだ1つもない人） | `<x-case-new.mystery>`・`<x-case-new.clue>` の中 |
| `<x-case-new.next>` | 「つぎへ」の横いっぱいのボタン。次の問いへ進むだけで送信しない | `tone=primary`（`<x-ui.big-button>` の色） | `<x-case-new.mystery>`・`clue`・`hypothesis` の中 |
| `<x-case-new.moves>` | カードの下の小さな1行：左に「‹ もどる」、右に「あとで書く ›」（書かずに次へ） | `skip=false`（「あとで書く」も出す） | `<x-case-new.clue>`・`hypothesis`・`confirm` の中 |
| `<x-case-new.goto>` | 確かめの札の右の小さな「直す」。押すとその問いへ戻る | `n`（戻る問いの番号） | `<x-case-new.confirm>` の中 |

### 5-3. 事件の画面（`components/case-step/`）

画面 `cases/board` は `<x-case-step.head>` と `<x-case-step.desk>` を置くだけで、値は `Queries/Cases/CaseBoardQuery` がまとめて組み立てます。カードのつまみの「ステップ n / 5」は、いまの一手のカードだけに付けます（`number`。ほかにできることから開いたカードは null）。カードの中のフォームは、同じカードに戻りたいときに隠し項目 `back_open` を送ります（`config('suiri.case_board.back_open')` の値だけ受け付ける）。言葉は `config('suiri.case_board')`（親子の人には `case_board.kid`）。

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-case-step.head>` | いちばん上：なぞ（アプリで明朝体はここだけ）と、あるときだけ小さな1行（日常以外の場面・相棒モード・きっかけ）。事件の番号は上の帯に出す | `case`・`kid=false` | 事件の画面 |
| `<x-case-step.desk>` | 机の上：どのカードを出すかを決め、進み具合・カード・ほかにできることを並べる。順は ① 閉じた事件 → ② 見立てにかかわらない `?open=`（`edit-case`・`edit-clue-{id}`・`add-clue`・`close`）→ ③ 相棒の番 → ④ 見立てにかかわる `?open=`（`edit-hyps`・`add-hypothesis`・`fit-{id}`・`predict-{id}`）→ ⑤ 予想の段でまだ ○× のない手がかりがあれば ○× → ⑥ いまの一手。スマホは上から 進み具合 → カード → ほかにできること（閉じた事件は進み具合を下へ、相棒の番の間は進み具合を出さない）。パソコンはカードを左の広い列、進み具合を右の細い列に。見せ合ったあとは、下に「見せ合い（ちがい n つ）」 | `CaseBoardQuery` の値をそのまま：`case`・`hypotheses`・`hiddenCount=0`・`turn=null`・`partnerName=null`・`selfName=null`・`kid=false`・`step`（`NextStep` の結果）・`watson=null`・`board=null`（見せ合いの並び）・`suggest=[]`・`atticOptions=[]`・`prompts=null`・`open=null`・`today=null`・`checkDefault=null`・`calendarConnected=false` | 事件の画面 |
| `<x-case-step.stages>` | 進み具合と済んだ段の要約。スマホは横の丸5つ（`<x-ui.progress>`）と、中身のある段の要約（見たこと＝荷札の束、どうして？・もしそうなら＝`<x-ui.done-summary>`）。いまの段に「・いまここ」。パソコンは5段を縦に並べた目次 | `case`・`hypotheses`（いま見えている見立て）・`hiddenCount=0`・`turn=null`・`partnerName=null`・`stage=null`（いまの段。0 から。null なら全部済み）・`tone=null`（いまの段の色を変えるとき。待つ間は `check`）・`kid=false` | `<x-case-step.desk>` の中 |
| `<x-case-step.clue-pile>` | 集めた見たことの束。たたむと「見たこと 3つ」の1行、開くと番号つきの荷札（`<x-ui.evidence-tag>`）。捜査中なら札の右に「直す」（`?open=edit-clue-{id}`） | `case`（`clues` を読み込んだもの）・`kid=false`・`title`（たたんだ1行の言葉）・`open=false` | `<x-case-step.stages>` の中 |
| `<x-case-step.now>` | いまやることのカードを1枚だけ出す（どれかは desk が決めて `card` で渡す）。保存した直後の警告（ほかの人がわかる情報らしきもの）だけはカードの上に赤い吹き出しで、ふだんのワトソンのひとことはカードの中に | `card`（`add_clue`・`add-clue`・`add_hypothesis`・`add-hypothesis`・`all_eliminated`・`fit`・`predict`・`wait`・`resolve`・`close`・`closed`・`partner`・`edit-case`・`edit-clue`・`edit-hyps`）・`case`・`step`・`hypotheses`・`target=null`（対象の見立て）・`editClue=null`・`hiddenCount=0`・`turn=null`・`partnerName=null`・`selfName=null`・`kid=false`・`watson=null`・`suggest=[]`・`atticOptions=[]`・`prompts=null`・`today=null`・`checkDefault=null`・`calendarConnected=false`・`number=null`（つまみの番号） | `<x-case-step.desk>` の中 |
| `<x-case-step.bubble>` | カードに添える吹き出し（1つだけ）。ワトソンのひとことがあればそれ（問いの右に × 。閉じた規則はその事件で出さない。説明は「くわしく」にたたむ）、なければ段の言葉の一言の説明。警告は深い赤で説明を開いたまま。フォームの中に置かない | `note=null`（`WatsonNotes::forCase()` の値）・`case`・`explain=null`（言葉の一言の説明） | `<x-case-step.now>` と各カードの中 |
| `<x-case-step.clue-card>` | 「見たことを1つ教えて」（`clues.store`）。どうやって知った？（`<x-ui.choices>`）→ 見たこと → 書き出し（S-1。たたむ）→「これでOK」。仕事の場面では欄の上に `<x-case.work-caution>` | `case`・`kid=false`・`more=false`（「見たことを、もう1つ教えて」として出し、下に「もどる」）・`number=null`・`total=null`・`note=null`・`prompt=null`（練習メニューの問いかけ。例の代わり）・`questions=[]`（S-1 の候補） | `<x-case-step.now>` の中 |
| `<x-case-step.hypothesis-card>` | 「どうしてだと思う？」（`hypotheses.store`）。欄の下に、ほかの説明の型（S-2。いつも1行にたたみ、見立てが少ないうちは見出しで「押すと書き出しが入る」と知らせる）と前にわかったこと（S-5） | `case`・`kid=false`・`variant=first`（`first`・`more` ほかの理由もありそう？・`eliminated` 全部ちがったとき）・`number=null`・`total=null`・`note=null`・`prompt=null`・`types=[]`・`emphasize=false`・`attic=[]` | `<x-case-step.now>` の中 |
| `<x-case-step.fit-card>` | 「この見立てに合う？」（○× の段。任意）。見立ての引用と「とばす」、荷札ごとに大きな ○・×。× があれば「ちがった にする」を勧める。主なボタン「次へ：もしそうならを考える」（`?open=predict-{id}`）。カードの id は `hyp-{id}` | `case`（`clues` を読み込んだもの）・`hypothesis`（`fits` を読み込んだもの）・`kid=false`・`number=null`・`total=null`・`note=null` | `<x-case-step.now>` の中 |
| `<x-case-step.fit-buttons>` | 見たこと1つの ○（合う）・×（合わない）の大きなボタン2つ（48px）。押すと印を付け（`fits.update`）、もう一度押すと外す（`fits.destroy`）。いまの印は塗る（○ は緑、× は深い赤） | `hypothesis`・`clue`・`fit=null`（いまの印 `Fit`）・`back=null`（送ったあと戻るカード。`back_open` の値） | `<x-case-step.fit-card>` の中 |
| `<x-case-step.predict-card>` | 「もしそうなら、何が見える？」（`predictions.store`）。1問ずつ：① 見えるはずのこと（「型を使う」＝S-3）→ 書くと ② どのくらい自信ある？ → 選ぶと ③ いつ確かめる？と「時刻や、前にわかったことも使う」と「保存すると、この文と自信は直せないよ」（CSS の `:has` で隠す）。主なボタン「この日に確かめる」はいつも下 | `case`・`hypothesis`・`kid=false`・`number=null`・`total=null`・`note=null`・`prompt=null`・`template=null`（穴埋めの文）・`dates=[]`（S-4）・`attic=[]`（S-5）・`atticOptions=[]`（選べる知識）・`today=null`・`checkDefault=null`（初めの日付＝明日） | `<x-case-step.now>` の中 |
| `<x-case-step.confidence>` | 「どのくらい自信ある？」の大きな選択（50〜95％の5刻みの10枚。100％は置かない。札に数と目安の言葉）。初めはどれも選ばない | `label`・`kid=false`（目安の言葉を子ども向けに） | `<x-case-step.predict-card>` の中 |
| `<x-case-step.check-date>` | 「いつ確かめる？」の大きな日付と、明日・3日後・1週間後・「次の『◯◯』の日」のボタン（押すと日付と時刻が入るだけ。動きは `suggest-chips.js`） | `label`・`dates=[]`（`CheckDateSuggestionsQuery` の値）・`today=null`・`checkDefault=null` | `<x-case-step.predict-card>` の中 |
| `<x-case-step.wait-card>` | 「◯月◯日に確かめよう」（待つ段）。カレンダーの紙のような大きな日付と予想の文・自信、「カレンダーに入れたよ」か「やることに入れたよ」。下に小さく「先に答え合わせをする」「取り下げる」（確かめてから） | `case`・`prediction`・`kid=false`・`number=null`・`total=null`・`note=null`・`calendarConnected=false` | `<x-case-step.now>` の中 |
| `<x-case-step.resolve-card>` | 「答え合わせをしよう」（確かめる日が来た）。前に書いた予想の文・書いた日・自信をそのまま見せ、主なボタンで答え合わせの画面へ | `case`・`prediction`・`kid=false`・`number=null`・`total=null`・`note=null` | `<x-case-step.now>` の中 |
| `<x-case-step.close-card>` | 「事件を閉じる？」（`cases.close`）。「解決！」「おあずけ」の大きな2枚と、わかったことを一言（なくてもOK）。「つまり？」は閉じたあとに聞く | `case`・`kid=false`・`suggested=false`（いまの一手が「閉じる」のとき。そうでなければ下に「もどる」）・`note=null` | `<x-case-step.now>` の中 |
| `<x-case-step.closed-card>` | 閉じた事件の中心：「解決！」か「おあずけ」の大きなはんこと閉じた日・結論。下に「つまり、何がわかった？」（`attic.store`。書き始めると「ほかに当てはまりそうなことは？」）。もう棚にしまったことがあれば、それを見せる。まだ答え合わせしていない予想があれば入口も | `case`（`atticNotes` を読み込んであれば使う）・`kid=false`・`note=null` | `<x-case-step.now>` の中 |
| `<x-case-step.edit-case-card>` | 「なぞを直す」（`cases.update`）。なぞと場面、「これで直す」、下に「事件を消す」（確かめてから）と「もどる」 | `case`・`kid=false` | `<x-case-step.now>` の中 |
| `<x-case-step.edit-clue-card>` | 「見たことを直す」（`clues.update`）。種類と本文、「これで直す」、下に「この見たことを消す」（確かめてから。付けた ○× も消える）と「もどる」 | `case`・`clue`・`kid=false` | `<x-case-step.now>` の中 |
| `<x-case-step.edit-hyps-card>` | 「見立てを見直そう」。見立てごとに `<x-case-step.hyp-row>`、下に「もどる」 | `case`・`hypotheses`（いま見えている見立て）・`partnerName=null`・`kid=false` | `<x-case-step.now>` の中 |
| `<x-case-step.hyp-row>` | 見直すカードの見立て1つ：本文（ちがった見立ては線で消す）・書いた人（相棒モード）・状態の大きなボタン（`hypotheses.update`。考えを変えた記録が残る）・「○× をつける」・「もしそうならを立てる」・「直す」（本文を直す・消す） | `hypothesis`（`fits` を読み込んだもの）・`case`（`clues` を読み込んだもの）・`partnerName=null`・`kid=false` | `<x-case-step.edit-hyps-card>` の中 |
| `<x-case-step.more>` | 「ほかにできること」：カードの下に小さく1行ずつ。押すとその用事がカードになる（`?open=…`）。捜査中：見たことをもう1つ・見立てをもう1つ・AI に相談する用に写す（`<x-case.ai-copy>`）・見立てを見直す・なぞを直す・事件を閉じる。相棒の番の間は見立てにかかわる行を出さない。閉じた事件：なぞを直す・事件を開き直す。いま出ているカードと同じ用事の行は出さない | `card`・`case`・`hypotheses`・`turn=null`・`board=null`・`kid=false` | `<x-case-step.desk>` の中 |

### 5-4. 相棒の渡し合い（`components/case-partner/`）

入口は `<x-case-partner.flow>` だけで、事件の画面の `<x-case-step.now>`（見せ合う前）と `<x-case-step.desk>`（見せ合ったあと）が置きます。見せ合う前は、いまの番の人の見立てだけを出し、もう片方の文は HTML にも入れません。同じページの中の段は、印（`#relay-partner-write` など）へのリンクと CSS の `:target` で切り替えるので、JavaScript なしで動きます。2人の呼び名 `names` は `['partner' => '長男さん', 'self' => '父さん']` の形（相棒の名前と表示名に「さん」。なければ「相棒」「あなた」）、`word` は見立ての呼び名（親子の人は「かも」）です。言葉は `config('suiri.case_partner')`。

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-case-partner.flow>` | 渡し合いの入口。見せ合ったあとなら見せ合い（`reveal`）を、見せ合う前で捜査中ならいまの番（`turn`）を出す。相棒モードでない事件では何も出さない。日本語を文節で折り返す | `case`（`clues`・`hypotheses.fits` を読み込んだもの）・`board=null`（`RevealBoard::build()` の値。見せ合う前は null）・`partnerName=null`・`turn=null`（`partner`・`self`）・`selfName=null`・`kid=false` | `<x-case-step.now>`・`<x-case-step.desk>` の中 |
| `<x-case-partner.turn>` | 1人の番。まだ書いていなければ「スマホを渡してね」の付箋、書いたあとは ○ を付けるカード（自分の番なら「いっしょに見せ合おう！」のカードも置いておく）。書いた文の送り直し（エラー）のときは書く欄を初めから開く | `case`・`who`（`partner`・`self`）・`names`・`word`・`kid=false` | `<x-case-partner.flow>` の中 |
| `<x-case-partner.relay-progress>` | 渡し合いの進み具合（丸3つ：相棒の番 → 自分の番 → 見せ合う） | `at`（`partner`・`self`・`reveal`）・`names`・`partnerDone=false`（相棒がもう書いたか） | `<x-case-partner.turn>` の中 |
| `<x-case-partner.write-card>` | 「どうしてだと思う？」を書くカード。なぞと手がかりの荷札（読むだけ）を見ながら、その人の見立てを1つ書く（`hypotheses.store`。`author` にいまの番） | `case`・`who`・`names`・`word`・`kid=false`・`step=1`（つまみの番号）・`cancel=null`（やめて ○ のカードに戻るリンク）・`skip=null`（書かずに次の人の番へ進むリンク） | `<x-case-partner.turn>` の中 |
| `<x-case-partner.mark-card>` | 書いたあとのカード「合う手がかりに ○ を付けよう」。いま書いた見立てと、荷札ごとの ○・×（付けなくても進める）。主なボタン（渡す・よぶ）は中身で受け取る | `case`・`who`・`names`・`word`・`kid=false`・`step=1`・`hypotheses`（その人の見立て。○× は最後に書いたもの）・`more`（「ほかの理由も書く」の行き先）・中身（主なボタン） | `<x-case-partner.turn>` の中 |
| `<x-case-partner.fit-buttons>` | 荷札の右の大きな ○・×（直径 48px）。押すと印を付け、もう一度押すと外す | `hypothesis`（`fits` を読み込んだもの）・`clue` | `<x-case-partner.mark-card>` の中 |
| `<x-case-partner.together>` | 「2人とも書けたね！」のカード。2人が何個書いたか（文は見せない）と「いっしょに見せ合おう！」（`cases.reveal`）。相棒がまだなら「まだ」と、相棒の番に戻るリンク | `case`・`names`・`partnerCount=0`・`selfCount=0`・`more`（「もう1つ書く」の行き先）・`partnerHref`（相棒の番の住所） | `<x-case-partner.turn>` の中 |
| `<x-case-partner.reveal>` | 見せ合い。2人の見立てを並べ、手がかりの荷札ごとに2人の ○× の印。付け方が分かれた荷札はからし色で「見方が分かれたよ」。いちばん下に「『どうしてそこに ○ を付けたの？』と聞いてみよう」。`id="reveal"` は見せ合った直後に戻る場所 | `case`・`board`（`partner`・`self`・`rows`）・`names`・`word`・`kid=false` | `<x-case-partner.flow>` の中 |
| `<x-case-partner.side>` | 見せ合いの1人分の見立ての欄（丸い印・呼び名・番号つきの見立て。番号は荷札の印の数字と同じ） | `who`・`name`・`word`・`hypotheses` | `<x-case-partner.reveal>` の中 |
| `<x-case-partner.marks>` | 荷札に付ける1人分の印（丸い印と「○1・2 ×3」。なければ「印なし」。見立てが1つなら番号を省く） | `who`・`name`・`marks`（`['fits' => [1], 'contradicts' => []]` の形）・`numbered=true` | `<x-case-partner.reveal>` の中 |
| `<x-case-partner.who>` | だれのものかの丸い印（呼び名の1文字目。相棒はからし色、自分は紺） | `who`・`name`・`size=md`（`md` 直径 32px・`sm` 24px） | `<x-case-partner.marks>`・`side`・`together` の中 |

### 5-5. 答え合わせ

画面 `predictions/show` の部品です。`kid`（子ども向けの言葉）は、受付から渡されなければ画面がふだんの場面が親子の人かで決めて渡します。

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-case.prediction-original>` | 前に書いた予想（要件 C-7）。後知恵を防ぐため、「この予想を書いたのは 10月30日（金）21:30だよ」と予想の文を大きく見せ、「あとから変えられないよ」と添える。自信・書いた日時（年まで）・確かめる日・見立て・使った屋根裏の知識は「くわしく」にたたむ。答え合わせが済んだら右上に「確かめた」のはんこ（`<x-ui.stamp>`） | `prediction`（`hypothesis`・`atticNote` を読み込んだもの）・`case`・`kid=false`（はんこの「あたり」、自信の目安「けっこう 自信がある」など） | 答え合わせ |
| `<x-case.resolve-form>` | 「実際はどうだった？」のカード（`id="resolve-form"`）。4つの大きなボタン（当たり・外れ・どちらとも言えない・前提が崩れた）は、はんこと同じ色と輪の形で、選ぶとその色で塗る。外れのときだけ「どこで見落とした？」、どれかを選んだら「ひとことメモ」（CSS の `:has`。送り直したときも選んだまま）。保存すると確かめるタスクが済みになる。下に個人情報の注意書き | `prediction`・`scene=null`（仕事の場面の言い方）・`kid=false`（あたり・はずれ、どこでまちがえた？ など） | 答え合わせ |
| `<x-case.resolve-result>` | 済んだあと（はんこは上の `prediction-original` に押してある）。確かめた日・どこで見落としたか・メモ、外れならノーベリ帳に入ったことと「ノーベリ帳を開く」。その下に次の一手を1つだけ大きく：当たり→「見立てを『当たり』にする？」、外れ→「『消えた』にする？」（押すと考えを変えた記録が残る）、どちらとも言えない・前提が崩れた→「もう一度、予想してみる？」、どれでもなければ「事件にもどる」 | `prediction`（`hypothesis` を読み込んだもの）・`case`・`kid=false`（「かも」を「あたり」にする？ など） | 答え合わせ |

### 5-6. 入力のまわりの小さな部品

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-case.textarea>` | 事件の画面で使う複数行の入力欄。1つの画面に同じ名前の欄（`body` など）を持つフォームがいくつもあるので、`formKey` が合うフォームから送り直したときだけ、入力の残り（`old()`）とエラーを出す | `name`・`label=null`・`formKey=null`・`value=null`・`hint=null`・`rows=3`・`required=false`・スロット `caption`（`label` の代わりの見出し。場面で言葉を出し分けるとき）・スロット `above`（見出しと入力欄のあいだに置くもの。書き出しの提案のボタン）。ほかの属性は入力欄に付く | `<x-case-step.hyp-row>`（本文を直す）・`<x-case-partner.write-card>` の中 |
| `<x-case.work-caution>` | 仕事の場面で使うときの注意（お客さまの記録には使えない。文は `config('suiri.cases.work_caution')`・`notes.privacy_work`） | `first=false`（初めて仕事の場面の事件を作るとき。目立つ枠で全文を出す。それ以外は入力欄の上の短い注意）・`toggle=false`（同じフォームで場面「仕事」を選んだときだけ見せる。フォームに `class="group/new"` が要る） | `<x-case-step.clue-card>` の中（仕事の場面の事件） |
| `<x-case.suggest-chips>` | 書き出しの提案（AI なし。requirements.md の 4-10）のボタンの並び。押すと同じフォームの欄に書き出し・種類・確かめる日と時刻・使う屋根裏の知識が入るだけで、保存は本人がする（動きは `resources/js/parts/suggest-chips.js`。JavaScript がなくてもフォームはふつうに送れる）。候補は props で受け取るだけ。空なら何も出さない | `items=[]`（1件：`label`・`hint`（任意）と、`text`・`kind`・`date`・`time`・`attic` のどれか。`App\Queries\Cases\SuggestionsQuery` の値）・`target=null`（書き出しを入れる入力欄の id）・`caption=null`（頭の小さな見出し）・`folded=false`（見出しの1行にたたむ） | `<x-case-new.clue>`（S-1。folded）・`<x-case-new.hypothesis>`（S-2。folded）・`<x-case-step.clue-card>`（S-1）・`<x-case-step.hypothesis-card>`（S-2・S-5）・`<x-case-step.predict-card>`（S-3 の「型を使う」・S-5）の中 |
| `<x-case.ai-copy>` | 「AI に相談する用に写す」（要件 4-11 の M-9）。押すと、なぞ・手がかり・見立て（○× と予想つき）と頼み方の文（正誤は判定しないで・名前などを書かないで）を端末のクリップボードに写し、「写したよ。ChatGPT に貼ってね」と出す。アプリからは何も送らない。仕事の場面の事件では何も出さない。相棒モードで見せ合う前は見立てを写さない。JavaScript が動かないときはボタンを隠し、「写す文を見る」を開いて手で写す。文は `App\Support\Ai\ConsultText`、言葉は `config('ai_connect.copy')`、動きは `resources/js/parts/ai-copy.js` | `case`（手がかり・見立ての ○× と予想を読み込んだもの）・`board=null`（見せ合いの表。いまは使わない） | 事件の詳細（`<x-case-step.more>` の「あとでできること」の中） |

## 6. 手引き（`components/guide/`）

手引きの画面（入口・わざ・名推理カードの一覧・名推理カード（表・裏）・練習メニュー・3つの考え方）を受け持ちます。教材の中身は `src/database/data/guide/*.json`（`GuideLibrary` で読む）の1件を、そのまま受け取って並べます。手引きの中で「別の話」を区切るときは、段の色のつまみが付いた紙 `<x-guide.section>` を使い、名推理カードは表・裏・入口の見本のどれも二重の枠のカードの形 `<x-guide.card-face>` にそろえます。

```text
手引きの入口（guide/index）：guide.play-card ─ guide.card-face → guide.entry-list → guide.flow-steps
名推理カードの一覧（guide/scenes/index）：guide.tabs → ui.big-button → guide.scene-item ─ guide.stamp
名推理カード（guide/scenes/show）：表 guide.scene-front ／ 裏 guide.scene-back → ui.big-button → guide.pager
わざ（guide/skills/show）：guide.tabs → guide.audience-toggle → guide.quote → guide.skill-practice → guide.practice-item・guide.scene-item → guide.pager
練習メニュー（guide/practices/index）：guide.tabs → 名前のボタン → guide.practice-item
3つの考え方（guide/reasoning）：guide.tabs → guide.audience-toggle → guide.rain-analogy → guide.reasoning-item → ui.hint
```

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-guide.tabs>` | 手引きの中の行き先（入口・名推理カード・練習・3つの考え方。言葉は `config('suiri.guide.tabs')`）。いまいる所は赤い下線。幅が足りない端末では横にすべらせて見る。入口（行き先の一覧そのものが案内）と名推理カード1枚の画面（カードと「裏返す」を最初の画面に収める）には置かない | `current=null`（いまいる行き先のルート名。わざの画面は `guide.index` を渡す） | わざ・名推理カードの一覧・練習メニュー・3つの考え方 |
| `<x-guide.play-card>` | 手引きの入口でいちばん大きく出す「名推理カードで遊ぼう」。見出しと遊び方の一言、次に遊ぶカード（まだ推理を書いていない最初の1枚）の見本（題・作品名・表の書き出しを3行）、そのカードの表へ進む大きなボタン、下に小さく「ほかのカードを選ぶ（全部で23枚）」 | `play`（`overview.json` の `play`：見出し・一言・ボタンの言葉）・`scene=null`（次に遊ぶカード。なければボタンは一覧へ）・`number=null`（そのカードが何枚目か）・`total=0`（カードの枚数）・`fresh=true`（まだ書いていないカードか。全部書いて最初のカードに戻ったときは false で、右上の札が「はじめのカード」になる） | 手引きの入口 |
| `<x-guide.entry-list>` | 入口の、名推理カードの次の行き先：今日の練習・ホームズの10のわざ・3つの考え方を、絵・名前・一言の行で並べる（押すとその場所へ）。今日の練習の行は、その日の練習の場所へ飛び、一言に練習の名前を入れる | `entries`（`overview.json` の `entries`：`key`・`route`・`anchor`・`icon`・`name`・`description`・`description_none`）・`practice=null`（`Services/Guide/DailyPractice` が選んだ今日の練習） | 手引きの入口 |
| `<x-guide.flow-steps>` | 「ホームズの10のわざ」：手帳の段（手がかり：見た／聞いた／なかった・見立て・合う合わない・もしそうなら・答え合わせ・原因をさかのぼる・屋根裏の棚・ノーベリ帳）ごとに、段の色の丸の絵・名前・一言の説明（初めて出る言葉の言い換え）・練習できるわざ（押すとわざの画面へ）・原作の場面（押すとカードへ）。段によっては考え方（アブダクション・演繹・帰納）の札が付く（押すと3つの考え方の画面へ）。requirements.md の 3-3 の対応表を画面にしたもの | `steps`（`overview.json` の `steps`：`step`・`icon`・`tone`・`note`・`skills`・`example`・`scene`・`reasoning`）・`skills`・`scenes`（キーで引ける技能・カード）・`reasoningNames=[]`（考え方のキーと名前）・`kid=false`（わざの子ども向けの名前を先に出す） | 手引きの入口 |
| `<x-guide.card-face>` | 名推理カードの紙の形：外の太い枠と内の細い枠の二重の枠、左上にカードの番号（No.07 / 23）、右上に「表」「裏」。表・裏・入口の見本のどれもこの形にして、「これはカード」とひと目でわかるようにする | `number=null`（何枚目か）・`total=null`（全部で何枚か）・`side=front`（`front` 白い紙／`back` 淡い紺）・スロット `corner`（右上の「表」「裏」の代わりに置くもの。はんこなど）・中身 | `<x-guide.play-card>`・`<x-guide.scene-front>`・`<x-guide.scene-back>` の中 |
| `<x-guide.scene-item>` | 名推理カードの一覧の1行（番号・題・作品名と章）。ホームズが外した場面には赤いはんこ、自分の推理を書いたカードには緑のはんこ。行のどこを押してもカードの表へ進む | `scene`（`scenes.json` の1件。表の欄だけでよい）・`number=null`（並びの何枚目か）・`done=false`（自分の推理を書いたか） | 名推理カードの一覧・わざ（このわざが出てくるカード） |
| `<x-guide.stamp>` | カードに押す小さなはんこ（「推理を書いた」「ホームズが外した」）。丸い枠と少しの傾きで、札とは違う「押した印」に見せる | `tone=ok`（`ok` 緑：推理を書いた／`accent` 深い赤：ホームズが外した）・中身（はんこの文字） | `<x-guide.scene-item>`・`<x-guide.scene-front>`・`<x-guide.scene-back>` の中 |
| `<x-guide.scene-front>` | 名推理カードの表：大きな問い「この手がかりから、何がわかる？」とひとこと（親子の人には「まずは声に出して言ってみよう」）→ カード（題・作品名・手がかり。外した場面にははんこ）→ 大きな「裏返す」→ ヒント（たたむ。親子の人には開いておく）→ たたんだ「考えをメモしておく（なくてもOK）」（欄・「メモして裏返す」・注意書き）→ 小さく一覧へもどるリンク。メモが空なら保存せずに裏へ進む。ホームズの推理はここに出さない（裏は別のページで、受付が表の欄だけを渡す）。前に書いたメモがあれば、欄に入れて開いておく（保存すると書きかわる）。言葉は `config('suiri.guide.front')` | `scene`（`scenes.json` の1件。表の欄だけ）・`attempt=null`（前に書いた推理 `SceneAttempt`）・`kid=false`（「声に出して」のひとことと、ヒントを開いておく）・`number=null`・`total=null` | 名推理カード（表） |
| `<x-guide.scene-back>` | 名推理カードの裏：たたんだ「表の手がかりをもう一度読む」→ あなたの考え（からし色の付箋。書いた日と「表にもどって直す」。書かずに裏返したときは書くさそい）→ カードの裏「ホームズはこう考えた」（見えたこと・考えたこと・だから を段の絵と色で。外した場面には赤いはんこと、外れから直すことを伝えるひとこと）→ どうやって確かめた？ → この場面で練習できるわざ → 原作の英語の引用。言葉は `config('suiri.guide.back')` | `scene`（`scenes.json` の1件。全部の欄）・`attempt=null`（自分の推理）・`steps=[]`（ホームズの推理を事実・推理・結論に分けたもの。`ReasoningSteps::split`）・`skillNames=[]`・`mistakeNote=''`（ホームズが外した場面に添えるひとこと。`overview.json` の `mistake_note`）・`number=null`・`total=null` | 名推理カード（裏） |
| `<x-guide.pager>` | 前と次へのリンク（わざ S1→S10、名推理カードの並び）。端では片方だけ出す。言葉は `config('suiri.guide.pager')` | `neighbors`（`['prev' => 1件か null, 'next' => 1件か null]`。`GuideLibrary::neighbors()`）・`route`（行き先のルート名）・`label=name`（見出しに使う欄の名前） | わざ・名推理カード（表・裏） |
| `<x-guide.work>` | 名推理カードの作品名と章（『緋色の研究』A Study in Scarlet 第1部第1・2章）。英語の題は斜体 | `scene`（`scenes.json` の1件。`story_ja`・`story_en`・`chapter` を使う） | 名推理カード（裏の題の下）／部品の中：guide（play-card・scene-item・scene-front・scene-back） |
| `<x-guide.quote>` | 原作の英語の短い引用と出典。斜体と左の赤い線で本文と分ける。文の途中からの引用には前後に「…」を添える | `text`（英語の引用。Project Gutenberg の本文のまま）・中身（出典の作品名と章。`<x-guide.work>` を入れてもよい） | わざ／`<x-guide.scene-back>` の中 |
| `<x-guide.audience-toggle>` | 説明を「子ども向け」「大人向け」で切り替える、横いっぱいの大きな2つのボタン（上に「だれと読む？」）。JavaScript なしで動くよう `?kid=1`・`?kid=0` のリンクにする（何も選んでいなければ、ふだんの場面が親子の人は子ども向け）。言葉は `config('suiri.guide.audience')` | `kid=false`（いま子ども向けを出しているか） | わざ・3つの考え方 |
| `<x-guide.section>` | 捜査ファイルの見出しのつまみ（段の色のタブ）が付いた紙。タブに絵と見出し、下の紙に中身を置く。何でも囲まず、手引きの中で「別の話」を区切るときだけ使う | `title`（タブの見出し）・`icon=null`・`tone=plain`（`clue`・`hypothesis`・`prediction`・`check`・`answer`・`accent`・`plain`）・`kicker=null`（タブの右の小さな言葉）・中身 | `<x-guide.skill-practice>`・`<x-guide.reasoning-item>` の中 |
| `<x-guide.skill-practice>` | わざの「ふだんの練習の例」（大人なら・子どもなら・施術者なら）と「やりがちなこと」をひとまとめにした、緑のつまみの紙。子ども向けを見ているときは子どもの例を先に出す。言葉は `config('suiri.guide.skill')` | `practice`（`skills.json` の `practice`：`adult`・`kid`・`work`）・`pitfall`（やりがちなことの文）・`kid=false` | わざ |
| `<x-guide.skill-links>` | わざの小さな札を並べる（押すとわざの画面へ）。「練習できるわざ」に使う | `keys=[]`（わざのキー S1〜S10）・`names=[]`（キーと名前の対応。`GuideLibrary::skillNames()`） | `<x-guide.practice-item>`・`<x-guide.scene-back>` の中 |
| `<x-guide.practice-item>` | 練習メニュー1件：名前とわざの札・もとの場面・説明と子ども向けの説明（子ども向けを見ている人には子ども向けを先に）・例のなぞ・「この練習を始める」（事件を作る画面へ問いかけ入りで進む。`cases.create` がなければ「準備中」の札）。id は `practice-{key}`（今日の画面と入口の「今日の練習」の飛び先）。言葉は `config('suiri.guide.practices')` | `practice`（`practices.json` の1件）・`skillNames=[]`・`kid=false` | 練習メニュー・わざ |
| `<x-guide.rain-analogy>` | 3つの考え方の入口に置く身近なたとえ（朝起きたら地面が濡れていたら？）。雨雲と水たまりの絵、見たこと、同じ場面での3つの考え方を1行ずつ（段の色の丸の絵つき）、最後に「かも」は2つ以上出して確かめよう、の一言。大人にも子どもにも同じたとえで伝わるよう、向きの切り替えに関係なく出す | `analogy`（`reasoning.json` の `analogy`：`title`・`fact`・`steps`・`note`）・`items`（`reasoning.json` の `items`。名前・絵・色をたとえの行に使う） | 3つの考え方 |
| `<x-guide.reasoning-item>` | 3つの考え方（アブダクション・演繹・帰納）の1つ。`<x-guide.section>` のつまみ（段の色と絵・順番・名前・子ども向けの呼び名）の下に、手帳の段の札・説明（大人向けか子ども向け）・どのくらい確か？・原作では（カードで推理してみる）・この手帳では、を並べる。言葉は `config('suiri.guide.reasoning')` | `item`（`reasoning.json` の `items` の1件。`icon`・`tone` も使う）・`number`（流れの中の順番）・`kid=false`・`scene=null`（原作の場面のカード） | 3つの考え方 |

## 7. ふりかえり（`components/review/`）

週のふりかえり・ノーベリ帳（外れた予想）・屋根裏の棚（わかった一般則）の3画面を受け持ちます。3画面の上に `<x-review.tabs>` を置いて行き来します。数を出す部品は、数が 0 でも責める言葉を出しません。

### 7-1. 3画面に共通

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-review.tabs>` | ふりかえり・ノーベリ帳・屋根裏の棚を行き来する切り替え（ナビの「ふりかえり」から入った先の中の目次） | `current=review`（`review`・`norbury`・`attic`。いまの画面に印を付ける） | 週のふりかえり・ノーベリ帳・屋根裏の棚 |
| `<x-review.story>` | ノーベリ帳・屋根裏の棚の上に置く短い説明。一言（いつも見える）と、名前の由来の話（開くと読める `<details>`）。親子の場面では子ども向けの呼び名と一言も添える | `lead`（一言）・`summary`（開く所の文字。例「名前の由来」）・`story`（由来の話）・`kid=false`・`kidName=null`（子ども向けの呼び名）・`kidTagline=null`（子ども向けの一言） | ノーベリ帳・屋根裏の棚 |
| `<x-review.pager>` | 一覧のページ送り（前へ・何ページ目か・次へ）。1ページで収まるときは何も出さない | `paginator`（`paginate()` の結果） | ノーベリ帳・屋根裏の棚 |
| `<x-review.case-link>` | 事件のなぞを、事件の詳細へのリンクにして出す（`cases.show` がなければ文字だけ） | `case=null`（`CaseFile`。null なら何も出さない） | `<x-review.miss-item>`・`<x-review.attic-item>` の中 |
| `<x-review.reason-counts>` | 「何を見落とした？」の種類ごとの数を横の棒で並べる（棒の長さは一番多い種類が基準。数は必ず文字でも出す。パソコンでは2列） | `items=[]`（`[['reason' => MissReason か null, 'count' => 数], …]`）・`unknown=0`（見落としを選ばなかった外れの数。0 なら出さない）・`kid=false` | ノーベリ帳・`<x-review.week-details>` の中 |

### 7-2. 週のふりかえり

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-review.week-nav>` | 上の帯（1行）。前の週へ・いまの週の期間（今週なら「今週」の札）・次の週へ。今週より先へは進めない | `week`（`App\Services\Review\ReviewWeek`）・`range=all`（確かさの期間。週を移っても引き継ぐ） | 週のふりかえり |
| `<x-review.stat>` | 大きな数字の札の1マス。値が null（数えようがない）なら「—」 | `label`・`value=null`・`unit=''`・`note=null`（数字の下の短い補足） | 週のふりかえり |
| `<x-review.week-details>` | 週の内訳を1枚にまとめる：外れから学んだこと・屋根裏の棚に増えた知識・見積もりの答え合わせ（答え合わせの結果の数は、上の数字の札 `<x-review.stat>` にまとめてある） | `stats`（`App\Services\Review\WeeklyStats::for()` の結果）・`kid=false` | 週のふりかえり |
| `<x-review.calibration>` | 確かさの答え合わせの枠：期間の切り替え（これまで全部・この週）→ 図 → 区間ごとの表。数えるのは当たり・外れだけ。件数が少ない区間は「まだ判断できない」 | `calibration`（`App\Services\Review\Calibration::for()` の結果）・`week`（`ReviewWeek`）・`range=all`（`all` か `week`） | 週のふりかえり |
| `<x-review.calibration-chart>` | 確かさの答え合わせの図（SVG。外のグラフの道具は使わない）。横は確かさ（50〜100％）、縦は当たった割合。点線が「確かさ＝当たった割合」の線。件数が足りた区間だけ点を打ち、どの区間にも件数を添える | `buckets=[]`（`Calibration` の結果の `buckets`） | `<x-review.calibration>` の中 |
| `<x-review.reflection-form>` | 週の問いの入力欄（今週いちばん考えが変わった事件は？・つまり？・たとえば？）。同じ週は上書き。問いの文は `config('suiri.review.questions')` | `week`（`ReviewWeek`）・`reflection=null`（保存済みの `WeeklyReflection`）・`caseOptions=[]`（「事件の id => なぞ」。その週に動いた自分の事件） | 週のふりかえり |

### 7-3. ノーベリ帳と屋根裏の棚

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-review.miss-item>` | ノーベリ帳の1件（外れた予想）：事件のなぞ・予想の文（書いたときのまま）・書いた日と確かさ・何を見落とした？・一言 | `prediction`（外れた `Prediction`。`caseFile` を読み込み済み）・`kid=false` | ノーベリ帳 |
| `<x-review.attic-item>` | 屋根裏の棚の1枚：つまり？（決まり）・たとえば？（例）・生まれた事件・この知識を使った予想の数と「直す」「消す」。`editing` のときは、その場で入力欄に変わる | `note`（`AtticNote`。`caseFile` と `predictions_count` を読み込み済み）・`caseOptions=[]`（「事件の id => なぞ」）・`editing=false` | 屋根裏の棚 |
| `<x-review.attic-form>` | 屋根裏の棚の知識の入力欄（つまり？・たとえば？・どの事件から）。足すときと直すときの両方で使う | `action`（送り先）・`method=POST`（直すときは `PATCH`）・`note=null`（直す知識）・`caseOptions=[]`（「事件の id => なぞ」）・`submit=棚に置く`（送るボタンの文字）・中身（保存ボタンの横に置くもの。「やめる」など） | 屋根裏の棚・`<x-review.attic-item>` の中 |

## 8. 設定（`components/settings/`）

設定の画面の枠と、カレンダーとつなぐ前の説明の行を受け持ちます。設定の画面（`settings/show`）は、枠の部品があるかを `View::exists()` で確かめてから `<x-dynamic-component component="settings.…">` で置きます（部品を外しても画面は壊れない）。

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-settings.calendar-panel>` | 「Google カレンダー」の枠。状態は4つ：準備中（Google の鍵が未設定）・つないでいない（「カレンダーとつなぐ」で説明の画面へ）・つながっている（最後に同期した日時と「つなぐのをやめる」）・切れた（赤い一文と「つなぎ直す」「つなぐのをやめる」）。やめるときは、専用カレンダーも Google から消すかを選べる | `connection=null`（その人の `CalendarConnection`。つないでいなければ null）・`user=null`（設定の画面が渡すが、中では使っていない）・`ready=GoogleTokens::configured()`（Google の鍵があるか。省略すると設定から決める） | 設定（`<x-dynamic-component>` で置く） |
| `<x-settings.danger-zone>` | 「データのダウンロードと退会」の枠。ダウンロードは自分のデータをすべて JSON で（カレンダー連携の鍵は入らない）。退会は確認の言葉を入れると、すべてを消してトップへ戻る | `user`（ログイン中の人） | 設定（`<x-dynamic-component>` で置く） |
| `<x-settings.consent-row>` | カレンダーとつなぐ前の説明の1行（見出しと説明）。`<dl>` の中に並べる。スマホでも見出しと説明を横に並べ、「同意して Google へ進む」がスクロールせずに見える高さに収める | `label`（左の見出し）・中身（説明の文） | カレンダーとつなぐ前の説明 |

### 8-2. ChatGPT・Claude とつなぐ枠（要件 4-11 の M-7）

設定の画面のカレンダーの枠の下に置きます。すぐ上のカレンダーの枠とそろえて、枠（カード）では囲まず、上の罫線で区切ります。日本語は文節で折り返します（`[word-break:auto-phrase]`）。言葉は `config('ai_connect.panel')`。

```text
設定（settings/show）
└─ settings.ai-panel ─┬─ settings.ai-app-row ─ ui.confirm（つないでいる AI ごとに1行）
                      └─ settings.ai-steps（ChatGPT・Claude のつなぎ方。たたんだ手順）
```

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-settings.ai-panel>` | 「ChatGPT・Claude とつなぐ」の枠。上から：絵と見出しとつないでいる数の札（なければ「つないでいません」）→ できること3つ（チェックの絵つき）と、読むだけ・仕事の場面は見せない・正誤を決めさせないの一言 → つないでいる AI の一覧（あるときだけ）→ 窓口の住所（写すボタンつき。動きは `resources/js/parts/ai-copy.js`）→ ChatGPT と Claude のつなぎ方 →「画面の名前は変わることがある」→ 渡った記録の扱いの注意 | `apps=[]`（つないでいる AI の一覧。受付が `App\Queries\Ai\ConnectedAppsQuery::for($user)` で作る：`id`・`name`・`host`・`last_at`）・`mcpUrl=url('/mcp')`（窓口の住所） | 設定 |
| `<x-settings.ai-app-row>` | つないでいる AI の1行（`<li>`）：名前（AI が名乗ったもの）・戻る先の住所・最後につながった日と「つなぐのをやめる」（押すと確かめの文が開く `<x-ui.confirm>`。`DELETE /settings/ai/{app}`） | `app`（`ConnectedAppsQuery` の1行） | `<x-settings.ai-panel>` の中 |
| `<x-settings.ai-steps>` | つなぎ方の手順。たたんで置き、押すと開く（JavaScript なしの `<details>`）。手順は順番があるので丸い番号を付ける。画面の項目名は英語のまま | `title`（見出し）・`steps`（手順の文の一覧）・`note=null`（手順の下の一言。ChatGPT のプランの注意など） | `<x-settings.ai-panel>` の中 |

### 8-3. 同意の画面（`views/oauth/authorize`。要件 4-11 の M-6）

AI に記録を読ませてよいかを聞く画面（`GET /oauth/authorize`）。部品ではなく画面ですが、AI とつなぐ部品と一緒にここに書きます。Passport の受付が出し、値は `App\Support\Ai\ConsentView::from()` が詰め替えます（画面で計算しないため）。言葉は `config('ai_connect.consent')`。骨組みはログイン前と同じ `<x-layouts.guest>` で、いまやることは「この AI に読ませてよいか決める」の1つなので、`<x-ui.step-card>` 1枚にまとめます。

| 置くもの | 中身 | 受け取る値 |
|---|---|---|
| 問い（カードの見出し） | 「◯◯ に、あなたの事件の記録（仕事の場面を除く）を読ませてよいですか？」と「読むだけです」 | `appName`（AI が名乗った名前。自己申告） |
| 許可したあとに戻る先 | `chatgpt.com` などの住所を太く。「名前はだれでも名乗れるよ。使っている AI の住所か確かめてね」 | `redirectHost` |
| 読めるもの・読めないもの | チェックの絵と × で1行ずつ | （config の `shows`・`hides`） |
| 記録が渡る会社と国 | 「記録は OpenAI（米国）に渡り…」と、その会社のプライバシーポリシーへのリンク（わかるときだけ） | `company`（`name`・`country`・`policy`。戻る先の住所から `consent.companies` で引く） |
| なりすましの注意 | 「自分で AI の設定から『つなぐ』を押したときだけ進めてね」（赤い枠） | — |
| 許可する・やめる | 横いっぱいの `<x-ui.big-button>` 2つ。許可する＝`POST /oauth/authorize`、やめる＝`DELETE /oauth/authorize`。どちらも隠し欄で `state`・`client_id`・`auth_token` を送る | `state`・`appId`・`authToken` |
| あとでできること | 「あとから 設定 → ChatGPT・Claude とつなぐ で、いつでもやめられるよ」 | — |

## 9. ログイン（`components/auth/`）

トップ（ログイン前）の画面の、ログインのボタンを受け持ちます。

| 部品 | 役目 | 受け取る値 | 使っている画面 |
|---|---|---|---|
| `<x-auth.google-button>` | 「Google でログイン」のボタン。Google の決まりどおり、白い面に色つきの G のマーク（色と形は変えない）。鍵が未設定なら「準備中」と出し、押せない | `ready=false`（`GoogleLoginController::enabled()` の結果を渡す） | トップ |
