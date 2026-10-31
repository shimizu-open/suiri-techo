# データ設計

表（テーブル）の形と、表どうしのつながりです。言葉は [言葉のルール](words.md) に合わせます。

- 更新：2026-10-31（最初の版の実装に合わせた。教材のファイルに `scenes.json` の `title` と `overview.json` を足した）

## 1. 決まり

- すべての記録は**利用者（users）を軸**にします。他人（お客さまなど）を表す表や列は作りません。
- 利用者の記録の表には `user_id` を持たせ、取り出すときは必ず持ち主で絞ります（Policy で強制し、テストで確かめる）。親の表を消したら子の表も消えるようにします（`cascadeOnDelete`）。退会すると、その人の行がすべて消えます。
- Google のトークンは Eloquent の `encrypted` キャストで暗号化して保存し、JSON に出しません。
- 日付だけの列（`date`）はアプリの中では `Y-m-d` の文字列で比べます（sqlite と MySQL の違いを避ける）。
- 状態や種類の列は文字列で持ち、`app/Enums/` の列挙にキャストします。

## 2. 表の一覧

```
users ─┬─ tasks ───────────────(prediction_id)──┐
       ├─ case_files ─┬─ clues ──┐              │
       │              ├─ hypotheses ─┬─ clue_hypothesis（○×）
       │              │              ├─ hypothesis_status_logs
       │              │              └─ predictions ┘
       │              └─ dismissed_hints
       ├─ attic_notes（case_file_id は任意）
       ├─ weekly_reflections
       ├─ scene_attempts
       └─ calendar_connections（1人1行）
invitations（招待の名簿。招待制のときの許可リスト。利用者とはメールアドレスで結ぶ）
```

## 3. 表の定義

### users（利用者）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| google_id | string unique | Google の利用者 ID（`sub`）。ログインの照合に使う |
| name | string(100) | 表示名。画面（設定）で入れられるのは50字まで（`config/suiri.php` の `limits.user_name`。Google から受け取る名前も50字で切る） |
| email | string unique | Google のメールアドレス |
| avatar_url | string nullable | アイコンの URL |
| partner_name | string(30) nullable | 相棒の呼び名（例「長男」）。入っていれば相棒モードを使える |
| default_scene | string(10) | `daily`／`family`／`work`。既定 `daily` |
| cue | string(100) nullable | 記録のきっかけ（例「夕食のあとに、気になったことを1つ」）。今日の画面に出す |
| agreed_at | timestamp nullable | 利用規約・プライバシーポリシー・年齢の確認に同意した日時。null なら同意画面へ |
| remember_token | string nullable | |
| timestamps | | |

パスワードの列は持ちません（Google ログインだけ）。

### invitations（招待）

招待制（`config('suiri.invite_only')` が true）のときだけ、登録を止めるのに使う名簿です。既定の一般公開では、行があれば登録したときに `used_at` を記録するだけです。

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| email | string unique | 招待制のときに新しく登録してよいメールアドレス（小文字にそろえる） |
| note | string(100) nullable | 運営者のメモ（例「保護者」） |
| used_at | timestamp nullable | 登録に使われた日時 |
| timestamps | | |

### tasks（タスク）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| user_id | FK users cascade | |
| title | string(100) | 題名 |
| memo | string(500) nullable | メモ |
| due_on | date nullable | 期日。null は「いつか」 |
| starts_at | string(5) nullable | 開始時刻 `HH:MM`。null は終日 |
| duration_minutes | smallint nullable | 予想時間（分）。カレンダーの予定の長さにもなる |
| actual_minutes | smallint nullable | かかった時間（分）。完了のときに任意で入れる（見積もりの答え合わせ） |
| kind | string(10) | `normal`／`verify`。既定 `normal` |
| prediction_id | FK predictions nullable nullOnDelete | 確かめるタスクのもとの予想 |
| done_at | timestamp nullable | 完了した日時 |
| calendar_event_id | string(64) nullable | Google の予定の ID（ハイフンを抜いた UUID。二重登録を防ぐ） |
| calendar_synced_at | timestamp nullable | 最後に書き出した日時 |
| timestamps | | |

