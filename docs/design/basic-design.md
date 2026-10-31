# 基本設計

画面・住所（URL）・ファイルの置き場所・部品の分け方です。要件は [要件定義書](requirements.md)、言葉は [言葉のルール](words.md)、表は [データ設計](data-model.md)。

- 更新：2026-10-31（「今日」と「タスク」を1つの画面「きょう」にまとめたのに合わせて、2-2・2-3・3・4・5 を直した。`tasks.index` は画面を返さず `/today?open=…` へ移すだけになり、ナビは4つになった。それより前に：最初の版の実装に合わせた。増えたルート `tasks.create`・`tasks.draft`・`cases.draft`・`calendar.redirect` と、受付の分け方を反映）

## 1. 層の分け方（太らない MVC）

```
ブラウザ → routes/web/{機能}.php → ミドルウェア → Controller（受付）
              ├─ FormRequest（形の検証・入力をそろえる）
              ├─ Policy（この人がしてよいか＝持ち主か）
              ├─ Action（1つの用事。handle() 1つ）── Model / DB
              │      └─ Service（外とのやりとり：Google）
              ├─ Query（画面に出す一覧・集計の組み立て。受付が太るときだけ）
              └─ view() → Blade の画面 → Blade コンポーネント（表示の部品）
```

| 層 | 置き場所 | 受け持つこと | してはいけないこと |
|---|---|---|---|
| ルート | `routes/web.php` は読み込みだけ。中身は `routes/web/{機能}.php` | 住所・ルート名・ミドルウェア・`->can()` | クロージャで処理を書く |
| 受付 | `app/Http/Controllers/{機能}/` | 受け取る → Action や Query を呼ぶ → 画面か転送を返す | `validate()`、DB の問い合わせの組み立て、外の API を呼ぶ |
| 形の検証 | `app/Http/Requests/{機能}/` | `rules()`・`attributes()`・`prepareForValidation()` | DB に書く |
| 権限 | `app/Policies/` | 持ち主か（`view`・`update`・`delete`）。事件・手がかり・見立ては `write`（持ち主で、事件が捜査中か。閉じた事件への書き込みは受付で 403） | 検証・書き込み |
| 用事 | `app/Actions/{機能}/` | 1クラス＝1つの用事。トランザクション | 画面の都合（リダイレクト・HTML）を知る |
| 外とのやりとり | `app/Services/{相手}/` | Google カレンダーなど。インターフェース（`app/Contracts/`）を置いてテストで偽物に差し替える | 画面・リクエストを知る |
| 読み出しのまとめ | `app/Queries/{機能}/` | 一覧・集計を組み立てて返す | 書き込み |
| モデル | `app/Models/` | 関係・キャスト・スコープ・小さな判定 | 複数の表にまたがる処理 |
| 列挙 | `app/Enums/` | 状態・種類。`label(?Scene $scene)` で表示の言葉を `config/suiri.php` から引く | |
| 画面 | `resources/views/{機能}/{動作}.blade.php` | 部品を並べるだけ | `@php` の計算、DB に触る |
| 表示の部品 | `resources/views/components/{分類}/` | 名前つきの部品（`<x-ui.button>`・`<x-case-step.clue-card>`）。`@props` で受け取る | データを取りに行く |
| 動き | `resources/js/app.js`（読み込むだけ）＋ `resources/js/parts/{部品}.js` | 1ファイル＝1つの部品の動き | 1ファイルに機能を足し続ける |
| 設定値・文言 | `config/suiri.php`・`config/watson.php`・`config/legal.php` | 数字・文言・選択肢 | コードに直書き |

行数の上限（コメント込み）：PHP クラス 200・コントローラー 150・ルート 120・Blade 150・JS 200・CSS 400・テスト 300。`bin/check` が止めます。

## 2. 画面と住所

ログインが要る画面には `auth` と `agreed`（同意済みか）のミドルウェアを付けます。

### 2-1. 公開とログイン（機能：Auth・Legal）

