---
paths:
  - "src/app/Services/Google/**"
  - "src/app/Contracts/**"
  - "src/app/Jobs/**"
  - "src/app/Observers/**"
  - "src/resources/views/calendar/**"
  - "src/app/Actions/Calendar/**"
  - "src/app/Actions/Auth/**"
  - "src/app/Http/Controllers/Calendar/**"
  - "src/app/Http/Controllers/Auth/**"
  - "src/app/Models/CalendarConnection.php"
  - "src/config/services.php"
  - "src/routes/web/calendar.php"
  - "src/routes/web/auth.php"
  - "src/routes/console.php"
  - "src/resources/views/components/settings/calendar-panel.blade.php"
  - "src/tests/Feature/Calendar/**"
  - "src/tests/Unit/Google/**"
---

# Google ログインとカレンダー連携

元は `requirements.md` の 4-1・4-5 と調査 R4（Google 連携）。

## スコープ（最小にする）

| 場面 | スコープ | いつ |
|---|---|---|
| ログイン | `openid` `email` `profile`（Socialite の既定のまま。名前・メール・アイコン・ID だけ） | 全員 |
| カレンダーに書く | `https://www.googleapis.com/auth/calendar.app.created`（アプリが作った専用カレンダーだけ） | 「カレンダーとつなぐ」を押した人だけ |
| 予定を読む（今日の予定・確かめる日の提案の14日分） | `https://www.googleapis.com/auth/calendar.events.owned.readonly`（自分のカレンダーの予定を読むだけ） | 同上 |

`calendar`（全権）・`calendar.events` は使わない。スコープを足すときは、プライバシーポリシーと連携前の説明も直す。

## 段階的な許可（ログインと連携は別）

- ログイン（`Auth\GoogleLoginController`）はオフラインの許可を求めない。利用者は `sub`（`users.google_id`）で照合し、ログイン後に `session()->regenerate()`。新しい人は、Google が確認済みのメールアドレスなら登録する（既定は一般公開）。`config('suiri.invite_only')` が true の招待制のときだけ、名簿（`invitations`）にないメールは新しく登録しない（「招待制です」）。
- 連携（`/calendar/connect`）は `access_type=offline`・`prompt=consent`・`include_granted_scopes=true`・`login_hint` を付けて追加の同意をもらう。戻り（`/calendar/callback`）で ①`sub` がログイン中の人と同じ ②求めたスコープが許可されている ③refresh token がある、を確かめ、どれかが欠けたら保存せずやり直しを案内する。
- 連携の前に、送る項目（タスクの題名・日時・戻るリンクと、どのタスクの予定かを見分ける番号（`suiri_task_id`）。番号はアプリの中の通し番号で、利用者の記録の中身ではないと添える）・受け取る項目・Google は米国の会社であることを1画面で示す。

## トークン

- `calendar_connections` の `access_token`・`refresh_token` は Eloquent の `encrypted` キャストで暗号化して保存し、`#[Hidden]`（または `$hidden`）で JSON・書き出しに出さない。テストで確かめる。
- 期限が近ければ refresh token で更新して保存し直す（`Google\GoogleTokens`）。`invalid_grant` を受けたら `status = broken` にして同期を止め、画面で「連携が切れました。つなぎ直してください」と出す。
- 解除・退会のときは Google の取り消し（revoke）を呼んでから行を消す。専用カレンダーも消すかは利用者が選ぶ。

## 書くのは専用カレンダーにだけ・送るのは題名と日時（とリンク・番号）だけ

- つないだら専用カレンダー「推理手帳」（タイムゾーン Asia/Tokyo）を作り、その ID を `app_calendar_id` に保存。**メインのカレンダーには書かない。** 利用者の予定を勝手に動かさない。
- 予定に入れるのは**タスクの題名・日時・アプリに戻るリンクと、どのタスクの予定かを見分ける番号（`suiri_task_id`）だけ**（番号は利用者に見えない `extendedProperties.private` に入れる。番号はアプリの中の通し番号で、利用者の記録の中身ではない。取り込みと残った予定の片づけで使う）。確かめるタスクの題名は決まった言葉（`calendar.verify_summary`）。メモ・事件の手がかり・見立て・予想の文などの本文は送らない（`bin/check` が `Services/Google`・`Jobs` での本文の列を止める）。
- 確かめるタスクの予定は「予定なし（時間を塞がない）」にし、答え合わせの画面へのリンクを付ける。
- 予定の ID はタスクごとに決めた値（ハイフンを抜いた UUID）にして二重登録を防ぐ（`tasks.calendar_event_id`）。
- 取り込み（15分ごと、`routes/console.php` の予定 → `PullCalendarChanges` → `Calendar\ApplyCalendarChanges`）：専用カレンダーで日時を動かされたらタスクの期日を合わせ（答え合わせ待ちの予想の確かめるタスクなら予想の `check_on`・`check_at` も同じトランザクションで合わせる）、消されたらつながりだけ外す（タスクは消さない）。消された知らせ（`cancelled`）は ID だけで届くことがあるので先に見て、その人のタスクを `calendar_event_id` で探す。Google 側で新しく作られた予定は取り込まない。差分は sync token、印がない・410 なら全件を読み直し、ページをまたいで集めた予定の ID とタスクの `calendar_event_id` を比べて、Google にないもののつながりを外してから新しい印を保存する（比べるのは読み始める前に書き出し済み＝`calendar_synced_at` があるタスクだけ）。
- 残った予定の片づけ（`Calendar\DeleteOrphanEvents`）：連携が切れている間にタスクを消すと、予定を消す仕事は何もせずに終わるので、予定だけが Google に残る。つなぎ直したとき（`PushAllTasksToCalendar`）と全件を読み直したときに、専用カレンダーの予定のうち `suiri_task_id` が本人の今あるタスクに当たらないものを消す。専用カレンダーはアプリだけが書く場所なので消してよい。印のない予定（利用者が手で足したもの）は消さない。
- 予定（`Contracts\CalendarAgenda`：今日の予定 `eventsOn` と、確かめる日の提案のこれからの予定 `upcoming`）は保存しない。題名と時刻だけを1回の問い合わせで読んで数分だけキャッシュし、提案の題名は画面に出すだけ（`Queries/Cases/CheckDateSuggestionsQuery`）。

## 失敗してもアプリの保存は成功させる

- Google への書き出しは後回しの仕事（`PushTaskToCalendar`・`RemoveTaskFromCalendar`。worker の箱が片づける）。受付から直接 Google を呼ばない。**Google が止まっていても、タスクや予想の保存は成功させ、書き出しはあとで再送する**。
- 失敗の記録（ログ）には id とエラーの種類だけを書き、題名や本文は書かない。
- Google の API は `App\Contracts\CalendarApi` 越しに使い（実装は `Services\Google\CalendarApi`。Laravel の HTTP クライアントで REST を直接呼ぶ）、テストでは偽物に差し替える。

## 運用

- 同意画面は「本番（未審査）」で運用し、利用者は通算100人まで。テストのままにしない（refresh token が7日で切れる）。ロゴは上げない。
- 鍵（`GOOGLE_CLIENT_ID`・`GOOGLE_CLIENT_SECRET`）は `.env` にだけ書く。本番の `GOOGLE_REDIRECT_URI` は https の絶対 URL。Google のアカウントは学習用に決めたものを使う（オーナーの決まり）。