索引：`(user_id, due_on)`、`(user_id, done_at)`。

### case_files（事件）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| user_id | FK users cascade | |
| title | string(120) | なぞ |
| scene | string(10) | `daily`／`family`／`work` |
| status | string(10) | `open`／`solved`／`cold`。既定 `open` |
| origin_kind | string(10) nullable | きっかけ：`task`／`event`／`practice` |
| origin_label | string(150) nullable | きっかけの写し（予定の題名と時刻、タスクの題名、練習メニューの名前）。Google の予定 ID は持たない |
| practice_key | string(40) nullable | 練習メニューから作ったときのキー |
| partner_mode | boolean | 相棒モードか。既定 false |
| revealed_at | timestamp nullable | 見せ合った日時。相棒モードで null の間は、互いの見立てを隠す |
| conclusion | text nullable | 閉じたときの結論 |
| closed_at | timestamp nullable | 閉じた日時 |
| timestamps | | |

索引：`(user_id, status)`。

### clues（手がかり）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| case_file_id | FK case_files cascade | |
| user_id | FK users cascade | 持ち主で絞るための写し |
| kind | string(10) | `seen`／`heard`／`absent` |
| body | string(500) | 本文 |
| timestamps | | created_at が「足した日時」 |

### hypotheses（見立て）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| case_file_id | FK case_files cascade | |
| user_id | FK users cascade | |
| author | string(10) | `self`／`partner`。既定 `self` |
| body | string(300) | 本文 |
| status | string(12) | `considering`／`likely`／`eliminated`／`confirmed`。既定 `considering` |
| timestamps | | |

### clue_hypothesis（○×：手がかりが見立てに合うか）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| hypothesis_id | FK hypotheses cascade | |
| clue_id | FK clues cascade | |
| fit | string(12) | `fits`／`contradicts` |
| timestamps | | |

一意：`(hypothesis_id, clue_id)`。

### hypothesis_status_logs（見立ての状態を変えた記録）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| hypothesis_id | FK hypotheses cascade | |
| user_id | FK users cascade | |
| from_status | string(12) | |
| to_status | string(12) | |
| created_at | timestamp | 変えた日時（「考えを変えた」記録。ふりかえりで使う） |

### predictions（予想）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| case_file_id | FK case_files cascade | |
| hypothesis_id | FK hypotheses cascade | どの見立てから導いたか |
| user_id | FK users cascade | |
| attic_note_id | FK attic_notes nullable nullOnDelete | 使った屋根裏の知識（任意。「この知識とこの手がかりから、この予想」） |
| statement | string(300) | 「〜が見えるはず」。**保存後は書き換えない** |
| confidence | tinyint | 50〜95（5刻み）。**保存後は書き換えない** |
| check_on | date | 確かめる日 |
| check_at | string(5) nullable | 確かめる時刻 `HH:MM`（任意） |
| result | string(10) nullable | `hit`／`miss`／`unclear`／`void`。null は答え合わせ待ち |
| result_note | string(300) nullable | 一言 |
| miss_reason | string(20) nullable | 外れたときの原因（`MissReason`） |
| resolved_at | timestamp nullable | 答え合わせした日時 |
| withdrawn_at | timestamp nullable | 取り下げた日時 |
| timestamps | | created_at が「書いた日時」（答え合わせの画面で必ず見せる） |

索引：`(user_id, result, check_on)`。

### attic_notes（屋根裏の棚）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| user_id | FK users cascade | |
| case_file_id | FK case_files nullable nullOnDelete | どの事件からわかったか（任意） |
| rule | string(300) | つまり？（一般則） |
| examples | string(500) nullable | たとえば？（具体例） |
| timestamps | | |

### weekly_reflections（週のふりかえり）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| user_id | FK users cascade | |
| week_start | date | 週の始まり（月曜日） |
| changed_case_file_id | FK case_files nullable nullOnDelete | 今週いちばん考えが変わった事件 |
| abstraction | string(500) nullable | つまり？ |
| concrete | string(500) nullable | たとえば？ |
| timestamps | | |