| 画面 | メソッドと住所 | ルート名 | 受付 |
|---|---|---|---|
| トップ（ログイン前） | GET `/` | `home` | `HomeController`（ログイン済みなら `/today` へ） |
| Google へ送り出す | GET `/auth/google` | `auth.google` | `Auth\GoogleLoginController@redirect` |
| Google から戻る | GET `/auth/google/callback` | `auth.google.callback` | `Auth\GoogleLoginController@callback` |
| ログアウト | POST `/logout` | `logout` | `Auth\LogoutController` |
| 同意 | GET/POST `/agree` | `agree.show`／`agree.store` | `Auth\AgreementController` |
| このアプリについて | GET `/about` | `about` | `LegalController@about` |
| プライバシーポリシー | GET `/privacy` | `privacy` | `LegalController@privacy` |
| 利用規約 | GET `/terms` | `terms` | `LegalController@terms` |

### 2-2. きょう（機能：Today）

ログインした人の最初の画面です。前は「今日」と「タスクの一覧」が別の画面でしたが、利用者の声「今日とタスクが一緒になって、もうちょっと操作性がシンプルになってくれたらありがたい」を受けて、1つの画面「きょう」にまとめました（住所は `/today` のまま）。たとえると、1日のページを開くと、今日の予定とやることが書いてあり、その下に「これから」「いつか」「済み」の付箋が名前と枚数だけ見える形にたたんで貼ってある手帳です。

| 画面 | メソッドと住所 | ルート名 | 受付 |
|---|---|---|---|
| きょう（いまやることのカード・きょうの予定とやること・これから／いつか／済み のたたんだ枠・今日の練習・捜査中の事件） | GET `/today?open=upcoming\|someday\|done` | `today` | `Today\TodayController`（各部品のデータは Query から。`?open=` の値の枠だけ最初から開いて出す。知らない値・なしなら、どの枠も開かない） |

- 画面の並び（上から）：やることを済ませた直後だけ「実際は何分だった？」の枠 → いまやることのカード（答え合わせ待ちか、記録のきっかけ）→ **きょうの予定とやること**（見出しのすぐ下に1行で書く欄 → 期日を過ぎたやること（赤い印）→ 予定と今日のやること（時刻順）→ いちばん下に これから・いつか・済み のたたんだ枠）→ 今日の練習・捜査中の事件。
- 受付が画面に渡す値：`user`・`today`・`predictions`（答え合わせ待ち）・`items`（予定と今日のやることの時刻順。`Today\AgendaQuery`）・`practice`・`cases`・`justDone` に、`Tasks\TaskListQuery::forToday()` の `overdue`（期日を過ぎた、まだ済んでいない Task の並び。古い順）・`upcoming`（明日から先の Task を期日 `Y-m-d` ごとにまとめたもの）・`someday`・`done`（今日を入れて `config('suiri.today_tasks.done_days')`＝7日のあいだに済んだもの。新しい順に `done_limit`＝30件まで）・`counts`（枠ごとの件数）・`dates`（1行で書く欄のボタンの日付：今日・明日）と、`open`（`TaskListQuery::normalizeOpen()` でそろえた `?open=` の値）を足したもの。

### 2-3. タスク（機能：Tasks）

| 画面・操作 | メソッドと住所 | ルート名 | 受付 |
|---|---|---|---|
| 前の一覧の住所（画面は返さず、きょうの画面へ移すだけ） | GET `/tasks?view=` | `tasks.index` | `Tasks\TaskController@index`（`?view=upcoming\|someday\|done` → `/today?open=` の同じ値。`today`・知らない値・なしは `/today`。値をそろえるのは `TaskListQuery::normalizeOpen()`） |
| くわしく作る画面・作る（きょうの画面の1行で書く欄も、ここへ送る） | GET `/tasks/create`・POST `/tasks` | `tasks.create`・`tasks.store` | `TaskController@create`・`@store` |
| 1行で書く欄の「くわしく」（まだ作らず、入れた題名を持って作る画面へ） | POST `/tasks/draft` | `tasks.draft` | `Tasks\DraftTaskController`（題名を URL に載せないため POST で受け、一度きりの入力として渡す） |
| 直す画面・直す | GET `/tasks/{task}/edit`・PATCH `/tasks/{task}` | `tasks.edit`・`tasks.update` | `TaskController@edit`・`@update` |
| 消す | DELETE `/tasks/{task}` | `tasks.destroy` | `TaskController@destroy` |
| 完了（かかった時間も送れる）・戻す | POST `/tasks/{task}/done`・DELETE `/tasks/{task}/done` | `tasks.done`・`tasks.undone` | `Tasks\CompleteTaskController`・`Tasks\ReopenTaskController` |

