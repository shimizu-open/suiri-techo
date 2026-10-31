---
paths:
  - "src/app/**"
  - "src/routes/**"
  - "src/bootstrap/app.php"
  - "src/config/**"
  - "src/resources/views/**"
  - "src/resources/js/**"
  - "src/resources/css/**"
---

# 太らない MVC（層・置き場所・行数・名前）

元は `docs/design/basic-design.md` の 1。**1ファイル＝1つの役目**にし、受付（コントローラー）を薄く保つ。行数と禁じた書き方は `bin/check` が止める（ファイルを直した直後のフックでも出る）。

## 層と置き場所

```
ブラウザ → routes/web/{機能}.php → ミドルウェア → Controller（受付）
              ├─ FormRequest（形の検証・入力をそろえる）
              ├─ Policy（この人がしてよいか＝持ち主か）
              ├─ Action（1つの用事。handle() 1つ）── Model / DB
              │      └─ Service（外とのやりとり：Google）
              ├─ Query（画面に出す一覧・集計の組み立て）
              └─ view() → Blade の画面 → Blade コンポーネント（表示の部品）
```

| 層 | 置き場所 | 受け持つこと | してはいけないこと（★は `bin/check` が止める） |
|---|---|---|---|
| ルート | `routes/web.php` は `require` で読み込むだけ。中身は `routes/web/{機能}.php` | 住所・ルート名・ミドルウェア・`->can()` | ★クロージャで処理を書く、★名前なしの `throttle:数字` |
| 受付 | `app/Http/Controllers/{機能}/` | 受け取る → Action や Query を呼ぶ → 画面か転送を返す。1アクションは目安 10 行 | ★`validate(`、★`DB::`、★`Http::`、問い合わせの組み立て |
| 形の検証 | `app/Http/Requests/{機能}/` | `rules()`・`attributes()`・`prepareForValidation()`。選択肢は列挙か config から | DB に書く |
| 権限 | `app/Policies/` | 持ち主か（`view`・`update`・`delete`） | 検証・書き込み |
| 用事 | `app/Actions/{機能}/` | 1クラス＝1つの用事、public は `handle()` だけ。複数の表に書くなら `DB::transaction()`。Action から Action は2段まで | 画面の都合（リダイレクト・HTML）を知る |
| 外とのやりとり | `app/Services/{相手}/`＋契約 `app/Contracts/` | Google カレンダーなど。テストで偽物に差し替えられるよう、インターフェース越しに使う（`AppServiceProvider` で結ぶ） | 画面・リクエストを知る |
| 読み出しのまとめ | `app/Queries/{機能}/` | 一覧・集計を組み立てて返す | 書き込み |
| モデル | `app/Models/` | 関係・キャスト・スコープ・小さな判定（`isDone()`） | 複数の表にまたがる処理 |
| 列挙 | `app/Enums/` | 状態・種類。`label(?Scene $scene)` で `config/suiri.php` から言葉を引く | 言葉の直書き |
| 後回しの仕事 | `app/Jobs/` | Google への書き出し・取り込み（worker の箱が片づける） | 画面を待たせる処理を受付で直接 |
| 画面 | `resources/views/{機能}/{動作}.blade.php` | 部品を並べるだけ | ★`@php` のかたまり、DB に触る、`@json` に複数行の配列を直接書く |
| 表示の部品 | `resources/views/components/{分類}/`（`<x-ui.button>`・`<x-case-step.clue-card>`） | `@props` で受け取るものを宣言する無名コンポーネント。計算が要るときだけ `app/View/Components/`。見た目は Tailwind のクラスで書き、種類ごとの組み合わせは部品の中の PHP の配列で選ぶ | データを取りに行く |
| 見た目の土台 | `resources/css/app.css` | `@theme` の色・書体・大きさの名前、`:root` の昼と夜の色の値、`@layer base` の要素の基本の見た目。Tailwind で書けない切り替えだけ `@custom-variant`・`@utility` | ★独自のクラス（`@layer components` の `.名前 {`・トップレベルの `.名前 {`）、★Blade の `<style>`・`dark:` |
| 動き | `resources/js/app.js`（読み込むだけ）＋ `resources/js/parts/{部品}.js` | 1ファイル＝1つの部品の動き | 1ファイルに機能を足し続ける |
| 設定値・文言 | `config/suiri.php`・`config/watson.php`・`config/legal.php` | 数字・文言・選択肢 | コードに直書き、★`config/` 以外での `env()` |

- 権限の書き場所は1通りにそろえる（土台で決めた書き方に従う。`$this->authorize()` は Laravel 11 以降の空の親コントローラーでは使えない）。
- CRUD は `Route::resource(...)->only([...])`。CRUD に当てはまらない動き（閉じる・見せ合う・答え合わせ）は1アクションのコントローラー（`__invoke`）か、表の名前に合わせたルート名（`cases.close`）。
- 日本語の検証メッセージと項目名は `lang/ja/validation.php` に集める（各 FormRequest に書かない）。

## 行数の上限（コメント込み・`bin/check` が止める）

| 対象 | 上限 | 超えたら |
|---|---|---|
| `src/tests/**/*.php` | 300 | `tests/Feature/{機能}/` に動作ごとに分ける |
| `src/app/Http/Controllers/**` | 150 | 7 アクションを超えたらコントローラーを分ける。処理は Action・Query へ |
| `src/routes/**/*.php` | 120 | `routes/web/{機能}.php` に分ける |
| `src/resources/views/**/*.blade.php` | 150 | 意味のまとまりを `components/{分類}/` の部品にする |
| そのほかの PHP クラス（`src/app/**`・移行・factory・seeder） | 200 | 1クラス＝1つの役目に分ける。移行は1つに1つの変更 |
| `src/resources/js/**/*.js` | 200 | `parts/` に部品ごとに分ける |
| `src/resources/css/**/*.css` | 400 | `@import` で分ける |

どうしても分けられないもの（文章だけの法律のページなど）は、`bin/check` の `ALLOW_SIZE` に**上限と理由**を書く。上限そのものを上げない。

## 名前の付け方

| もの | 形 | 例 |
|---|---|---|
| モデル／表 | 単数の英語／複数のスネークケース | `CaseFile`／`case_files` |
| コントローラー | `{機能}/{モデル}Controller`、1アクションは `{動詞}{名詞}Controller` | `Cases/CaseController`・`Cases/CloseCaseController` |
| FormRequest | `{Store\|Update}{モデル}Request` | `StoreClueRequest` |
| Policy | `{モデル}Policy` | `CaseFilePolicy` |
| Action | `{動詞}{名詞}`（`handle()`） | `ResolvePrediction`・`AddClue`・`ConnectCalendar` |
| Service／契約 | `{相手}\{役目}`／`Contracts\{役目}` | `Google\CalendarApi`／`Contracts\CalendarApi` |
| Query | `{機能}\{何の}Query` | `Today\DuePredictionsQuery` |
| Job | `{動詞}{名詞}` | `PushTaskToCalendar` |
| URL／ルート名 | 英語の名詞の複数／URL に合わせる | `/cases`／`cases.show`・`cases.close` |
| ビュー／部品 | `{表}/{動作}.blade.php`／`components/{分類}/{名前}.blade.php` | `cases/index`／`<x-case-step.clue-card>` |
| JS | `parts/{部品}.js`（ケバブケース） | `parts/suggest-chips.js` |
| テスト | `tests/Feature/{機能}/{動作}Test.php` | `tests/Feature/Cases/ResolvePredictionTest.php` |

画面の言葉とコードの名前の対応は `rules/domain.md`（人間向けは `docs/design/words.md`）の表に載せてから使う。
