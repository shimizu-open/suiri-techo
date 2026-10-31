# 部品の見本（UI キット）

- 状態：作り直しの共通部品（2026-10-31。同じ日に「今日」と「タスク」を1つの画面「きょう」にまとめたのに合わせて、使う画面とナビの説明を直した）。部品の一覧の正本は `components.md`。ここは「子どもと一緒に使える画面」を組むための部品の見本帳。
- 置き場所：`src/resources/views/components/ui/`（色の名前は `src/resources/css/app.css` の `@theme`、決まった言葉は `src/config/suiri.php` の `ui`）。

## この見本でわかること

画面の作り直しの3つの原則を、部品でどう守るか。

1. **1画面に「いまやること」は1つ** … `step-card`（いまやることのカード）1枚と `big-button`（主なボタン）。済んだことは `done-summary` で小さくたたむ。
2. **やわらかい話し言葉** … 部品は文言を持たず、画面から受け取る（決まった言葉だけ `config('suiri.ui')`）。言葉の説明は `hint` の吹き出しで。
3. **子どもと一緒に使える** … 主なボタンは高さ 56px で横いっぱい、本文は 16px 以上、段ごとの色と絵で「いまどこか」を見せる。

たとえると、捜査の手帳に使う**文房具の種類**を決めたもの。段ごとの色のつまみ（捜査ファイルのタブ）、証拠品の荷札、「確かめた」のはんこ、相棒に渡す付箋。紺のつまみは「見たこと」、からし色は「どうして？」……と決めておくと、どの画面でも同じ形と色が同じ意味になる。

## 見た目の土台（AI が作りがちな見た目から抜け出す）

- **紙**：うすい青みの紙（`paper` #f3f5f9）と白いカード（`sheet`）。クリーム色の背景はやめた。灰色（`muted`・`rule`・`mark`）はすべて紺に寄せた灰色。
- **書体**：端末の書体だけ。子どもにも読みやすい UD 書体を先頭に `"BIZ UDPGothic", "Hiragino Sans", "Noto Sans JP", sans-serif`。見出しも同じ書体の太字。明朝体（`font-mincho`）は**事件のなぞの1か所だけ**。数字は桁がそろう形（`tabular-nums`。body に指定ずみ）。
- **文字の大きさの段**：`text-xs` 13px・`text-sm` 15px・`text-base` 16px（本文）・`text-lg` 18px・`text-xl` 22px・`text-2xl` 28px。この段の外の大きさを作らない。
- **カードは役割のあるものだけ**：枠と角丸で囲むのは「いまやること」（`step-card`）と、題材の形（`evidence-tag`・`stamp`・`handoff`）だけ。済んだこと（`done-summary`）と空のとき（`empty-state`）は囲まない。カードの左に色の帯を付けない。
- **状態は形でも見せる**：段は色のつまみと絵、手がかりは荷札、答え合わせははんこの色と線の形（二重の輪・点線）。
- **ダークモード**：反転ではなく、同じ意味の色を夜でも読める濃さで選び直した（下の表）。

## 段ごとの色と絵

| 段 | tone | 色の名前（`@theme`） | 絵（`step-icon`） | 昼／夜の色 |
|---|---|---|---|---|
| 見たこと（手がかり） | `clue` | `clue`・`clue-soft` | `eye` | 紺 #23457e ／ #a9c1ee |
| どうして？（見立て） | `hypothesis` | `hypothesis`・`hypothesis-soft` | `question` | からし色 #7d5800 ／ #e6c065 |
| もしそうなら（予想） | `prediction` | `prediction`・`prediction-soft` | `arrow` | 深い緑 #1f6248 ／ #8fd1ad |
| 確かめる日 | `check` | （`prediction` と同じ緑） | `calendar` | 同上 |
| 答え合わせ | `answer` | `answer`・`answer-soft` | `check` | 深い赤 #962234 ／ #ef9aa1 |
| ふつう | `plain` | `ink`・`mark` | なし | 紺のインク #1d2a4d ／ #e7ebf4 |

- Tailwind のクラスでそのまま使える：`bg-clue`・`text-clue`・`bg-clue-soft`・`border-clue/40` など。ダークモードは `app.css` の変数の入れ替えだけで切り替わる（`dark:` を書かない）。
- 段の色で塗った面の上の文字は `text-on-ink`（昼は生成り・夜は濃い紺）。
- 文字の読みやすさ（コントラスト比）：段の色の文字は、紙・カード・淡い面のどれの上でも 4.5 以上（昼 5.4〜9.4、夜 6.7〜10.5）。塗ったボタンの上の文字も 6.4 以上。やわらかい文字（`muted`）も紙・淡い面の上で 5.4 以上。
- 部品の中では、段の種類ごとのクラスの組み合わせを PHP の配列で選ぶ（`['clue' => 'bg-clue-soft text-clue', …]`）。`app.css` に独自のクラスは足さない。