予想から作った確かめるタスク（`Task::answeredByPrediction()` が真）は、丸を押して済みにしたり戻したりできません（答え合わせで済みになる）。受付の FormRequest も画面の部品も、このメソッド1つで判断します。

**戻り先**（どの操作のあとに、どこへ戻すか）：

- 作る・直す・消すのあとは、きょうの画面の**そのタスクが見える枠**を開いて戻します（`TaskController::listUrl()` → `TaskListQuery::openOf()`）。済んでいれば `?open=done`、期日がなければ `?open=someday`、期日が明日より先なら `?open=upcoming`、今日まで（今日の並び・期日を過ぎたもの）なら枠は開かず `/today`（上の並びに出るため）。1行で書く欄から作っても、くわしく作る画面から作っても同じです。直す画面の「もどる」のリンクも同じ住所です。
- 1行で書く欄は、押したボタン（「今日」「明日」「いつか」）の値が `due_on` になって `POST /tasks` に届きます（今日の日付・明日の日付・空）。
- 済ませる（`CompleteTaskController`）・戻す（`ReopenTaskController`）は、元の画面へ戻します（`redirect()->back()`。開いていた枠の `?open=` も元の住所ごと残る。元がわからなければ `/today`）。済ませたときは、きょうの画面のいちばん上に「実際は何分だった？」の枠を出します。

### 2-4. 事件（機能：Cases）

受付は「事件の CRUD は1つのコントローラー、CRUD でない動き（閉じる・開き直す・見せ合う・答え合わせ・取り下げ・ひとことを閉じる）は1アクションのコントローラー」に分けています（置き場所は `app/Http/Controllers/Cases/`）。

| 画面・操作 | メソッドと住所 | ルート名 | 受付 |
|---|---|---|---|
| 一覧 | GET `/cases?status=&scene=` | `cases.index` | `CaseController@index`（`CaseListQuery`） |
| 作る画面 | GET `/cases/create?origin=practice&practice=` | `cases.create` | `CaseController@create`（`NewCaseDraft`） |
| 予定・タスクの「気になる」（まだ作らず、題名の写しを持って作る画面へ） | POST `/cases/draft` | `cases.draft` | `DraftCaseController`（題名を URL に載せないため POST） |
| 作る | POST `/cases` | `cases.store` | `CaseController@store`（`CreateCase`） |
| 事件の詳細（手がかり・見立て・予想が1画面） | GET `/cases/{case}?turn=&open=` | `cases.show` | `CaseController@show`（`CaseBoardQuery`） |
| なぞ・場面を直す | PATCH `/cases/{case}` | `cases.update` | `CaseController@update` |
| 消す | DELETE `/cases/{case}` | `cases.destroy` | `CaseController@destroy`（`DeleteCase`） |
| 閉じる・開き直す | POST `/cases/{case}/close`・DELETE `/cases/{case}/close` | `cases.close`・`cases.reopen` | `CloseCaseController`・`ReopenCaseController` |
| 見せ合う | POST `/cases/{case}/reveal` | `cases.reveal` | `RevealHypothesesController` |
| 手がかりを足す・直す・消す | POST `/cases/{case}/clues`・PATCH/DELETE `/clues/{clue}` | `clues.store`・`clues.update`・`clues.destroy` | `ClueController` |
| 見立てを足す・状態や本文を直す・消す | POST `/cases/{case}/hypotheses`・PATCH/DELETE `/hypotheses/{hypothesis}` | `hypotheses.store`・`hypotheses.update`・`hypotheses.destroy` | `HypothesisController` |
| ○×を付ける・外す | PUT/DELETE `/hypotheses/{hypothesis}/clues/{clue}` | `fits.update`・`fits.destroy` | `FitController` |
| 予想を立てる | POST `/hypotheses/{hypothesis}/predictions` | `predictions.store` | `PredictionController@store`（`MakePrediction`） |
| 答え合わせの画面 | GET `/predictions/{prediction}` | `predictions.show` | `PredictionController@show` |
| 答え合わせ | POST `/predictions/{prediction}/result` | `predictions.resolve` | `ResolvePredictionController` |
| 取り下げ | POST `/predictions/{prediction}/withdraw` | `predictions.withdraw` | `WithdrawPredictionController`（済んでいない確かめるタスクは消す） |
| ワトソンのひとことを閉じる | POST `/cases/{case}/hints/{rule}` | `hints.dismiss` | `DismissHintController` |