一意：`(user_id, week_start)`。

### scene_attempts（名推理カードへの自分の推理）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| user_id | FK users cascade | |
| scene_key | string(40) | `database/data/guide/scenes.json` のキー |
| answer | string(500) | 自分の推理 |
| timestamps | | |

一意：`(user_id, scene_key)`（書き直すと上書き）。

### dismissed_hints（閉じたワトソンのひとこと）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| user_id | FK users cascade | |
| case_file_id | FK case_files cascade | |
| rule | string(30) | `config/watson.php` の規則のキー |
| created_at | timestamp | |

一意：`(case_file_id, rule)`。

### calendar_connections（Google カレンダー連携）

| 列 | 型 | 説明 |
|---|---|---|
| id | bigint | |
| user_id | FK users cascade unique | 1人1行 |
| access_token | text | `encrypted`。JSON に出さない |
| refresh_token | text | `encrypted`。JSON に出さない |
| expires_at | timestamp | access_token の期限 |
| scopes | json | 許可されたスコープ |
| app_calendar_id | string nullable | アプリが作った専用カレンダーの ID |
| sync_token | text nullable | 差分取り込みの印（`encrypted` にしなくてよい。秘密ではない） |
| status | string(10) | `active`／`broken`（許可が取り消された・切れた） |
| last_synced_at | timestamp nullable | |
| timestamps | | |

### Laravel 標準の表

`sessions`・`cache`・`cache_locks`・`jobs`・`job_batches`・`failed_jobs` は Laravel の雛形のまま使います（セッション・キャッシュ・キューは database ドライバ）。今日の予定（Google カレンダーの予定の題名と時刻）は `cache` に5分だけ置き、記録としては保存しません。期限の過ぎた行は、コマンド `cache:prune-expired` が5分ごとに消します（`routes/console.php`）。`password_reset_tokens` は使わないので作りません。

## 4. 教材（表ではなくファイル）

技能・名推理カード・練習メニュー・考え方の説明・手引きの入口は、利用者のデータではなく「本の中身」なので、表ではなく `database/data/guide/*.json` に置き、`App\Support\Guide\GuideLibrary` が読み込んで（キャッシュして）返します。利用者の推理だけを `scene_attempts` に保存します。

| ファイル | 中身 |
|---|---|
| `skills.json` | 10の技能：`key`（S1〜S10）、`name`、`kid_name`、`summary`、`body`、`kid_body`、`quote_en`、`quote_source`、`practice`（`adult`／`kid`／`work`）、`pitfall` |
| `scenes.json` | 名推理カード：`key`、`title`（カードの見出し。例「初対面のワトソン」）、`story_ja`、`story_en`、`chapter`、`front`（手がかり）、`quote_en`、`reasoning`（ホームズの推理）、`verification`（どう確かめたか）、`skills`（技能のキー）、`is_mistake`（ホームズが外した場面か）、`kid_hint` |
| `practices.json` | 練習メニュー：`key`、`name`、`skill`、`origin`（由来の作品）、`description`、`prompts`（事件を作るときの問いかけ：`title`・`clue`・`hypothesis`・`prediction`）、`kid_description` |
| `reasoning.json` | 演繹・帰納・アブダクションの説明：`intro`・`kid_intro`、`items`（1つずつ：`key`・`name`・`kid_name`・`step`（アプリのどの段か）・`adult`・`kid`・`certainty`・`kid_certainty`・`holmes_scene`・`scene`（名推理カードのキー）・`in_app`）、`note`・`kid_note` |
| `overview.json` | 手引きの入口の中身：`intro`（手引きの一言）、`entries`（入口に並べる項目：`key`・`route`（ルート名）・`name`・`description`）、`flow_intro`、`steps`（アプリの流れの段とホームズの技能の対応：`key`・`step`・`skills`・`example`・`scene`（名推理カードのキーか null）・`reasoning`（`deduction`／`induction`／`abduction` か null））、`mistake_note` |