その他：`rounded-step`（いまやることのカードの角丸 1.25rem）、本文 16px・行間 1.75。

## 部品

### step-card — いまやることのカード

- **役目**：その画面でいちばんすべきことを、問いの見出し・例・入力欄・主なボタンの1枚にまとめる。形は捜査ファイル：左上に段の色のつまみ（絵と「ステップ 2 / 5」）、その下に段の淡い色の見出しの面。
- **props**：`step`・`total`（つまみの「ステップ 2 / 5」。どちらか省くと数を出さない）、`icon`（つまみに出す `step-icon` の名前。数も絵もなければつまみを出さない）、`tone`（clue・hypothesis・prediction・check・answer・plain）、`title`（問い）、`hint`（例。問いのすぐ下にやわらかい色で出す）
- **slot**：中身（入力欄）、`action`（主なボタン）、`later`（「あとでできること」。カードの外の下に小さく1行）
- **使う画面**：事件の各段（見たこと・どうして？・もしそうなら・確かめる日・答え合わせ）、今日のいちばん上、ふりかえりの1問

```blade
<form method="POST" action="{{ route('clues.store', $case) }}">
  @csrf
  <x-ui.step-card :step="2" :total="5" icon="eye" tone="clue" title="見たことを1つ教えて" hint="例：今週、体操着を2回わすれた">
    <x-ui.textarea name="body" label="見たこと" rows="3" />
    <x-ui.privacy-note />
    <x-slot:action><x-ui.big-button tone="clue" icon="check">これでOK</x-ui.big-button></x-slot:action>
    <x-slot:later><a href="{{ route('cases.show', $case) }}">あとで書く</a></x-slot:later>
  </x-ui.step-card>
</form>
```

### progress — 進み具合の丸の列

- **役目**：いまが何段目かを見せる。済み＝塗った丸にチェック、いま＝大きな枠（段の色）、まだ＝うすい小さな丸。上に「ステップ 2 / 5」。
- **props**：`steps`（`[['label' => 'なぞ', 'done' => true], ['label' => '見たこと', 'current' => true], ['label' => 'どうして？'], …]`。各段に `tone` を入れてもよい）、`tone`（いまの段の色）、`count`（`false` で「ステップ n / m」を出さない。`step-card` に数を出すときに）
- 段が6つ以上でスマホの幅のときは、いまの段の名前だけを見せる（ほかは読み上げだけ）。
- **使う画面**：事件の画面の上、相棒モードの流れ

```blade
<x-ui.progress tone="clue" :count="false" :steps="$steps" />
```

### big-button — 横いっぱいの主なボタン

- **役目**：1画面に1つ、いまやることの決め手。高さ 56px・文字 18px・横いっぱい。
- **props**：`href`（あればリンク）、`type`（submit が既定）、`icon`（`step-icon` の名前か、`ui.icon` の名前 `plus` など）、`tone`（primary・clue・hypothesis・prediction・check・answer・secondary）
- ほかの属性（`name`・`value`・`disabled`）はそのまま付く。2つ目の控えめな操作は `tone="secondary"` か、`step-card` の `later` の小さなリンクにする。

```blade
<x-ui.big-button :href="route('cases.create')" icon="plus">事件を作ってみよう</x-ui.big-button>
```

### step-icon — 段ごとの絵（10種）

- **役目**：段の色と一緒に「いまどこか」を見せる、手で描いた線の絵（inline SVG。外の画像やアイコンの道具は使わない）。色は文字の色（`text-clue` などで変える）。
- **props**：`name`（eye・question・arrow・calendar・check・book・attic（屋根裏の棚）・norbury（ノーベリ帳）・partner（相棒）・sparkle（ひらめき・ワトソン））。`class` を省くと `size-6`。
- ナビ・設定などの道具の絵は、これまでどおり `<x-ui.icon>`。

```blade
<x-ui.step-icon name="attic" class="size-6 text-ink" />
```

### hint — やわらかい説明の吹き出し

- **役目**：言葉の一言の説明（「見立て＝『どうしてだろう？』の答えの案」）や、ワトソンのひとことを同じ見た目で出す。左に話し手の丸の絵。
- **props**：`label`（吹き出しの上の小さな名前。「ワトソンのひとこと」など）、`icon`（既定 sparkle。`:icon="null"` で出さない）、`tone`（既定 plain）
- **slot**：中身、`actions`（閉じるなどの小さなボタン）

```blade
<x-ui.hint label="ワトソンのひとこと" tone="hypothesis">ほかの理由も1つ考えてみない？</x-ui.hint>
```

### done-summary — 済んだことの小さな要約

- **役目**：済んだ段を1行にたたむ（押すと一覧が開く。JavaScript なしの `<details>`）。いまやることのカードの上に置く。カードで囲まず、下に細い線を引いた1行（何行か重ねると済んだ段の目次になる）。
- **props**：`title`（「見たこと 3つ」）、`items`（文字の配列。`['text' => …]` でもよい）、`edit-href`・`edit-label`（「直す」のリンク）、`icon`（既定 check）、`tone`、`open`