`{case}` は `CaseFile` に結びつけます（`Route::model('case', CaseFile::class)`）。持ち主かはルートの `->can()` で確かめ、書き込みのルートには `throttle:writes` を付けます。

### 2-5. 手引き（機能：Guide）

| 画面・操作 | メソッドと住所 | ルート名 |
|---|---|---|
| 手引きの入口 | GET `/guide` | `guide.index` |
| 技能 | GET `/guide/skills/{key}` | `guide.skills.show` |
| 演繹・帰納・アブダクション | GET `/guide/reasoning` | `guide.reasoning` |
| 名推理カードの一覧・カード | GET `/guide/scenes`・GET `/guide/scenes/{key}` | `guide.scenes.index`・`guide.scenes.show` |
| 自分の推理を保存して裏返す | POST `/guide/scenes/{key}/attempt` | `guide.scenes.attempt` |
| 練習メニューの一覧 | GET `/guide/practices` | `guide.practices.index` |
| 練習を始める（事件を作る画面へ） | GET `/cases/create?practice={key}` | `cases.create` |

### 2-6. ふりかえり（機能：Review）

| 画面・操作 | メソッドと住所 | ルート名 |
|---|---|---|
| 週のふりかえり | GET `/review/{week?}`（`2026-10-26` の形。省略は今週） | `review.show` |
| 週の問いを保存 | PUT `/review/{week}` | `review.update` |
| ノーベリ帳 | GET `/norbury` | `norbury.index` |
| 屋根裏の棚 | GET/POST `/attic`・PATCH/DELETE `/attic/{note}` | `attic.index`・`attic.store`・`attic.update`・`attic.destroy` |

### 2-7. 設定とカレンダー（機能：Settings・Calendar）

| 画面・操作 | メソッドと住所 | ルート名 |
|---|---|---|
| 設定 | GET `/settings`・PATCH `/settings` | `settings.show`・`settings.update` |
| データのダウンロード | GET `/settings/export` | `settings.export` |
| 退会 | DELETE `/account` | `account.destroy` |
| カレンダーとつなぐ前の説明（送る・送らない・読む項目） | GET `/calendar/connect` | `calendar.connect` |
| 同意して Google へ進む | POST `/calendar/connect` | `calendar.redirect` |
| Google から戻る | GET `/calendar/callback` | `calendar.callback` |
| つなぐのをやめる | DELETE `/calendar` | `calendar.disconnect` |

## 3. 画面の骨組み

- スマホが基本。下に固定のナビ4つ（きょう・事件・手引き・ふりかえり。項目は `config('suiri.nav')`）。パソコンでは左に縦のナビ。「きょう」は、タスクを作る・直す画面（`tasks.*`）にいるときも「いまここ」にします。
- 上の帯：画面の名前と、右に設定（アイコン）。
- 見た目は「手帳」：紙の色の背景、紺のインク、差し色に深い赤。見出しは明朝体（端末の書体：ヒラギノ明朝・游明朝・Noto Serif JP の順。外部の書体は読み込まない）、本文はゴシック体。影や浮き上がりの動きは付けない（平らな紙）。ダークモードでは「夜の書斎」（濃い紺の背景・生成りの文字）。
- 入力欄の下には、個人情報の注意書き（`<x-ui.privacy-note>`）を常に小さく出す。
- 画面のフッター：このアプリについて・プライバシーポリシー・利用規約、「医療機器ではありません」の一文。

## 4. 部品（Blade コンポーネント）の分け方

| 分類 | 置き場所 | 例 |
|---|---|---|
| 共通の部品 | `components/ui/` | `button`・`card`・`field`（ラベル・入力・エラー）・`textarea`・`select`・`badge`・`page-header`・`empty`（空のときの案内）・`flash`・`privacy-note`・`confirm`（消す前の確認） |
| 骨組み | `components/layouts/` | `app`（ログイン後）・`guest`（ログイン前）・`nav`・`footer` |
| タスク | `components/task/` | `item`（やることの1行。押すとその場で開いて直す・気になる・消す）・`check`（済みの丸）・`curious`（「気になる」）・`quick-add`（1行で書く欄と「今日」「明日」「いつか」）・`form`・`done-followup` など |
| 事件 | `components/case/` | `summary`（一覧の1件）・`header`・`next-step`（いまの一手）・`clue-list`・`clue-form`・`hypothesis-list`・`hypothesis-card`・`fit-list`・`fit-toggle`・`prediction-card`・`prediction-form`・`resolve-form`・`watson-note`・`reveal-board` など |
| きょう | `components/today/` | `next-step`（いまやることのカード）・`due-prediction`・`due-predictions`・`agenda`（きょうの予定とやること。1行で書く欄・やり残し・時刻順の並び・たたんだ枠を1枚に）・`agenda-event`・`later-tasks`（これから・いつか・済み の3つの枠）・`fold`（たたんだ枠1つ）・`task-rows`（枠の中のやることの並び）・`practice-suggestion`・`open-cases` |
| 手引き | `components/guide/` | `tabs`・`flow-steps`・`quote`（英語の引用と出典）・`scene-item`・`scene-front`・`scene-back`・`practice-item`・`reasoning-item`・`audience-toggle` など |
| ふりかえり | `components/review/` | `tabs`・`week-nav`・`stat`・`week-details`・`reflection-form`・`calibration`・`calibration-chart`（SVG。件数つき）・`miss-item`・`attic-form`・`attic-item` など |
| 設定 | `components/settings/` | `calendar-panel`・`consent-row`・`danger-zone` |
| ログイン | `components/auth/` | `google-button` |

全部の部品（9分類）の役目・受け取る値・使っている画面は、[部品説明書](components.md) にまとめています。部品を足したら書き足します。

## 5. 主なクラス

| 機能 | Action（用事） | Service・Query・そのほか |
|---|---|---|
| Auth | `Auth\LoginWithGoogle`（利用者を探す／作る。`config('suiri.invite_only')` が true の招待制のときだけ名簿を確かめる）・`Auth\AcceptTerms` | コマンド：`invite:add`・`invite:list`・`invite:remove` |
| Tasks | `Tasks\CreateTask`・`UpdateTask`・`CompleteTask`・`ReopenTask`・`DeleteTask` | `Tasks\TaskListQuery`（きょうの画面のタスクの枠と件数 `forToday()`・保存したあとに開く枠 `openOf()`・`?open=`／`?view=` の値をそろえる `normalizeOpen()`・済みにした直後の1件 `justDone()`）・`Tasks\SimilarTasksQuery`（題名の似た過去の実績） |
| Cases | `Cases\CreateCase`・`DeleteCase`・`CloseCase`・`ReopenCase`・`RevealHypotheses`・`AddClue`・`AddHypothesis`・`ChangeHypothesisStatus`・`DeleteHypothesis`・`SetFit`・`MakePrediction`（確かめるタスクも作る）・`ResolvePrediction`（確かめるタスクを済みにする）・`WithdrawPrediction`（済んでいない確かめるタスクを消す）・`DismissHint` | `Cases\CaseListQuery`（一覧と数）・`Cases\OpenCasesQuery`（今日の画面の捜査中の事件。手がかり・見立て・答え合わせ待ちの数つき）・`Cases\NewCaseDraft`・`Cases\CaseBoardQuery`・`Cases\NextStep`（いまの一手）・`Cases\RevealBoard`・`Watson\WatsonNotes`・`Watson\TextSigns` |
| Today | | `Today\DuePredictionsQuery`・`Today\AgendaQuery`（予定と今日のやることを時刻順に）・`Tasks\TaskListQuery::forToday()`（期日を過ぎた・これから・いつか・済み）・`Cases\OpenCasesQuery`・`Guide\DailyPractice` |
| Guide | `Guide\SaveSceneAttempt` | `Support\Guide\GuideLibrary`（教材の JSON を読む）・`Support\Guide\ReasoningSteps`・`Support\Guide\SceneAttemptReader`・`Services\Guide\DailyPractice` |
| Review | `Review\SaveWeeklyReflection`・`Attic\SaveAtticNote` | `Review\WeekReviewQuery`・`Review\WeekCasesQuery`・`Review\NorburyQuery`・`Review\AtticNotesQuery`・`Services\Review\WeeklyStats`・`Calibration`・`EstimateCheck`（見積もりの答え合わせ）・`ReviewWeek` |
| Settings | `Settings\UpdateSettings`・`Account\ExportUserData`・`Account\DeleteAccount` | |
| Calendar | `Calendar\ConnectCalendar`・`Calendar\DisconnectCalendar`・`Calendar\ApplyCalendarChanges`（取り込んだ日時をタスクに写す）・`Calendar\DeleteOrphanEvents`（連携が切れている間に消したタスクの予定を、つなぎ直したとき・全件を読み直したときに消す） | `Google\GoogleTokens`（更新・取り消し）・`Google\CalendarApi`（REST を呼ぶ。`Contracts\CalendarApi` の実装。テストは `FakeCalendarApi`）・`Google\GoogleCalendarAgenda`（今日の予定。`Contracts\CalendarAgenda` の実装。題名と時刻だけを受け取り5分だけ覚える）・`Google\TaskEvent`（タスク ⇔ 予定の形）・見張り役 `Observers\TaskObserver`・Job：`PushTaskToCalendar`・`PushAllTasksToCalendar`・`RemoveTaskFromCalendar`・`PullCalendarChanges`・コマンド `cache:prune-expired`（期限の過ぎたキャッシュを5分ごとに消す） |