```blade
<x-ui.done-summary title="見たこと 3つ" icon="eye" tone="clue" :items="$clueTexts" :edit-href="route('cases.show', $case)" />
```

### empty-state — まだ何もないとき

- **役目**：大きな絵・一言・主なボタン1つだけで「まず何をすればいいか」を見せる。枠で囲まず、左にそろえる。
- **props**：`icon`（既定 sparkle）、`title`、`action-href`・`action-label`（両方あるときだけボタン）、`action-icon`（既定 plus）、`tone`
- **slot**：中身（短い説明）、`action`（ボタンの代わりにフォームを置くとき）
- **使う画面**：事件・ノーベリ帳・屋根裏の棚の一覧が空のとき（きょうの画面のたたんだ枠が空のときは、絵を出さず一言だけ）

```blade
<x-ui.empty-state icon="eye" tone="clue" title="まだ事件がないよ" :action-href="route('cases.create')" action-label="最初の事件を作ってみよう">気になることを1つ書くだけでOK</x-ui.empty-state>
```

### evidence-tag — 証拠品の荷札

- **役目**：手がかり1つ＝1枚の札。左の角を落とした形とひもの穴で「集めた証拠」だとわかる。札は段の淡い色で塗り、枠は付けない。
- **props**：`no`（札の番号。「No.3」）、`label`（種類の言葉：見た・聞いた・なかった。子ども向け・仕事の場面の言葉は画面から渡す）、`tone`（既定 clue）
- **slot**：中身（手がかりの文）、`actions`（○×・直す・消すなどの小さなボタン）
- **使う画面**：事件の画面の手がかりの一覧、事件を作る画面・相棒モードで手がかりを見せる所

```blade
<x-ui.evidence-tag :no="$loop->iteration" :label="$clue->kind->label($case->scene)">{{ $clue->body }}</x-ui.evidence-tag>
```

### stamp — 答え合わせの「確かめた」のはんこ

- **役目**：答え合わせの結果を、丸いはんこの色と線の形で見せる（文字を読まなくてもわかる）。少し傾けて押した形。
- **props**：`result`（hit・miss・unclear・void。`PredictionResult` の列挙のままでもよい）、`label`（はんこの中の言葉。省略すると `config('suiri.labels.prediction_result')`。子ども向けは画面から渡す）、`size`（md 直径 112px・上に「確かめた」／sm 直径 72px・言葉だけ）
- 形：当たり＝赤い二重の輪、外れ＝紺の二重の輪、どちらとも言えない＝からし色で内側が点線、前提が崩れた＝灰色の点線の輪。
- **使う画面**：答え合わせが済んだ予想、ノーベリ帳・ふりかえりの一覧（sm）

```blade
<x-ui.stamp :result="$prediction->result" :label="$prediction->result->label($case->scene)" />
```

### handoff — 「スマホを渡して」の大きな付箋

- **役目**：相棒モードで1台のスマホを回すとき、次に書く人と「見ないでね」をはっきり見せる（見せ合うまで互いの見立てを隠す決まりを、画面でも支える）。形は「相棒の番の付箋」：からし色の紙をテープで留めた形。
- **props**：`to`（呼び名。「長男にスマホを渡してね」になる）、`href`（受け取った人が押すボタンの行き先）、`label`（ボタンの文字。既定「受け取った！はじめる」）
- **slot**：中身（ひとこと）、`action`（リンクではなくフォームで進めるときのボタン）

```blade
<x-ui.handoff :to="$partnerName" :href="route('hypotheses.create', $case)">お父さんは見ないでね</x-ui.handoff>
```

## 骨組みとすでにある部品の見直し

- 下のナビ（スマホ）：4つ（きょう・事件・手引き・ふりかえり）を横に4等分。絵 24px・名前 13px。いまの画面は絵を紺で塗った丸の中に出し、名前を太く濃く。高さ 64px。
- 左のナビ（パソコン）：文字 16px、いまの画面は紺で塗る。
- 上の帯：高さ 48px、画面の名前は小さめのやわらかい色（いまやることのカードを目立たせる）。事件の画面は「事件 No.12」のように番号を名前にできる。
- 左のナビの題字（推理手帳）も UD 書体の太字（明朝体は事件のなぞだけ）。
- `ui/button`：md は高さ 48px、sm は 40px。`ui/field`・`textarea`・`select`・`choices`：ラベル 16px、補足 15px、入力欄と選ぶ札は高さ 48px 以上。

## 見本の画面の撮り方

本番のルートは作らない。一時の Feature テストで `actingAs` し、`Blade::render()` で見本を HTML に書き出して、`/build/` の CSS を `file://` で読ませた headless Chrome で撮る（スマホ 390×844・パソコン 1280・ダークモード）。撮ったら一時のテストは消す。