## 6. 作業の分担（並行して作るときの持ち場）

最初の版は、この持ち場で土台 → 5つの機能を並行して作り、最後に統合の担当がつなぎ目（今日の画面の数・確かめるタスクの判定・「気になる」の送り方など）をそろえました。土台（共通）を先に作り、そのあと機能ごとに担当を分けます。担当は自分の持ち場のファイルだけを作り・直します。共通の部品を変えたいときは、統合の担当に頼みます。

| 担当 | 持ち場 |
|---|---|
| 土台 | `config/`・`app/Enums/`・`app/Models/`・`database/migrations/`・`database/factories/`・`app/Policies/`・`bootstrap/app.php`・`routes/web.php`・`components/ui/`・`components/layouts/`・ログイン・同意・公開ページ・設定の表示名と相棒 |
| タスクと今日 | `routes/web/tasks.php`・`routes/web/today.php`・`Controllers/Tasks`・`Controllers/Today`・`Requests/Tasks`・`Actions/Tasks`・`Queries/Tasks`・`Queries/Today`・`views/tasks`・`views/today`・`components/task`・`components/today/agenda` と `due-predictions`・`tests/Feature/Tasks`・`tests/Feature/Today` |
| 事件 | `routes/web/cases.php`・`Controllers/Cases`・`Requests/Cases`・`Actions/Cases`・`Queries/Cases`・`Services/Watson`・`config/watson.php`・`views/cases`・`components/case`・`components/today/open-cases`・`tests/Feature/Cases`・`tests/Unit/Watson` |
| カレンダー | `routes/web/calendar.php`・`Controllers/Calendar`・`Actions/Calendar`・`Contracts/CalendarApi.php`・`Services/Google`・`Jobs/`・`routes/console.php` の取り込みの予定・`components/settings/calendar-panel`・`tests/Feature/Calendar`・`tests/Unit/Google` |
| 手引き | `routes/web/guide.php`・`Controllers/Guide`・`Actions/Guide`・`Support/Guide`・`Services/Guide`・`database/data/guide/`・`views/guide`・`components/guide`・`components/today/practice-suggestion`・`tests/Feature/Guide` |
| ふりかえり・退会 | `routes/web/review.php`・`routes/web/settings.php` の書き出しと退会・`Controllers/Review`・`Controllers/Attic`・`Actions/Review`・`Actions/Attic`・`Actions/Account`・`Services/Review`・`Queries/Review`・`views/review`・`views/attic`・`views/norbury`・`components/review`・`components/settings/danger-zone`・`tests/Feature/Review` |
