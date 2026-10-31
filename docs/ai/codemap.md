# Codemap（AI 向け索引）

目的：機能・住所・表・決まりから「どのファイルのどのシンボルを見ればよいか」を一度で引く。読み手は AI のセッション。

- パスはプロジェクト直下からの相対。Laravel 本体は `src/`（コンテナの中では `/data`）。`App\` は `src/app/`。
- 行番号は書かない（変わるため）。クラス名・メソッド名・ルート名・config のキーで `grep -rn` して引く。
- 画面の言葉＝表＝コードの名前の対応は `.claude/rules/domain.md`（人間向けは `docs/design/words.md`）。表の列は `docs/design/data-model.md`、部品（Blade コンポーネント）の受け取る値は `docs/design/components.md`、層の決まりと行数の上限は `.claude/rules/architecture.md`。
- 使わない言葉は `docs/design/words.md` の 3。この索引にも書かない。
- この索引は 2026-10-31 の `src/`（コミット `fe3a4cd` の時点）から作り、そのあとの仕上げ（閉じた事件への書き込みを受付で断る `write` の能力・答え合わせの Action の鍵・残った予定の片づけ `DeleteOrphanEvents`・`tasks.update`／`tasks.destroy` の回数制限）と、そのあとの作り直し（`d3e1746`：1問ずつの画面 `cases.create-steps`・いまやることのカード1枚の事件の画面 `cases.board`・入力の提案・ChatGPT と Claude の読むだけの窓口）、古い画面と部品の片づけ（`cases/show`・`cases/create` と `components/case/*` の古い部品を消した）、一般公開と招待制の切り替え（`config('suiri.invite_only')`）も反映した。`src/` の決まりを変えたら、ここも直す（`.claude/rules/docs.md`）。

---

## 1. 住所 → ルート名 → 受付 → 検証 → 能力 → 用事 → 画面

### 1-0. しくみ

| もの | 場所・シンボル |
|---|---|
| ルートの入口 | `src/routes/web.php`：`glob(__DIR__.'/web/*.php')` を名前の順に `require` するだけ。中身は `src/routes/web/{ai,auth,calendar,cases,guide,legal,review,settings,tasks,today}.php`。新しい機能はファイルを置けば読まれる |
| 予定・コマンド | `src/routes/console.php`（6 章） |
| ミドルウェア | `src/bootstrap/app.php`：別名 `agreed` → `App\Http\Middleware\EnsureAgreed`、全応答に `SecurityHeaders` を `append`、`redirectGuestsTo` → `home`、`redirectUsersTo` → `Landing::url()`（＝`route('today')`）、`trustProxies(at: '*')`、死活確認 `/up` |
| 回数制限（名前つき） | `App\Providers\AppServiceProvider::boot` の `RateLimiter::for`：`auth`（10/分・IP）・`writes`（60/分・人か IP）・`account`（5/分）・`calendar`（10/分）。AI の窓口の分は `App\Providers\AiConnectServiceProvider::boot`：`mcp`（120/分・人）・`oauth`（60/分・IP）・`oauth-register`（20/時・IP） |
| AI の窓口の住所 | `src/routes/ai.php`（laravel/mcp がミドルウェアのグループなしで読む。案内・自己登録・通行証・`/mcp`）と `src/routes/web/ai.php`（同意の画面・つなぐのをやめる）。表は 1-11 |
| `{case}` の結びつけ | `AppServiceProvider::boot` の `Route::model('case', CaseFile::class)`（`case` は PHP の予約語） |
| 持ち主の確かめ | ルートの `->can('view'|'update'|'delete', '…')` → `app/Policies/*Policy`（全部 `Policies\Concerns\OwnerOnly`。`user_id` の一致だけ）。他人は 403。事件・手がかり・見立ては `->can('write', '…')` も持つ（`Policies\Concerns\WritesOpenCase`：持ち主で、事件が `isOpen()` のときだけ。閉じた事件は `suiri.cases.closed_message` の 403） |
| 状態の確かめ | FormRequest の `authorize()`（捜査中か・答え合わせ待ちか など）。偽なら 403 |
| 書き込みの戻り先 | 事件まわりは `Cases\Concerns\RedirectsToCase::backToCase($case, $status, $texts, $anchor, $turn)`（`status` と `watson_alert` をフラッシュ） |
| 一覧を作る | `php artisan route:list --json`（コンテナ経由）。全 80 本（下の表に全部ある。AI の窓口の分は laravel/mcp・Passport が足すものも含む） |

表の「制限」列が空なら、そのルートには `throttle` がない。ログインの要るルートは全部 `auth`、同意の画面とログアウト以外は `auth` + `agreed`。

### 1-1. トップ・ログイン・同意（`routes/web/auth.php`）

| メソッド | 住所 | ルート名 | 受付 | 検証（FormRequest） | 制限 | 用事・組み立て | 画面・戻り先 |
|---|---|---|---|---|---|---|---|
| GET | `/` | `home` | `HomeController`（`__invoke`） | — | — | `GoogleLoginController::enabled()` | `home`（`googleReady`）。ログイン済みは `Landing::url()` へ |
| GET | `/auth/google` | `auth.google` | `Auth\GoogleLoginController@redirect` | — | `guest`・`throttle:auth` | Socialite `google`・`enablePKCE()`・`prompt=select_account` | Google へ。鍵がなければ `home` に「準備中」 |
| GET | `/auth/google/callback` | `auth.google.callback` | `Auth\GoogleLoginController@callback` | — | `guest`・`throttle:auth` | `Actions\Auth\LoginWithGoogle::handle`（断るときは `Exceptions\LoginRefused`）→ `Auth::login(remember: true)` → `session()->regenerate()` | 同意前は `agree.show`、済みは `intended(Landing::url())`。失敗は `home` に error |
| POST | `/logout` | `logout` | `Auth\LogoutController` | — | `auth` | `Auth::logout`・`invalidate`・`regenerateToken` | `home` |
| GET | `/agree` | `agree.show` | `Auth\AgreementController@show` | — | `auth` | `User::hasAgreed()` | `auth.agree`（`layouts.guest`）。同意済みは `Landing::url()` |
| POST | `/agree` | `agree.store` | `Auth\AgreementController@store` | `Auth\AcceptTermsRequest`（`accept_terms`・`confirm_age` が `accepted`） | `auth`・`throttle:writes` | `Actions\Auth\AcceptTerms::handle`（`agreed_at` を一度だけ入れる） | `intended(Landing::url())` |

### 1-2. 公開ページ（`routes/web/legal.php`。ミドルウェアなし）

| メソッド | 住所 | ルート名 | 受付 | 画面（渡す値） |
|---|---|---|---|---|
| GET | `/about` | `about` | `LegalController@about` | `legal.about` |
| GET | `/privacy` | `privacy` | `LegalController@privacy` | `legal.privacy`（`legal`＝`config('legal')`・`agendaMinutes`＝`suiri.calendar.agenda_cache_seconds`÷60） |
| GET | `/terms` | `terms` | `LegalController@terms` | `legal.terms`（`legal`） |

### 1-3. 今日（`routes/web/today.php`）

| メソッド | 住所 | ルート名 | 受付 | 組み立て | 画面 |
|---|---|---|---|---|---|
| GET | `/today` | `today` | `Today\TodayController`（`__invoke`） | `Today\DuePredictionsQuery::for`・`Today\AgendaQuery::for`／`overdueCount`（答え合わせ待ちに出した予想の id を渡して二重に出さない）・`Services\Guide\DailyPractice::for`・`Cases\OpenCasesQuery::get`・`Tasks\TaskListQuery::justDone(session('task_done'))` | `today.index` |

### 1-4. タスク（`routes/web/tasks.php`。`Route::resource('tasks')->only([...])` と3本）

| メソッド | 住所 | ルート名 | 受付 | 検証 | 能力 | 制限 | 用事・組み立て | 画面・戻り先 |
|---|---|---|---|---|---|---|---|---|
| GET | `/tasks?view=today\|upcoming\|someday\|done` | `tasks.index` | `Tasks\TaskController@index` | — | — | — | `TaskListQuery::normalize`・`list`・`counts`・`justDone` | `tasks.index` |
| GET | `/tasks/create` | `tasks.create` | `TaskController@create` | — | — | — | `Tasks\SimilarTasksQuery::for($user, old('title'))` | `tasks.create` |
| POST | `/tasks` | `tasks.store` | `TaskController@store` | `Tasks\StoreTaskRequest` | — | `writes` | `Actions\Tasks\CreateTask` | `back=today` なら `today`、ほかは `TaskListQuery::viewOf` のタブ |
| POST | `/tasks/draft` | `tasks.draft` | `Tasks\DraftTaskController` | — | — | — | `withInput(title, due_on, back)` | `tasks.create`（題名を URL に載せない） |
| GET | `/tasks/{task}/edit` | `tasks.edit` | `TaskController@edit` | — | `update,task` | — | `SimilarTasksQuery::for($user, $task->title, $task->id)` | `tasks.edit` |
| PUT/PATCH | `/tasks/{task}` | `tasks.update` | `TaskController@update` | `Tasks\UpdateTaskRequest` | `update,task` | `writes` | `Actions\Tasks\UpdateTask`（→ `SyncPredictionCheck`） | そのタスクのタブ |
| DELETE | `/tasks/{task}` | `tasks.destroy` | `TaskController@destroy` | `Tasks\DeleteTaskRequest`（答え合わせ待ちの予想の確かめるタスクは 403） | `delete,task` | `writes` | `Actions\Tasks\DeleteTask` | いたタブ |
| POST | `/tasks/{task}/done` | `tasks.done` | `Tasks\CompleteTaskController` | `Tasks\CompleteTaskRequest`（`authorize`：`! answeredByPrediction()`・`actual_minutes`） | `update,task` | `writes` | `Actions\Tasks\CompleteTask` | `back()`＋`task_done`（さそう枠） |
| DELETE | `/tasks/{task}/done` | `tasks.undone` | `Tasks\ReopenTaskController` | `Tasks\ReopenTaskRequest`（同上） | `update,task` | `writes` | `Actions\Tasks\ReopenTask` | `back()` |

`Route::resource` の `->middlewareFor()` は同じ動作への指定をあとから書いたもので上書きするので、動作ごとに1回で配列にまとめて書いている（`tasks.update`・`tasks.destroy` に `throttle:writes` と `can:` の両方が付くことは `tests/Feature/Tasks/TaskRouteMiddlewareTest.php` が確かめる）。

### 1-5. 事件・手がかり・見立て・○×・予想（`routes/web/cases.php`）

`cases.index`・`cases.create`・`cases.draft`・`cases.show`・`predictions.show` 以外は `throttle:writes`。能力の `write` は「持ち主で、事件が捜査中」（`Policies\Concerns\WritesOpenCase`）。閉じた事件への手がかり・見立て・○×・予想・見せ合うは 403（`suiri.cases.closed_message`）。答え合わせ・取り下げは閉じた事件でもできる（待っている予想の結果を残す・確かめるタスクを片づけるため）。

| メソッド | 住所 | ルート名 | 受付 | 検証 | 能力 | 用事・組み立て | 画面・戻り先 |
|---|---|---|---|---|---|---|---|
| GET | `/cases?status=&scene=` | `cases.index` | `Cases\CaseController@index` | — | — | `Queries\Cases\CaseListQuery::handle` | `cases.index` |
| GET | `/cases/create?origin=practice&practice=` | `cases.create` | `CaseController@create` | — | — | `Queries\Cases\NewCaseDraft::for($user, old() + query の origin・practice だけ)` | `cases.create-steps`（4つの問いを1問ずつ。部品 `components/case-new/*`・動き `js/parts/stepper.js`） |
| POST | `/cases/draft` | `cases.draft` | `Cases\DraftCaseController` | — | — | `withInput(origin, label)` | `cases.create`（題名の写しを URL に載せない） |
| POST | `/cases` | `cases.store` | `CaseController@store` | `Cases\StoreCaseRequest` | — | `Actions\Cases\CreateCase` | `backToCase`（W-5 の確かめ：title・clue_body・hypothesis_body） |
| GET | `/cases/{case}?turn=partner\|self&open=add-clue\|add-hypothesis\|edit-case\|edit-clue-{id}\|edit-hyps\|fit-{id}\|predict-{id}\|close` | `cases.show` | `CaseController@show` | — | `view,case` | `Queries\Cases\CaseBoardQuery::handle`（`session('watson_alert')` を渡す） | `cases.board`（いまやることのカードを1枚。部品 `components/case-step/*`、相棒モードは `components/case-partner/*`） |
| PATCH | `/cases/{case}` | `cases.update` | `CaseController@update` | `Cases\UpdateCaseRequest`（title・scene） | `update,case` | 受付で `$case->update(validated)` | `backToCase` |
| DELETE | `/cases/{case}` | `cases.destroy` | `CaseController@destroy` | — | `delete,case` | `Actions\Cases\DeleteCase` | `cases.index` |
| POST | `/cases/{case}/close` | `cases.close` | `Cases\CloseCaseController` | `Cases\CloseCaseRequest`（`authorize`：`isOpen()`） | `update,case` | `Actions\Cases\CloseCase` | `backToCase` |
| DELETE | `/cases/{case}/close` | `cases.reopen` | `Cases\ReopenCaseController` | — | `update,case` | `Actions\Cases\ReopenCase` | `backToCase` |
| POST | `/cases/{case}/reveal` | `cases.reveal` | `Cases\RevealHypothesesController` | — | `write,case` | `Actions\Cases\RevealHypotheses` | `backToCase(…, 'reveal')` |
| POST | `/cases/{case}/clues` | `clues.store` | `Cases\ClueController@store` | `Cases\StoreClueRequest` | `write,case` | `Actions\Cases\AddClue` | `backToCase(…, 'clues')` |
| PATCH | `/clues/{clue}` | `clues.update` | `ClueController@update` | `Cases\UpdateClueRequest`（＝Store と同じ） | `write,clue` | 受付で `$clue->update` | `backToCase(…, 'clues')` |
| DELETE | `/clues/{clue}` | `clues.destroy` | `ClueController@destroy` | — | `write,clue` | `$clue->delete()`（○× は cascade） | `backToCase(…, 'clues')` |
| POST | `/cases/{case}/hypotheses` | `hypotheses.store` | `Cases\HypothesisController@store` | `Cases\StoreHypothesisRequest` | `write,case` | `Actions\Cases\AddHypothesis` | `backToCase(…, 'hypotheses', turnOf)` |
| PATCH | `/hypotheses/{hypothesis}` | `hypotheses.update` | `HypothesisController@update` | `Cases\UpdateHypothesisRequest`（body か status） | `write,hypothesis` | body は受付で `update`、status は `Actions\Cases\ChangeHypothesisStatus` | `backToCase(…, 'hyp-{id}', turnOf)` |
| DELETE | `/hypotheses/{hypothesis}` | `hypotheses.destroy` | `HypothesisController@destroy` | — | `write,hypothesis` | `Actions\Cases\DeleteHypothesis` | `backToCase(…, 'hypotheses')` |
| PUT | `/hypotheses/{hypothesis}/clues/{clue}` | `fits.update` | `Cases\FitController@update` | `Cases\UpdateFitRequest`（同じ事件か・PUT は `fit` 必須） | `write,hypothesis`＋`write,clue` | `Actions\Cases\SetFit`（`Fit::from`） | `FitController::back`（`#hyp-{id}`） |
| DELETE | `/hypotheses/{hypothesis}/clues/{clue}` | `fits.destroy` | `FitController@destroy` | `Cases\UpdateFitRequest` | 同上 | `SetFit::handle(…, null)` | 同上 |
| POST | `/hypotheses/{hypothesis}/predictions` | `predictions.store` | `Cases\PredictionController@store` | `Cases\StorePredictionRequest`（`authorize`：`! hidesHypotheses()`） | `write,hypothesis` | `Actions\Cases\MakePrediction`（確かめるタスクも作る） | `backToCase(…, 'hyp-{id}')` |
| GET | `/predictions/{prediction}` | `predictions.show` | `PredictionController@show` | — | `view,prediction` | `load(['hypothesis','caseFile','atticNote'])` | `predictions.show`（答え合わせの画面） |
| POST | `/predictions/{prediction}/result` | `predictions.resolve` | `Cases\ResolvePredictionController` | `Cases\ResolvePredictionRequest`（`authorize`：`isPending()`） | `update,prediction` | `Actions\Cases\ResolvePrediction`（行に鍵をかけて読み直し、待機中でなければ何もしない） | `predictions.show` |
| POST | `/predictions/{prediction}/withdraw` | `predictions.withdraw` | `Cases\WithdrawPredictionController` | `Cases\WithdrawPredictionRequest`（`authorize`：`isPending()`） | `update,prediction` | `Actions\Cases\WithdrawPrediction` | `backToCase(…, 'hyp-{id}')` |
| POST | `/cases/{case}/hints/{rule}` | `hints.dismiss` | `Cases\DismissHintController` | — | `update,case` | `Actions\Cases\DismissHint`。`{rule}` は `->whereIn('rule', array_keys(config('watson.rules')))` | `cases.show` |

予想の文と確かさを直すルートは作らない（7 章の 1）。

### 1-6. 手引き（`routes/web/guide.php`。`prefix('guide')->name('guide.')`）

| メソッド | 住所 | ルート名 | 受付 | 組み立て | 画面 |
|---|---|---|---|---|---|
| GET | `/guide` | `guide.index` | `Guide\GuideController@index` | `GuideLibrary::overview`・`skills`・`scenes`・`reasoning()['items']` | `guide.index` |
| GET | `/guide/reasoning?kid=` | `guide.reasoning` | `GuideController@reasoning` | `GuideLibrary::reasoning`・`scenes`。`kid`＝`?kid` があればそれ、なければ `prefersKidWords()` | `guide.reasoning` |
| GET | `/guide/skills/{key}?kid=` | `guide.skills.show` | `Guide\SkillController@show` | `GuideLibrary::skill`（なければ 404）・`practicesFor`・`scenesFor`・`skillNames`・`neighbors('skills')` | `guide.skills.show` |
| GET | `/guide/scenes` | `guide.scenes.index` | `Guide\SceneController@index` | `GuideLibrary::scenes`・`SceneAttemptReader::keys` | `guide.scenes.index` |
| GET | `/guide/scenes/{key}?side=back` | `guide.scenes.show` | `SceneController@show` | 表は `Arr::except($scene, SceneController::BACK_ONLY)`（reasoning・verification・quote_en を渡さない）、裏は `ReasoningSteps::split`。`SceneAttemptReader::find` | `guide.scenes.show` |
| POST | `/guide/scenes/{key}/attempt` | `guide.scenes.attempt` | `Guide\SaveSceneAttemptController`（`throttle:writes`） | `Guide\StoreSceneAttemptRequest` → `Actions\Guide\SaveSceneAttempt`（空なら保存しない） | `guide.scenes.show` の `side=back` |
| GET | `/guide/practices` | `guide.practices.index` | `Guide\PracticeController@index` | `GuideLibrary::practices`・`skillNames` | `guide.practices.index` |

練習を始めるボタンは `route('cases.create', ['origin' => 'practice', 'practice' => key])`（`components/guide/practice-item`・`components/today/practice-suggestion`）。

### 1-7. ふりかえり・ノーベリ帳・屋根裏の棚（`routes/web/review.php`）

| メソッド | 住所 | ルート名 | 受付 | 検証 | 能力・制限 | 用事・組み立て | 画面・戻り先 |
|---|---|---|---|---|---|---|---|
| GET | `/review/{week?}?range=all\|week` | `review.show` | `Review\ReviewController@show` | — | — | `Services\Review\ReviewWeek::fromParam`（ない日付・未来は 404、月曜でなければ月曜の住所へ転送）→ `Queries\Review\WeekReviewQuery::for` | `review.show` |
| PUT | `/review/{week}` | `review.update` | `ReviewController@update` | `Review\UpdateWeeklyReflectionRequest` | `throttle:writes` | `Actions\Review\SaveWeeklyReflection` | `review.show` |
| GET | `/norbury` | `norbury.index` | `Review\NorburyController@index` | — | — | `Queries\Review\NorburyQuery::misses`・`reasonCounts` | `norbury.index` |
| GET | `/attic?edit={id}` | `attic.index` | `Attic\AtticNoteController@index` | — | — | `Queries\Review\AtticNotesQuery::notes`・`caseOptions` | `attic.index` |
| POST | `/attic` | `attic.store` | `AtticNoteController@store` | `Attic\StoreAtticNoteRequest` | `throttle:writes` | `Actions\Attic\SaveAtticNote` | `attic.index` |
| PUT/PATCH | `/attic/{note}` | `attic.update` | `AtticNoteController@update` | `Attic\UpdateAtticNoteRequest`（＝Store と同じ） | `throttle:writes`・`can:update,note` | `SaveAtticNote::handle($user, $data, $note)` | `attic.index` |
| DELETE | `/attic/{note}` | `attic.destroy` | `AtticNoteController@destroy` | — | `throttle:writes`・`can:delete,note` | `$note->delete()`（予想の `attic_note_id` は nullOnDelete） | `attic.index` |

`{week}` は `->where('week', '\d{4}-\d{2}-\d{2}')`。`{note}` は `->parameters(['attic' => 'note'])` で `AtticNote` に結ぶ。

### 1-8. 設定・書き出し・退会（`routes/web/settings.php`）

| メソッド | 住所 | ルート名 | 受付 | 検証 | 制限 | 用事 | 画面・戻り先 |
|---|---|---|---|---|---|---|---|
| GET | `/settings` | `settings.show` | `Settings\SettingsController@show` | — | — | `load('calendarConnection')`・`Scene::options()`・`config('suiri.cue_examples')`・`Queries\Ai\ConnectedAppsQuery::for($user)`（`aiApps`） | `settings.show` |
| PATCH | `/settings` | `settings.update` | `SettingsController@update` | `Settings\UpdateSettingsRequest` | `writes` | `Actions\Settings\UpdateSettings` | `settings.show` |
| GET | `/settings/export` | `settings.export` | `Account\ExportUserDataController` | — | `account` | `Actions\Account\ExportUserData` | JSON の添付（`suiri.review.export_filename`-日付.json） |
| DELETE | `/account` | `account.destroy` | `Account\DeleteAccountController` | `Account\DeleteAccountRequest`（`confirmation` が `suiri.review.delete_confirmation`） | `account` | `Auth::logout` → `Actions\Account\DeleteAccount` → `invalidate` | `home` |

### 1-9. カレンダー連携（`routes/web/calendar.php`。全部 `auth`・`agreed`・`throttle:calendar`。住所に連携の id を入れない）

| メソッド | 住所 | ルート名 | 受付 | 用事 | 画面・戻り先 |
|---|---|---|---|---|---|
| GET | `/calendar/connect` | `calendar.connect` | `Calendar\ConnectCalendarController@show` | `GoogleTokens::configured()` | `calendar.connect`（送る・送らない・読む項目。`config('suiri.calendar.consent')`） |
| POST | `/calendar/connect` | `calendar.redirect` | `ConnectCalendarController@redirect` | Socialite：`redirectUrl(route('calendar.callback'))`・`scopes(suiri.calendar.scopes)`・`access_type=offline`・`prompt=consent`・`include_granted_scopes=true`・`login_hint`・`enablePKCE()` | Google へ |
| GET | `/calendar/callback` | `calendar.callback` | `ConnectCalendarController@callback` | `Actions\Calendar\ConnectCalendar::handle`（断るときは `Services\Google\Exceptions\CalendarConnectRefused`） | `settings.show` |
| DELETE | `/calendar` | `calendar.disconnect` | `Calendar\DisconnectCalendarController` | `Actions\Calendar\DisconnectCalendar::handle($user, $request->boolean('delete_calendar'))` | `settings.show` |

### 1-10. Laravel が足すルート

| メソッド | 住所 | ルート名 | 中身 |
|---|---|---|---|
| GET | `/up` | （なし） | `bootstrap/app.php` の `health: '/up'` |
| GET・PUT | `/storage/{path}` | `storage.local`・`storage.local.upload` | `config/filesystems.php` の local ディスクの `'serve' => true` が足すクロージャ（アプリのコードではない） |

### 1-11. ChatGPT・Claude とつなぐ（`routes/web/ai.php`・`routes/ai.php`。要件 4-11・`rules/ai-connect.md`）

| メソッド | 住所 | ルート名 | 置き場所・ミドルウェア | 受付 | 中身 |
|---|---|---|---|---|---|
| GET | `/.well-known/oauth-protected-resource{/path}`・`/.well-known/oauth-authorization-server{/path}` | `mcp.oauth.*` | `routes/ai.php`・`throttle:oauth` | laravel/mcp（`Mcp::oauthRoutes(registration: false)`） | 通行証の出し方の案内（`resource`＝`…/mcp`・S256・登録と通行証の住所） |
| POST | `/oauth/register` | `mcp.oauth.register` | 同上＋`throttle:oauth-register` | `Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController` | AI の自己登録。戻り先は `config('mcp.redirect_domains')` の3つだけ（ほかは 400） |
| POST | `/oauth/token` | `passport.token` | `routes/ai.php`・`throttle:oauth`（CSRF の外） | Passport の `AccessTokenController@issueToken` | 引換券＋PKCE の合言葉・更新の札を通行証に |
| POST | `/mcp`（GET・DELETE は 405） | （laravel/mcp） | `routes/ai.php`：`RejectForeignOrigin`・`auth:api`・`CheckToken::using('mcp:use')`・`throttle:mcp` | `App\Mcp\Servers\SuiriServer`（`Mcp::web`） | 読むだけの道具4つ（2-15） |
| GET | `/oauth/authorize` | `passport.authorizations.authorize` | `routes/web/ai.php`：`auth`・`agreed` | Passport の `AuthorizationController@authorize` | 同意の画面 `oauth.authorize`（値は `Support\Ai\ConsentView::from`。`Passport::authorizationView`） |
| POST・DELETE | `/oauth/authorize` | `passport.authorizations.approve`・`.deny` | 同上＋`throttle:writes` | Passport の `ApproveAuthorizationController@approve`・`DenyAuthorizationController@deny` | 戻り先へ `code` か `error=access_denied` |
| DELETE | `/settings/ai/{app}`（`whereUuid`） | `settings.ai.revoke` | 同上＋`throttle:writes` | `Settings\RevokeAiAppController` | `ConnectedAppsQuery` にない番号は 404 → `Actions\Ai\RevokeAiAccess::handle` → `settings.show`（`ai_connect.panel.stopped`） |

- Passport 自身の住所は `AiConnectServiceProvider::register` の `Passport::ignoreRoutes()` で止め、上で**同じルート名のまま**置き直している（laravel/mcp の案内の中身と埋め込み禁止の札がルート名で動くため）。デバイスの許可は `Passport::$deviceCodeGrantEnabled = false`。
- 未ログインで `/oauth/authorize` に来た人は `bootstrap/app.php` の `redirectGuestsTo` で `auth.google` へ（ほかの住所はトップへ）。`shouldRenderJsonWhen` に `mcp`・`oauth/token`・`oauth/register` を足し、通行証なしの `/mcp` は 401 の JSON（`WWW-Authenticate` つき）。

---

## 2. 機能 → ファイル → シンボル

受付・FormRequest・Action の住所との対応は 1 章。ここは「その機能のロジックがどこにあるか」。

### 2-1. 事件（CaseFile）

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 作る画面の初めの値 | `app/Queries/Cases/NewCaseDraft.php` | `for($user, $query)`：`origin`（`OriginKind`）・`practice`（`GuideLibrary::practice`。なければきっかけなし）・`label`・`title`（練習の `prompts.title`）・`scene`（`default_scene`）・`firstWork`（仕事の場面の事件がまだないか）・`partner` |
| 作る | `app/Http/Requests/Cases/StoreCaseRequest.php`・`app/Actions/Cases/CreateCase.php` | `prepareForValidation`：`partner_mode` は `hasPartner()` の人だけ、`practice_key` は `origin_kind=practice` のときだけ、`practice_key` は `GuideLibrary::practices()` のキーだけ。`CreateCase::handle`：最初の手がかり・見立てを同じトランザクションで（相棒モードは見立てを受け取らない） |
| 一覧 | `app/Queries/Cases/CaseListQuery.php` | `handle($user, $status, $scene)`（既定は `CaseStatus::Open`・`latest('updated_at')`・`suiri.cases.per_page`）・`withCounts($query)`（`clues_count`・`hypotheses_count`・`pending_predictions_count`・`due_predictions_count`。今日の画面も使う） |
| 事件の画面を組み立てる | `app/Queries/Cases/CaseBoardQuery.php` | `handle($case, $user, $turn, $open, $alert)` → `case`・`hypotheses`（相棒の番で絞る）・`hiddenCount`・`turn`・`partnerName`・`selfName`・`kid`・`calendarConnected`・`step`・`watson`・`board`・`atticOptions`（`suiri.cases.attic_options`。提案した知識が外れていれば後ろに足す）・`suggest`（`SuggestionsQuery`。2-14）・`prompts`・`open`・`today`・`checkDefault`（`suiri.cases.check_after_days`） |
| いまの一手 | `app/Queries/Cases/NextStep.php` | `for($case, $turn, $today)` の `key`：`closed`・`partner_turn`・`self_turn`・`resolve`（`prediction`）・`add_clue`・`add_hypothesis`・`all_eliminated`・`close`・`predict`（`hypothesis`）・`wait`。画面は `components/case-step/desk`（`?open=` と相棒の番を先に見て、出すカードを1つ決める）→ `now`（カード1枚）・`stages`（進み具合） |
| なぞ・場面を直す | `CaseController::update`・`UpdateCaseRequest` | — |
| 閉じる・開き直す | `app/Actions/Cases/CloseCase.php`・`ReopenCase.php`・`CloseCaseRequest` | `CloseCase::handle`：status・conclusion・closed_at、`rule` があれば `atticNotes()->create`（`case_file_id` つき）。`rule` は `required_with:examples`。`ReopenCase` は結論を残す。画面は `components/case-step/close-card`（`?open=close`）、閉じたあとは `closed-card`（「つまり？」を `attic.store` へ） |
| 消す | `app/Actions/Cases/DeleteCase.php` | 確かめるタスクを `->each->delete()`（オブザーバーがカレンダーの予定も消す）→ `$case->delete()`（子は cascade） |
| 画面 | `resources/views/cases/{index,create-steps,board}.blade.php` | 事件の画面：`case-step.head`（なぞ）→ `case-step.desk`（`stages` 進み具合・`now` いまやることのカード1枚・`more` ほかにできること・見せ合ったあとの `case-partner.flow`）。作る画面：`case-new.mystery` → `clue` → `hypothesis` → `confirm`（どれも `case-new.step` の中の `ui.step-card`）。一覧は `case.list-filter`・`case.summary`。部品の一覧は `docs/design/components.md` の 5 |

### 2-2. 手がかり（Clue）

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 足す | `app/Actions/Cases/AddClue.php` | `handle($case, $kind, $body)`：`$case->clues()->create`（`user_id` は `CopiesOwnerFromCase` が写す）・`$case->touch()` |
| 直す・消す | `ClueController::update`・`destroy` | 受付でモデルを直接（○× は `clue_hypothesis` の cascade で消える） |
| 種類の言葉 | `App\Enums\ClueKind::label($case->scene)` | 仕事の場面は `labels.by_scene.work.clue_kind` |
| 画面 | `components/case-step/clue-card`（足す。`?open=add-clue`・仕事の場面は `case.work-caution`）・`edit-clue-card`（直す・消す。`?open=edit-clue-{id}`）・`clue-pile`（集めた手がかりの荷札の束） | 手がかりがない事件は `NextStep` の `add_clue` でこのカードがいまの一手になる |

### 2-3. 見立て（Hypothesis）と ○×

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 足す | `app/Actions/Cases/AddHypothesis.php` | `handle($case, $body, $author)`：相棒モードでなければ `author` はいつも `self` |
| 状態を変える | `app/Actions/Cases/ChangeHypothesisStatus.php` | `handle($hypothesis, HypothesisStatus $to)`：同じなら false。変われば `statusLogs()->create(from_status, to_status)`＝「考えを変えた」記録 |
| 消す | `app/Actions/Cases/DeleteHypothesis.php` | 確かめるタスクを1件ずつ消してから `delete()` |
| ○× を付ける・外す | `app/Actions/Cases/SetFit.php` | `handle($hypothesis, $clue, ?Fit $fit)`：`ClueHypothesis::updateOrCreate`（組ごとに1行）、null なら `fits()->where('clue_id')->delete()` |
| 同じ事件か | `app/Http/Requests/Cases/UpdateFitRequest.php` | `authorize()`：`hypothesis.case_file_id === clue.case_file_id` |
| 画面 | `components/case-step/hypothesis-card`（足す。`?open=add-hypothesis`）・`edit-hyps-card` ─ `hyp-row`（状態のボタン・本文を直す・消す。`?open=edit-hyps`）・`fit-card` ─ `fit-buttons`（○×。付いている印をもう一度押すと `fits.destroy`。`?open=fit-{id}`） | `Fit::symbol()`（○・×） |

### 2-4. 予想（Prediction）と答え合わせ

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 立てる | `app/Actions/Cases/MakePrediction.php` | `handle($hypothesis, $data)`：予想と確かめるタスク（`kind=verify`・`due_on=check_on`・`starts_at=check_at`・`prediction_id`。題名は `suiri.cases.verify_task_title` の `:statement` を置き換え、`Str::limit` で切る）を1つのトランザクションで |
| 入力の決まり | `app/Http/Requests/Cases/StorePredictionRequest.php` | `confidence` は `array_keys(config('suiri.confidence.choices'))`、`check_on` は `after_or_equal:today`、`attic_note_id` は自分の棚だけ（`Rule::exists(...)->where('user_id')`） |
| 書き換え禁止 | `app/Models/Prediction.php` | `FROZEN = ['statement','confidence']`・`booted()` の `updating` で `isDirty(FROZEN)` なら `LogicException` |
| 取り下げ | `app/Actions/Cases/WithdrawPrediction.php`・`WithdrawPredictionRequest` | `handle()` は `lockForUpdate` で読み直し、`isPending()` でなければ何もしない（false）。`withdrawn_at` を入れ、済んでいない確かめるタスクを消す |
| 答え合わせ | `app/Actions/Cases/ResolvePrediction.php`・`ResolvePredictionRequest` | `handle(): bool`：`lockForUpdate()` で読み直し、`isPending()` でなければ false（取り下げ・二重の送信と競合しない。`WithdrawPrediction` と同じ形）。result・result_note・miss_reason（外れのときだけ残す。`required_if:result,miss`）・resolved_at、確かめるタスクを `done_at=now()` |
| 確かめる日をタスクと合わせる | `app/Actions/Tasks/SyncPredictionCheck.php` | `handle($task)`：`checksPendingPrediction()` のタスクの `due_on`・`starts_at` を予想の `check_on`・`check_at` に写す（文と確かさには触らない）。`UpdateTask`・`ApplyCalendarChanges::moveTask` が呼ぶ |
| 判定 | `Prediction::isPending()`・`isResolved()`・`isWithdrawn()`・`isDue($today)` | `isDue` は `check_on <= $today` の文字列比較 |
| 画面 | `components/case-step/predict-card`（立てる。`?open=predict-{id}`。─ `confidence`・`check-date`）・`wait-card`（確かめる日を待つ・取り下げ。直すボタンなし）・`resolve-card`（答え合わせの画面へ）・`predictions/show` → `case.prediction-original`（最初の文・確かさ・`created_at`）・`case.resolve-form`・`case.resolve-result`（見立ての状態の直しを `hypotheses.update` で勧める） | — |
| 今日の答え合わせ待ち | `app/Queries/Today/DuePredictionsQuery.php`・`components/today/due-predictions` | `for($user, $today)`：result null・withdrawn_at null・`check_on <= $today` |

### 2-5. 相棒モード

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 使えるか | `App\Models\User` | `hasPartner()`（`partner_name` がある）・`partnerLabel()`（なければ `labels.words.partner`） |
| 隠すか | `App\Models\CaseFile::hidesHypotheses()` | `partner_mode && revealed_at === null` |
| 番で絞る | `CaseBoardQuery::handle` | `turn` は隠している間だけ `partner`（既定）か `self` |
| 戻る番 | `HypothesisController::turnOf`・`FitController::back` | 隠している間は書いた人の番の画面へ戻す |
| 見せ合う | `app/Actions/Cases/RevealHypotheses.php` | 隠している事件だけ `revealed_at=now()` |
| 渡し合いの画面 | `components/case-partner/flow`（入口。`case-step.now`・`desk` が置く）→ `turn`（`relay-progress`・`ui.handoff`・`write-card`・`mark-card` ─ `fit-buttons`・`together`） | 見せ合う前はもう片方の見立ての文を HTML に入れない。段の切り替えは `#relay-…` と CSS の `:target`（JavaScript なし）。言葉は `suiri.case_partner` |
| 並べて見る表 | `app/Queries/Cases/RevealBoard.php` | `build($case)` → `partner`・`self`・`rows`（手がかりごとの ○× の番号と `differs`）。画面は `components/case-partner/reveal`（─ `side`・`marks`・`who`） |
| 隠している間は予想を立てない | `StorePredictionRequest::authorize`・`WatsonNotes::lacksCheckDate` | — |

### 2-6. ワトソンのひとこと（AI ではない）

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 規則と言葉 | `config/watson.php` | `rules`（上ほど優先：`personal_info` W-5 → `seen_guess` W-1 → `seen_cause` W-2 → `work_better` W-4 → `single_hypothesis` W-3 → `no_check_date` W-6）・`quote_length`・`detect` |
| 1つ選ぶ | `app/Services/Watson/WatsonNotes.php` | `forCase($case, $alert, $skip)`（閉じた規則と `$skip` を飛ばし、当てはまる最初の1つ）・`alertFor(...$texts)`（W-5 のキーか null）・`match($rule, $case)`・`note()` |
| しるしを探す | `app/Services/Watson/TextSigns.php` | `hasGuess`・`hasCause`・`hasFeelsBetter`・`hasPersonalInfo`（`hasEmail`・`hasPhone`・`hasNamedPerson`・`isRoleWord`・`normalize` は全角→半角） |
| 保存した直後の警告 | `Cases\Concerns\RedirectsToCase::backToCase` → `session('watson_alert')` → `CaseController::show` → `CaseBoardQuery` → `WatsonNotes::forCase($case, $alert)` | 警告があるときは `#…` を付けず画面の上から見せる。保存は止めない |
| 同じ一手と重ねない | `CaseBoardQuery::handle` | `step.key === 'predict'` なら `no_check_date` を `$skip` |
| 閉じる | `app/Actions/Cases/DismissHint.php`・`app/Models/DismissedHint.php` | `dismissedHints()->firstOrCreate(['rule' => …])`（事件と規則の組で1行） |
| 画面 | `components/case-step/bubble`（カードの中の吹き出し。1つだけ・× で閉じる） | 保存した直後の警告は `case-step/now` がカードの上に赤い吹き出しで出す |

### 2-7. タスク（Task）

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 一覧の振り分け | `app/Queries/Tasks/TaskListQuery.php` | `normalize($view)`（`suiri.tasks.views` のキー以外は `today`）・`viewOf($task, $today)`・`list`（today＝期日が今日まで、upcoming＝明日以降、someday＝期日なし、done＝`suiri.tasks.done_limit` 件）・`counts`・`justDone($user, $id)`・`byDate` |
| 作る | `app/Actions/Tasks/CreateTask.php`・`StoreTaskRequest` | 種類はいつも `TaskKind::Normal`。`starts_at` を入れるなら `due_on` 必須（`required_with`） |
| 直す | `app/Actions/Tasks/UpdateTask.php`・`UpdateTaskRequest` | 期日を消したら `starts_at` も消す。保存のあと `SyncPredictionCheck::handle`（トランザクション）。確かめるタスクは題名を受け取らず、答え合わせ待ちなら `due_on` 必須 |
| 消す | `app/Actions/Tasks/DeleteTask.php`・`DeleteTaskRequest` | `checksPendingPrediction()` なら消さない（受付は 403、Action は `LogicException`） |
| 済み・戻す | `app/Actions/Tasks/CompleteTask.php`・`ReopenTask.php` | `done_at ??= now()`、`actual_minutes` は渡されたときだけ。戻してもかかった時間は残す |
| 丸で済みにできないタスク | `Task::answeredByPrediction()` | `isVerify() && prediction_id !== null`。`CompleteTaskRequest`・`ReopenTaskRequest`・`components/task/item`・`tasks/edit` がこれ1つで判断 |
| 題名の似た実績 | `app/Queries/Tasks/SimilarTasksQuery.php` | `for($user, $title, $exceptId)`：済み・`actual_minutes` あり・`suiri.tasks.similar.pool` 件から、`score`（同じ 1.0・含む `CONTAINS_SCORE` 0.8・2文字ずつのダイス係数）が `min_score` 以上を `limit` 件 |
| 直す画面の確かめるタスク | `resources/views/tasks/edit.blade.php`・`components/task/form` | `answeredByPrediction()` なら題名は入力欄でなく文字だけ、`checksPendingPrediction()` なら消すボタンの代わりに `predictions.withdraw`（取り下げるとタスクも消える） |
| 画面 | `resources/views/tasks/{index,create,edit}.blade.php`・`components/task/*`（`item`・`check`・`curious`（`cases.draft` へ POST）・`quick-add`（「くわしく」は `formaction=tasks.draft`）・`form`・`done-followup`・`minutes`・`similar-history`・`tabs`） | — |

### 2-8. 今日

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 受付 | `app/Http/Controllers/Today/TodayController.php` | 1-3 の表 |
| 予定とタスクを混ぜる | `app/Queries/Today/AgendaQuery.php` | `for($user, $date, $skipPredictionIds)`：`CalendarAgenda::eventsOn` の予定＋期日が今日のタスク。終日 → 時刻 → 予定が先。1件に `just_ended`（`justEnded`：終わってから `suiri.suggest.just_ended_hours` 時間以内の予定。S-6）。`overdueCount`・`endOf` |
| 今日の練習 | `app/Services/Guide/DailyPractice.php` | `for($user, $date)`：`crc32(日付#user_id) % 件数`（同じ日は同じ。やらなくても何も減らない） |
| 捜査中の事件 | `app/Queries/Cases/OpenCasesQuery.php` | `get($user)`：`suiri.today.open_cases` 件 |
| 最初の画面 | `app/Support/Landing.php` | `url()` → `route('today')`（いつも今日の画面。`HomeController`・同意のあと・ログインのあとが使う） |
| 画面 | `resources/views/today/index.blade.php` | 上から：記録のきっかけ（`users.cue`）と「気になることを記録」→ `task.done-followup` → `today.due-predictions` → `today.agenda`（中に `task.quick-add back="today"`）→ `today.practice-suggestion` → `today.open-cases` |

### 2-9. カレンダー連携

| やること | ファイル | シンボル・要点 |
|---|---|---|
| つなぐ（確かめて保存） | `app/Actions/Calendar/ConnectCalendar.php` | `handle($user, $google)`：`check()`（①`sub`＝`users.google_id` ②`suiri.calendar.scopes` が全部ある ③refresh token）→ `prepareCalendar()`（前の専用カレンダーが `hasCalendar` ならそれ、なければ `insertCalendar`）→ 新しいカレンダーならタスクの `calendar_event_id` を外し `sync_token=null` → 保存 → `PushAllTasksToCalendar::dispatch`（つなぎ直したときも毎回。残った予定の片づけもこの仕事） |
| 書き出し（見張り） | `app/Observers/TaskObserver.php` | `saving`（つないでいる人の期日つきタスクに予定の ID＝ハイフンなし UUID を決める）・`created`（期日があれば）・`updated`（`SENT_COLUMNS` が変わったときだけ）・`deleted` → `PushTaskToCalendar`／`RemoveTaskFromCalendar` を `afterCommit()`。`connected()`・`safely()`（失敗しても保存は成功） |
| 書き出し（仕事） | `app/Jobs/PushTaskToCalendar.php`・`PushAllTasksToCalendar.php`・`RemoveTaskFromCalendar.php` | 6 章 |
| 予定の中身 | `app/Services/Google/TaskEvent.php` | `toEvent($task)`（summary・description＝`link_label`＋URL・start/end・transparency（確かめるタスクは transparent）・source・`extendedProperties.private.suiri_task_id`）・`times`・`timesFrom($event)`・`summary`（確かめるタスクは `verify_summary`、済みは `done_prefix`）・`link`（確かめるタスクは `predictions.show`、ほかは `tasks.edit`） |
| 取り込み | `app/Jobs/PullCalendarChanges.php` → `app/Actions/Calendar/ApplyCalendarChanges.php` | `handle($connection)`：`listChangedEvents`（`SyncTokenExpired` なら全件）→ `apply`（cancelled は `unlink`、`suiri_task_id` なしは無視、自分のタスクで同じ予定 ID のものだけ `moveTask`）→ 全件のときは `unlinkMissing` と `sweepOrphans`（`DeleteOrphanEvents::handle($connection, $events)`。`GoogleApiFailed` は id とエラーの種類だけログして取り込みは続ける）→ `sync_token`・`last_synced_at` を保存。日時が動いた確かめるタスクは `moveTask` の中で `SyncPredictionCheck`（予想の `check_on`・`check_at` も同じトランザクションで動く）。タスクは `saveQuietly()`（書き戻さない） |
| 残った予定の片づけ | `app/Actions/Calendar/DeleteOrphanEvents.php` | `handle($connection, ?$events = null): int`：`$events` がなければ `listChangedEvents(…, null)` で全件を読む → 印 `suiri_task_id` があり cancelled でない予定のうち、番号が本人の今あるタスクに当たらないものを `deleteEvent`。連携が切れている間に消したタスクの予定を消すため。呼ぶ所は `PushAllTasksToCalendar::handle` と `ApplyCalendarChanges::handle`（全件のとき） |
| 予定（読むだけ） | `app/Contracts/CalendarAgenda.php`・`app/Services/Google/GoogleCalendarAgenda.php`・`NullCalendarAgenda.php`（いつも空）・`FakeCalendarAgenda.php`（テストの偽物） | `eventsOn($user, $date)`（今日の画面）と `upcoming($user, $days)`（確かめる日の提案 S-4：いまから `$days` 日先の終わりまでを `listEvents` 1回で読み、始まった時刻つきの予定とアプリの予定を除き、日付 → 終日 → 時刻の順）。どちらも `remember`（つながっていて `read_scope` があるときだけ。`Cache::remember("calendar-agenda:{id}:{date}")`・`"calendar-upcoming:{id}:{days}:{date}"`、`agenda_cache_seconds`、失敗は空）。`fetch`・`item`・`fetchUpcoming`・`upcomingItem`。題名は保存しない |
| 期限切れの覚えを消す | `app/Console/Commands/PruneExpiredCache.php` | `cache:prune-expired`（`cache.default` が database のときだけ）。5分ごと |
| 解除 | `app/Actions/Calendar/DisconnectCalendar.php` | `handle($user, $deleteCalendar)`：選ばれ、つながっていれば `deleteCalendar` → `GoogleTokens::revoke` → タスクの `calendar_event_id` を外す → 行を消す。退会からも呼ぶ |
| トークン | `app/Services/Google/GoogleTokens.php` | `configured()`・`accessToken()`（期限の `refresh_before_seconds` 前なら `refresh`）・`refresh()`（`invalid_grant` → `markBroken` → `GoogleAuthBroken`、そのほか → `GoogleApiFailed`）・`expire()`・`revoke()`（失敗しても false）・`saveIfStored()` |
| REST の窓口 | `app/Contracts/CalendarApi.php`・`app/Services/Google/CalendarApi.php` | `insertCalendar`・`hasCalendar`・`deleteCalendar`・`upsertEvent`（409 なら PUT）・`deleteEvent`（404/410 は成功扱い）・`listChangedEvents`（ページを最後まで。410 → `SyncTokenExpired`）・`listEvents`（`fields`＝`agenda_fields`）。`send`・`http`・`failUnless`（401 なら `expire`） |
| 例外 | `app/Services/Google/Exceptions/` | `GoogleApiFailed($operation, $status)`（試し直す）・`GoogleAuthBroken`（あきらめる）・`SyncTokenExpired`・`CalendarConnectRefused`（画面に出す文） |
| 偽物 | `app/Services/Google/FakeCalendarApi.php`・`FakeCalendarAgenda.php` | `FakeCalendarApi`：公開の配列 `calendars`・`events`・`changes`・`agenda`・`syncTokensSeen`、旗 `expiredSyncToken`・`failing`、`calls`。`FakeCalendarAgenda`（`$this->app->instance(CalendarAgenda::class, …)` で結ぶ）：`today`・`upcoming`・`upcomingCalls` |
| 結びつけ | `app/Providers/CalendarServiceProvider.php` | `register`：`CalendarAgenda` → `GoogleCalendarAgenda`、`CalendarApi` → テストなら `FakeCalendarApi` の singleton、ふだんは `Services\Google\CalendarApi`。`boot`：`Task::observe(TaskObserver::class)`、テストでは `Http::preventStrayRequests()` |
| 画面 | `resources/views/calendar/connect.blade.php`・`components/settings/calendar-panel`（設定の中。つないでいない／つながっている／切れた）・`components/settings/consent-row` | — |

### 2-10. 手引き（教材はファイル）

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 教材 | `database/data/guide/skills.json`（10 件：key・name・kid_name・summary・body・kid_body・quote_en・quote_source・practice・pitfall）・`scenes.json`（23 件：key・title・story_en・story_ja・chapter・front・kid_hint・reasoning・verification・quote_en・skills・is_mistake）・`practices.json`（11 件：key・name・origin・skill・description・kid_description・prompts）・`reasoning.json`（intro・kid_intro・items・note・kid_note）・`overview.json`（intro・entries・flow_intro・steps・mistake_note） | 形は `docs/design/data-model.md` の 4、書き方は `.claude/rules/guide-content.md` |
| 読む | `app/Support/Guide/GuideLibrary.php` | `skills`・`skill`・`scenes`・`scene`・`practices`・`practice`・`reasoning`・`overview`・`practicesFor`・`scenesFor`・`skillNames`・`neighbors`・`find`・`load`（`Cache::rememberForever("guide:{name}:".filemtime)`） |
| 推理の段 | `app/Support/Guide/ReasoningSteps.php` | `split($reasoning)`：「事実：」「推理：」「結論：」で区切る |
| 自分の推理 | `app/Support/Guide/SceneAttemptReader.php`・`app/Actions/Guide/SaveSceneAttempt.php` | `keys($user)`・`find($user, $key)`／`handle($user, $key, $answer)`（`updateOrCreate`、空なら null） |
| 画面 | `resources/views/guide/{index,reasoning}.blade.php`・`guide/skills/show`・`guide/scenes/{index,show}`・`guide/practices/index`・`components/guide/*` | 子ども向けの切り替えは `components/guide/audience-toggle` |

### 2-11. ふりかえり・ノーベリ帳・屋根裏の棚

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 週 | `app/Services/Review/ReviewWeek.php` | `current()`・`fromParam($param)`・`start`・`end`・`param`・`previous`・`next`（今週なら null）・`isCurrent`・`label`・`scope($query, $column)`（月曜 0 時以上・次の月曜 0 時未満） |
| 週の画面をまとめる | `app/Queries/Review/WeekReviewQuery.php` | `for($user, $week, $range)` → `week`・`stats`・`range`・`calibration`・`reflection`・`caseOptions`・`kid` |
| 週の数字 | `app/Services/Review/WeeklyStats.php`・`EstimateCheck.php` | `for`：cases・multi_hypothesis・resolved（結果ごと）・mind_changes（`hypothesis_status_logs`）・misses・attic・estimates・is_empty。`percent()`（分母 0 は null）。`EstimateCheck::for`：予想時間とかかった時間の両方があるタスク |
| 確かさの答え合わせ | `app/Services/Review/Calibration.php` | `for($user, $week)`（hit と miss だけ・取り下げを除く）・`summarize($rows)`（区間は `suiri.review.calibration_buckets`、件数が `suiri.calibration_min_count` 未満は `rate=null`） |
| 週の問い | `app/Queries/Review/WeekCasesQuery.php`・`app/Actions/Review/SaveWeeklyReflection.php`・`UpdateWeeklyReflectionRequest` | `options($user, $week, $selectedId)`（その週に作った・状態を変えた・答え合わせした事件）／`updateOrCreate(['week_start' => param()])` |
| ノーベリ帳 | `app/Queries/Review/NorburyQuery.php` | `misses`（result=miss・`suiri.review.norbury_per_page`）・`reasonCounts`（`reasons`・`unknown`・`total`） |
| 屋根裏の棚 | `app/Queries/Review/AtticNotesQuery.php`・`app/Actions/Attic/SaveAtticNote.php`・`StoreAtticNoteRequest` | `notes`（`withCount('predictions')`）・`caseOptions`／`handle($user, $data, ?$note)`。事件を閉じるときにも `CloseCase` が足す |
| 画面 | `resources/views/review/show`・`norbury/index`・`attic/index`・`components/review/*`（`tabs` で3画面を行き来。`story` は `suiri.review.norbury`／`attic` の由来の文） | — |

### 2-12. 設定・書き出し・退会

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 設定を保存 | `app/Actions/Settings/UpdateSettings.php`・`UpdateSettingsRequest` | name・partner_name・default_scene・cue（空は null） |
| 設定の画面 | `resources/views/settings/show.blade.php` | 4つの問いのフォーム → `<x-settings.calendar-panel>` → `<x-settings.ai-panel :apps="$aiApps">`（ChatGPT・Claude とつなぐ枠。2-15）→ `<x-settings.account>` |
| 書き出し | `app/Actions/Account/ExportUserData.php` | `handle($user)` → app・exported_at・user（`Hidden` で google_id・remember_token なし）・tasks・cases（clues・hypotheses.fits・hypotheses.statusLogs・predictions・dismissedHints）・attic_notes・weekly_reflections・scene_attempts・calendar（connected と app_calendar_id だけ）・ai_connections（`ConnectedAppsQuery` の名前・戻る先の住所・最後につながった日時だけ。通行証の番号は出さない）。新しい表・列はここにも足す |
| 退会 | `app/Actions/Account/DeleteAccount.php` | `handle($user)`：`DisconnectCalendar::handle` → トランザクションで `sessions` のその人の行・`Invitation`（メールで）・`$user->delete()`（子は cascade）。Google の専用カレンダーは消さない。AI とつないだ許可（Passport の通行証・更新の札・引換券・デバイスの番号）は `forgetAiGrants` で明示して消す（Passport の表には cascade がない） |
| 画面 | `components/settings/danger-zone` | ダウンロードのボタンと、確認の言葉を入れる退会のフォーム |

### 2-13. ログイン・招待・同意・公開ページ・共通

| やること | ファイル | シンボル・要点 |
|---|---|---|
| Google ログイン | `app/Http/Controllers/Auth/GoogleLoginController.php` | `redirect`・`callback`・`enabled()`（`services.google.client_id` と `client_secret`） |
| 利用者を探す・作る | `app/Actions/Auth/LoginWithGoogle.php` | `handle($google)`（`google_id`＝sub で照合、既存はアイコンだけ更新）・`register`（確認済みメール・`config('suiri.invite_only')` が true のときだけ招待の名簿にあるか（既定 false＝一般公開。断るときは `LoginRefused`）・同じメールの別アカウントなし、`google_id` は `forceFill`、名簿の行があれば `used_at`）・`verified`・`displayName`・`avatar` |
| 招待 | `app/Models/Invitation.php`・`app/Console/Commands/InviteAdd.php`・`InviteList.php`・`InviteRemove.php` | `normalize()`（小文字）・`findFor()`・`isUsed()`。`invite:add {email} {--note=}`・`invite:list`・`invite:remove {email}`（登録済みの人は消さない）。名簿が効くのは招待制（`.env` の `SUIRI_INVITE_ONLY=true` → `suiri.invite_only`）のときだけ。トップ（`home`）と利用規約（`legal/terms`）の文もこの値で出し分ける |
| 同意 | `app/Http/Middleware/EnsureAgreed.php`・`AgreementController`・`AcceptTermsRequest`・`AcceptTerms` | `User::hasAgreed()`。未同意は `redirect()->guest(route('agree.show'))` |
| 公開ページ | `LegalController`・`resources/views/legal/{about,privacy,terms}.blade.php`・`config/legal.php` | 運営者の【】は運営者が書く |
| 守りの札 | `app/Http/Middleware/SecurityHeaders.php` | nosniff・SAMEORIGIN・Referrer-Policy・Permissions-Policy、https か本番なら HSTS、ログイン中は `Cache-Control: private, no-store` |
| 骨組み | `components/layouts/app`（ログイン後）・`guest`・`nav`（`config('suiri.nav')`）・`footer`（`suiri.notes.footer`） | — |
| 共通の部品 | `components/ui/*`（いまやることを組む step-card・progress・big-button・step-icon・hint・done-summary・empty-state・evidence-tag・stamp・handoff と、土台の button・field・textarea・select・choices・badge・flash・privacy-note（`suiri.notes.privacy`）・confirm・icon。決まった言葉は `suiri.ui`） | 一覧と受け取る値は `docs/design/components.md` |
| 画面の動き | `resources/js/app.js`（読み込むだけ）・`resources/js/parts/{suggest-chips,minute-chips,stepper,ai-copy}.js`・`resources/css/app.css` | JavaScript がなくても送れる形（`<details>`・ふつうのフォーム）を先に作り、`parts/` は使いやすくするために足す |
| 見本のデータ | `database/seeders/DemoSeeder.php`・`database/seeders/Demo/*` | `app()->environment('local')` のときだけ。`watson@example.com` |

---

### 2-14. 提案（AI なし。requirements.md の 4-10 S-1〜S-6）

決まった型と本人の記録から、入力欄の書き出しの候補を出す。押すと欄に入るだけで保存はしない。言葉は `config/suiri.php` の `suggest`。

| やること | ファイル | シンボル・要点 |
|---|---|---|
| まとめる | `app/Queries/Cases/SuggestionsQuery.php` | `for($case, $user, $visible, $kid, $today)` → `clue`・`types`・`emphasize`・`attic`・`templates`（見立ての id => 穴埋めの文）・`dates`。閉じた事件は全部空。予想の欄が出ない（見せ合う前・生きた見立てなし）ときは `templates`・`dates` を作らない（カレンダーも読まない）。`CaseBoardQuery::handle` が `suggest` として画面に渡す |
| 決まった型（S-1〜S-3） | `app/Queries/Cases/StarterSuggestions.php` | `clueQuestions($scene)`（仕事は `work`。`kind` で種類を切り替える）・`hypothesisTypes($scene, $kid)`（仕事 → `work`、親子の人 → `kid`、ほか → `default`）・`emphasizeTypes($count)`（`emphasize_until` 以下）・`predictionTemplate($body, $scene, $kid)`・`atticStarter($rule)`・`quote`（`quote_length` で切る） |
| 屋根裏の棚（S-5） | `app/Queries/Cases/AtticSuggestionsQuery.php` | `for($case, $user)`：`$user->atticNotes()`（この事件から生まれた知識は除く）を `pool` 件まで、なぞ・手がかりとの `WordOverlap::score` が 1 以上を多い順に `limit` 件。1件は `id`・`rule`・`label`・`text`・`attic` |
| 確かめる日（S-4） | `app/Queries/Cases/CheckDateSuggestionsQuery.php` | `for($case, $user, $today)`：`check_dates.quick`（明日・3日後・1週間後）＋`fromCalendar`（つながっている人だけ `CalendarAgenda::upcoming($user, calendar_days)`。なぞ・手がかり・消えていない見立てと重なる題名を、同じ題名は近い日だけ・多い順に `calendar_limit` 件・日付順。`calendar_label`）。1件は `label`・`date`・`time`・`from` |
| 言葉の重なり | `app/Support/WordOverlap.php` | `grams(...$texts)`：漢字は2文字・カタカナと英数字は3文字のかけら（ひらがなは見ない。全角英数字は半角に）。`stop_words` は数えない。`score($grams, $text)`：同じかけらの数 |
| 穴埋めのまま保存させない | `app/Http/Requests/Cases/StorePredictionRequest.php` | `statement` に `not_regex`（`suiri.suggest.blank`＝＿＿）。文は `lang/ja/validation.php` の `custom.statement.not_regex` |
| さっき終わった予定（S-6） | `app/Queries/Today/AgendaQuery.php`・`components/today/agenda`・`components/task/curious`（`strong`） | 2-8 の表 |
| 画面 | `components/case/suggest-chips`（候補を props で受け取るだけ。並びは初めは隠し `data-suggest-chips`、JS が見せる）・`resources/js/parts/suggest-chips.js` | 置き場所：`case-new.clue`（S-1。たたむ）・`case-new.hypothesis`（S-2。たたむ）・`case-step.clue-card`（`questions`）・`case-step.hypothesis-card`（`types`・`emphasize`・`attic`）・`case-step.predict-card`（`template` は「型を使う」のチップ・`attic`）・`case-step.check-date`（`dates`）。JS は押したチップの `data-text`（`data-suggest-target` の欄に足す）・`data-kind`・`data-date`/`data-time`・`data-attic` を同じフォームに入れるだけ（送信しない） |

### 2-15. ChatGPT・Claude とつなぐ（読むだけの MCP。要件 4-11・`rules/ai-connect.md`）

アプリの中では AI を使わない。利用者が自分の ChatGPT・Claude から読むだけの窓口（`POST /mcp`）。部品は laravel/mcp（窓口）と Laravel Passport（通行証を出す OAuth）。住所は 1-11。

| やること | ファイル | シンボル・要点 |
|---|---|---|
| 窓口 | `app/Mcp/Servers/SuiriServer.php` | `$tools`（読むだけの4つ）・`$resources`・`$prompts` は空・`boot()` で名前・版・説明（`ai_connect.server`・`instructions`） |
| 道具 | `app/Mcp/Tools/{ListCases,GetCase,ListDuePredictions,SearchAttic}.php` | `#[Name('list_cases')]` などの snake_case・`#[IsReadOnly]`・`handle(Request)` で `$request->validate` → `Queries/Ai` → `Response::structured`・`schema()`。説明文は `Mcp/Concerns/DescribedInConfig`（`ai_connect.tools.{name}` ＋ `tool_tail`） |
| 見える事件の決まり（1か所） | `app/Queries/Ai/VisibleCases.php` | `of($user)`（本人の事件で `scene` が `work` でない）・`find($user, $id)`（仕事の場面・他人・ない番号はどれも null）・`scope()`・`showingHypotheses()`（見せ合う前を除く） |
| 組み立て（読むだけ） | `app/Queries/Ai/CaseListQuery.php`・`CaseSheetQuery.php`・`DuePredictionsQuery.php`・`AtticNotesQuery.php`・`PredictionSheet.php` | 一覧（見せ合う前は見立てと予想の数を null）／1件の中身（見せ合う前は `hypotheses` を空にして `hidden_note`）／答え合わせ待ち（見せ合う前の事件は除く）／棚（事件なしの知識と見える事件の知識）／予想1つの形（`state` は言葉） |
| つないでいる AI | `app/Queries/Ai/ConnectedAppsQuery.php` | `for($user)`：取り消していない通行証で、通行証か更新の札が期限内のものを AI（`client_id`）ごとに1行（`id`・`name`・`host`・`last_at`）。設定の枠・書き出し・取り消しの確かめで使う |
| つなぐのをやめる | `app/Actions/Ai/RevokeAiAccess.php`・`Http/Controllers/Settings/RevokeAiAppController.php` | `handle($user, $appId)`：その人のその AI の通行証・更新の札・引換券を `revoked`（トランザクション）。すぐ効く |
| Passport の設定 | `app/Providers/AiConnectServiceProvider.php` | `ignoreRoutes()`・デバイスの許可を切る・`authorizationView`・`tokensExpireIn`（`ai_connect.tokens.access_minutes`）・`refreshTokensExpireIn`（`refresh_days`）・`defaultScopes(['mcp:use'])`・回数制限3つ。`skipsAuthorization()` は上書きしない |
| 同意の画面 | `resources/views/oauth/authorize.blade.php`・`app/Support/Ai/ConsentView.php` | `from($parameters)` → `appName`（`client_id` から名前を引き直す）・`redirectHost`・`company`（`ai_connect.consent.companies`）・`authToken`・`state`・`appId` |
| よその画面からの呼び出しを断る | `app/Http/Middleware/RejectForeignOrigin.php` | `Origin` が付いていて、推理手帳自身でも `mcp.redirect_domains` でもなければ 403 |
| 掃除 | `app/Console/Commands/PruneAiApps.php`・`routes/console.php` | `ai:prune-apps`（通行証が1つもないまま `ai_connect.prune_unused_apps_days` 日たった自己登録を消す）・`passport:purge`（6 章） |
| 設定の枠 | `components/settings/ai-panel`・`ai-app-row`・`ai-steps` | 言葉は `ai_connect.panel`。窓口の住所の写すボタンは `resources/js/parts/ai-copy.js` |
| 写すボタン | `components/case/ai-copy`・`app/Support/Ai/ConsultText.php`・`resources/js/parts/ai-copy.js` | `ConsultText::for($case)`：なぞ・手がかり・見立て（○× と予想）・わかったこと・お願いの文。見せ合う前は見立てを写さない。仕事の場面は部品が何も出さない。置き場所は `<x-case-step.more>` |
| ポリシーの文 | `resources/views/legal/partials/ai-connect.blade.php` | プライバシーポリシーの「4. ほかの人に渡すこと」の中で `@include`。数字は `ai_connect.tokens` から |
| 本番の鍵 | `src/storage/oauth-private.key`・`oauth-public.key`（git に入らない） | 本番で初回だけ `php artisan passport:keys`（`.claude/rules/deploy.md`）。テストは `tests/Feature/Ai/Concerns/ConnectsAi::usePassportKeys()` が一時の鍵を config に入れる |

## 3. 表 → モデル

全部 `App\Models\`。日付だけの列（`due_on`・`check_on`・`week_start`）はキャストせず `'Y-m-d'` の文字列で比べる。状態・種類は文字列の列を列挙にキャスト。

| 表 | モデル | 主な関係 | キャスト・属性 | 小さな判定・決まり |
|---|---|---|---|---|
| `users` | `User` | `tasks`・`caseFiles`・`clues`・`hypotheses`・`predictions`・`atticNotes`・`weeklyReflections`・`sceneAttempts`・`dismissedHints`（HasMany）・`calendarConnection`（HasOne） | `default_scene`→`Scene`・`agreed_at`。`#[Hidden(['google_id','remember_token'])]`。`google_id`・`agreed_at` は Fillable にない（`forceFill`） | `hasAgreed()`・`hasPartner()`・`prefersKidWords()`・`partnerLabel()` |
| `invitations` | `Invitation` | （外部キーなし。メールで結ぶ） | `used_at`。`email()` の set で小文字 | `normalize()`・`findFor()`・`isUsed()` |
| `case_files` | `CaseFile` | `user`・`clues`（`oldest('id')`）・`hypotheses`（同）・`predictions`・`atticNotes`・`dismissedHints` | `scene`→`Scene`・`status`→`CaseStatus`・`origin_kind`→`OriginKind`・`partner_mode` bool・`revealed_at`・`closed_at` | `isOpen()`・`isClosed()`・`hidesHypotheses()` |
| `clues` | `Clue` | `caseFile`・`user`・`hypotheses`（BelongsToMany `clue_hypothesis`・`using(ClueHypothesis)`・pivot `id`・`fit`） | `kind`→`ClueKind` | `CopiesOwnerFromCase`（user_id を事件から写す） |
| `hypotheses` | `Hypothesis` | `caseFile`・`user`・`clues`（BelongsToMany）・`fits`（HasMany `ClueHypothesis`）・`statusLogs`（`oldest('id')`）・`predictions` | `author`→`HypothesisAuthor`・`status`→`HypothesisStatus` | `isByPartner()`・`isEliminated()`・`CopiesOwnerFromCase` |
| `clue_hypothesis` | `ClueHypothesis`（`Pivot`・`$incrementing = true`） | `hypothesis`・`clue` | `fit`→`Fit` | `(hypothesis_id, clue_id)` は一意。user_id は持たない（両方の親の Policy で守る） |
| `hypothesis_status_logs` | `HypothesisStatusLog` | `hypothesis`・`user` | `from_status`・`to_status`→`HypothesisStatus`。`UPDATED_AT = null` | `booted` の `creating` で user_id を見立てから写す |
| `predictions` | `Prediction` | `caseFile`・`hypothesis`・`user`・`atticNote`・`task`（HasOne） | `confidence` int・`result`→`PredictionResult`・`miss_reason`→`MissReason`・`resolved_at`・`withdrawn_at`。`check_on`・`check_at` は文字列 | `FROZEN`・`booted`（creating で case_file_id・user_id を写す／updating で FROZEN を止める）・`isPending()`・`isResolved()`・`isWithdrawn()`・`isDue($today)` |
| `tasks` | `Task` | `user`・`prediction` | `kind`→`TaskKind`・`duration_minutes`・`actual_minutes` int・`done_at`・`calendar_synced_at`。`due_on`・`starts_at`（HH:MM）は文字列 | `isDone()`・`isVerify()`・`answeredByPrediction()`・`checksPendingPrediction()`・`hasTime()`・スコープ `open()`・`done()`（`#[Scope]`）。`TaskObserver` が見張る |
| `attic_notes` | `AtticNote` | `user`・`caseFile`（任意。nullOnDelete）・`predictions` | — | — |
| `weekly_reflections` | `WeeklyReflection` | `user`・`changedCase`（`changed_case_file_id`。nullOnDelete） | `week_start` は文字列 | `(user_id, week_start)` は一意 |
| `scene_attempts` | `SceneAttempt` | `user` | — | `(user_id, scene_key)` は一意 |
| `dismissed_hints` | `DismissedHint` | `caseFile`・`user` | `UPDATED_AT = null` | `(case_file_id, rule)` は一意・`CopiesOwnerFromCase` |
| `calendar_connections` | `CalendarConnection` | `user`（1人1行・user_id 一意） | `access_token`・`refresh_token`→`encrypted`・`expires_at`・`scopes`→array・`status`→`CalendarStatus`・`last_synced_at`。`#[Hidden(['access_token','refresh_token','sync_token'])]` | `isActive()`・`isBroken()`・`hasScope($scope)` |
| `sessions`・`cache`・`jobs` など | （Laravel 標準） | — | — | 退会で `sessions` のその人の行を消す。`cache` は5分ごとに期限切れを消す |
| `oauth_clients`・`oauth_auth_codes`・`oauth_access_tokens`・`oauth_refresh_tokens`・`oauth_device_codes` | （Laravel Passport。`Passport::client()`・`authCode()`・`token()`・`refreshToken()` で引く） | `user_id` は索引だけ（外部キーなし） | — | AI の自己登録と許可の記録（本文は持たない）。退会で `DeleteAccount::forgetAiGrants` が明示して消す。`oauth_clients` は利用者の記録ではない（`user_id` なし）。移行は `2026_10_31_10000{1..5}_create_oauth_*_table.php`。`User` は `OAuthenticatable`・`HasApiTokens`、`config/auth.php` の `api` の guard は `passport` |

- 外部キーは全部 `cascadeOnDelete`（利用者・事件・見立てを消すと子も消える）。例外は nullOnDelete：`attic_notes.case_file_id`・`predictions.attic_note_id`・`tasks.prediction_id`・`weekly_reflections.changed_case_file_id`。
- 移行は `database/migrations/2026_10_31_0000{01..13}_create_*_table.php`（1 ファイル 1 表）。ファクトリーは `database/factories/{モデル}Factory.php`（全モデル分）。

---

## 4. 列挙 → 値 → 表示の言葉

全部 `App\Enums\`、`use Concerns\HasLabel`。言葉は `config/suiri.php` の `labels.{labelGroup()}.{値}`。

| 列挙 | 列 | 値 | `labelGroup()` | 仕事の場面で変わる値 | ほかのメソッド |
|---|---|---|---|---|---|
| `Scene` | `users.default_scene`・`case_files.scene` | `daily`・`family`・`work` | `scene` | — | — |
| `CaseStatus` | `case_files.status` | `open`・`solved`・`cold` | `case_status` | — | `isClosed()` |
| `ClueKind` | `clues.kind` | `seen`・`heard`・`absent` | `clue_kind` | `seen`・`heard` | — |
| `HypothesisAuthor` | `hypotheses.author` | `self`・`partner` | `hypothesis_author` | — | — |
| `HypothesisStatus` | `hypotheses.status` | `considering`・`likely`・`eliminated`・`confirmed` | `hypothesis_status` | — | `isAlive()` |
| `Fit` | `clue_hypothesis.fit` | `fits`・`contradicts` | `fit` | — | `symbol()` |
| `PredictionResult` | `predictions.result` | `hit`・`miss`・`unclear`・`void` | `prediction_result` | — | `isMiss()` |
| `MissReason` | `predictions.miss_reason` | `few_clues`・`single_hypothesis`・`assumption`・`wrong_check`・`chance` | `miss_reason` | — | — |
| `TaskKind` | `tasks.kind` | `normal`・`verify` | `task_kind` | — | — |
| `OriginKind` | `case_files.origin_kind` | `task`・`event`・`practice` | `origin_kind` | — | — |
| `CalendarStatus` | `calendar_connections.status` | `active`・`broken` | `calendar_status` | — | — |

引き方：

| 書き方 | 中身（`app/Support/Label.php`） | 探す順 |
|---|---|---|
| `$enum->label(?Scene $scene)` | `Label::enum($group, $value, $scene)` | `labels.by_scene.{scene}.{group}.{value}` → `labels.{group}.{value}` → 値そのもの |
| `$enum->kidLabel()` | `Label::kidEnum($group, $value)` | `labels.kid.{group}.{value}` → 共通 |
| `Enum::options(?Scene)`・`Enum::values()` | `HasLabel` | `<x-ui.select :options>`・`<x-ui.choices>` にそのまま渡せる「値 => 言葉」 |
| `Label::word($key, ?Scene)` | 列挙ではない言葉 | `labels.by_scene.{scene}.words.{key}` → `labels.words.{key}` → キー |
| `Label::kidWord($key)` | 子ども向け | `labels.kid.words.{key}` → 共通 |

- 仕事の場面の出し分けは `labels.by_scene.work`（`clue_kind.seen`・`clue_kind.heard`・`words.hypothesis`・`words.prediction`）だけ。事件の `scene` を渡す（例 `ClueKind::label($case->scene)`・`Label::word('prediction', $scene)`）。
- 子ども向けを出す条件は1つ：`User::prefersKidWords()`（`default_scene === Scene::Family`）。事件ごとの場面では切り替えない。例外は手引きの `?kid=0|1`（`GuideController::reasoning`・`SkillController::show`）。受付・Query が `kid` を画面に渡す：`CaseBoardQuery`・`NorburyController`・`AtticNoteController`・`WeekReviewQuery`・`SceneController::show`（ヒントを開いておく）・`today/index`（`practice-suggestion :kid`）。画面ごとの対応は `docs/design/words.md` の 2-2。
- 確かさの目安の言葉は列挙ではなく `config('suiri.confidence.choices')`（`hint`・`kid`）。

---

## 5. 設定ファイル

コードに数字・文言を直書きしない。`env()` は `config/` の中だけ。

### 5-1. `config/suiri.php`（かたまりごと）

| キー | 中身 | 使う所 |
|---|---|---|
| `labels` | 列挙の言葉（`scene`・`case_status`・`clue_kind`・`hypothesis_author`・`hypothesis_status`・`fit`・`prediction_result`・`miss_reason`・`task_kind`・`calendar_status`・`origin_kind`）・`words`（列挙ではない言葉）・`by_scene.work`・`kid` | `Support\Label`・`HasLabel` |
| `confidence` | `min` 50・`max` 95・`step` 5・`choices`（50〜95 の各値に `hint`・`kid`） | `StorePredictionRequest`・`components/case-step/confidence`・`components/case/prediction-original` |
| `nav` | 下のナビの項目（`route`・`match`・`label`・`icon`） | `components/layouts/nav` |
| `ui` | 作り直しの共通部品の決まった言葉（`step`「ステップ n / m」・`now`・`done`・`later`・`tag_no`・`stamp_top`・`handoff_title`・`handoff_button`・`summary_edit` など） | `components/ui/*`・`case/summary`・`case-step/now`・`clue-pile` |
| `invite_only` | 招待制にするか（`.env` の `SUIRI_INVITE_ONLY`。既定 false＝一般公開） | `LoginWithGoogle::register`・`home`・`legal/terms` |
| `limits` | 入力の長さの上限（user_name 50（表の `users.name` は 100 だが、設定の画面と Google から受け取る名前は 50 にそろえる）・partner_name 30・cue 100・task_title 100・task_memo 500・case_title 120・origin_label 150・clue_body 500・hypothesis_body 300・prediction_statement 300・result_note 300・attic_rule 300・attic_examples 500・reflection_text 500・scene_answer 500・invitation_note 100） | 各 FormRequest・`MakePrediction`・`LoginWithGoogle`・`InviteAdd` |
| `cue_examples` | 記録のきっかけの例文 | `SettingsController::show` |
| `today.open_cases` | 今日の画面の捜査中の事件の数（3） | `OpenCasesQuery` |
| `tasks` | `views`（タブ）・`empty`（空の一言）・`done_limit` 50・`max_minutes` 1440・`minute_choices`・`similar`（`limit`・`min_score`・`pool`） | `TaskListQuery`・`SimilarTasksQuery`・タスクの FormRequest・`components/task/*` |
| `cases` | `per_page` 20・`verify_task_title`（`確かめる：:statement`）・`check_after_days` 1・`conclusion_max` 1000・`attic_options` 50・`closed_message`（閉じた事件に書き込もうとしたときの 403 の文。`WritesOpenCase`）・`work_caution`（仕事の場面の最初の注意） | `CaseListQuery`・`MakePrediction`・`CaseBoardQuery`・`CloseCaseRequest`・`components/case/work-caution`（`case-step.clue-card` の中） |
| `case_list` | 事件の一覧の言葉（つまみ `tabs`・`tabs_kid`・一言 `leads`・段の名前 `steps`・空のとき `empty`・`case_no`） | `cases/index`・`case/list-filter`・`case/summary`・`case-step/stages`・`now` |
| `case_new` | 事件を作る画面（1問ずつ）の言葉（`page_title`・4つの問い `steps`・場面と種類の札・相棒モード・確かめの札） | `cases/create-steps`・`components/case-new/*` |
| `case_board` | 事件の画面の言葉（`page_title`・カードごとの `cards`・段の色 `stage_tones`・`back_open`（カードに戻る `?open=` として受け付ける値）・ほかにできること `more`・`watson`・子ども向け `kid`） | `cases/board`・`components/case-step/*`・`RedirectsToCase` |
| `case_partner` | 相棒の渡し合いの言葉（`handoff` 付箋・`write` 書く・`mark` ○・`pass`／`call` 渡す・よぶ・`together` いっしょに見せ合おう・`reveal` 見せ合い） | `components/case-partner/*` |
| `resolve` | 答え合わせの画面の言葉（前に書いた予想・4つの結果・次の一手） | `predictions/show`・`case/prediction-original`・`resolve-form`・`resolve-result`・`ResolvePredictionController` |
| `calibration_min_count` | 区間を判断する最少の件数（5） | `Calibration::summarize` |
| `review` | `calibration_buckets`・`recent_attic`・`case_options`・`norbury_per_page`・`attic_per_page`・`questions`（週の問い3つ）・`empty_week`・`norbury`（`story`・`kid_tagline`）・`attic`（同）・`export_filename`・`delete_confirmation` | ふりかえりの Query・Service・部品・`ExportUserDataController`・`DeleteAccountRequest` |
| `calendar` | `name`（専用カレンダー名）・`timezone`・`pull_every_minutes` 15・`scopes`（2つ）・`read_scope`・`default_minutes` 30・`refresh_before_seconds`・`agenda_cache_seconds` 300・`agenda_fields`・`timeout_seconds`・`tries` 5・`backoff`・`done_prefix`・`verify_summary`・`link_label`・`untitled`・`urls`（`token`・`revoke`・`api`）・`consent`（つなぐ前の説明：`what`・`send`・`not_send`・`receive`・`company`・`stop`・`warning`） | `Services/Google/*`・`Jobs/*`・`ConnectCalendar`・`routes/console.php`・`calendar/connect`・`LegalController::privacy` |
| `suggest` | 提案（AI なし。2-14）：`clue_questions`（`default`・`work`）・`hypothesis_types`（`default`・`kid`・`work`・`emphasize_until`）・`prediction_template`（`default`・`work`・`kid`）・`blank`・`quote_length`・`check_dates`（`quick`・`calendar_days` 14・`calendar_limit` 3・`calendar_label`・`title_length`）・`attic`（`limit` 3・`pool`・`label`・`hypothesis_text`）・`stop_words`・`just_ended_hours` 3・`just_ended_label`・`captions` | `Queries/Cases/*Suggestions*`・`Support/WordOverlap`・`AgendaQuery`・`StorePredictionRequest`・`case.*-form`・`today.agenda`・`legal/privacy`・`legal/terms` |
| `notes` | `privacy`・`privacy_work`・`medical`・`footer`・`effect`・`age` | `ui.privacy-note`（仕事の場面は `privacy_work`）・`case.work-caution`・`layouts.footer`・`home`・`legal/*`・`auth/agree` |

### 5-2. そのほかの設定

| ファイル | キー | 中身 |
|---|---|---|
| `config/watson.php` | `rules.{key}` | `id`（W-1〜W-6）・`title`・`body`・`alert`（W-5 だけ）。キーは `dismissed_hints.rule`（30 字）に入るので変えない。並び順＝優先度 |
| | `quote_length` | 引用の幅（48） |
| | `detect` | `guess`・`cause`・`work_better`（正規表現の配列）・`email`・`phone`・`name_suffix`・`not_names`・`not_name_endings` |
| `config/legal.php` | `operator_name`・`contact_email`・`established_on`・`effective_on` | 【】は運営者が書く（AI が作らない） |
| | `backup_days` 7・`access_log`（`file_mb` 10・`files` 5）・`error_log_days` 90・`shutdown_notice_days` 30・`google_user_data_policy`・`processors` | プライバシーポリシーの数字。`docker-compose.yml` の `x-logging`・本番の `LOG_DAILY_DAYS` とそろえる |
| `config/services.php` | `google.client_id`・`google.client_secret`・`google.redirect`（既定 `/auth/google/callback`） | 両方あれば `GoogleLoginController::enabled()`・`GoogleTokens::configured()` が真。カレンダーの戻り先は `redirectUrl(route('calendar.callback'))` で上書き |
| `config/ai_connect.php` | `server`・`tokens`（`access_minutes` 60・`refresh_days` 30）・`prune_unused_apps_days` 7・`limits`・`instructions`（窓口の説明。先頭の512字で意味が通るように）・`tools.{name}`（`title`・`description`）・`tool_tail`・`messages`・`consent`（同意の画面と `companies`）・`panel`（設定の枠）・`copy`（写すボタンと写す文） | ChatGPT・Claude とつなぐ窓口（2-15）。`bin/check` の使わない言葉の決まりも効く |
| `config/mcp.php` | `redirect_domains`（`https://chatgpt.com`・`https://claude.ai`・`https://claude.com`） | laravel/mcp の設定。`*` に戻さない（`rules/ai-connect.md`） |
| `config/auth.php` | `guards.api`（`driver` `passport`） | 窓口の `auth:api` |
| `config/app.php` | `timezone` `Asia/Tokyo`・`locale` `ja` | — |
| `config/cache.php`・`queue.php` | 既定 `database` | 今日の予定の覚え（`cache` 表）・後回しの仕事（`jobs` 表） |
| `lang/ja/validation.php` | 日本語の検証メッセージと項目名 | FormRequest の `attributes()`・`messages()` は足りない所だけ |
| `phpunit.xml` | sqlite `:memory:`・`QUEUE_CONNECTION=sync`・`CACHE_STORE=array`・`GOOGLE_CLIENT_ID/SECRET` 空 | テストの環境 |

---

## 6. 後回しの仕事と予定

worker の箱（`docker-compose.yml` の `worker`）が `php artisan schedule:work & php artisan queue:work --sleep=3 --tries=3 --max-time=3600` を動かす。仕事の `tries()` が CLI の `--tries` より先。Job・予定のコードを変えたら `docker compose restart worker`。

| 仕事 | 出す所 | 中身 | 重ねない・試し直し |
|---|---|---|---|
| `Jobs\PushTaskToCalendar($taskId)` | `TaskObserver::created`・`updated`（`afterCommit`）・`PushAllTasksToCalendar` | タスクを読み直し、期日があれば `upsertEvent`（予定 ID がなければ決めて `saveQuietly`）、期日がなければ `unlink`。`calendar_synced_at`・`last_synced_at` を入れる。`GoogleAuthBroken` ならあきらめる | `ShouldBeUniqueUntilProcessing`（`uniqueId`＝taskId）・`tries`／`backoff` は config・`failed()` は task_id とエラーの種類だけログ |
| `Jobs\PushAllTasksToCalendar($userId)` | `ConnectCalendar::handle` | その人のまだ済んでいない期日つきタスクと `calendar_event_id` のあるタスク（済んだ・いつかにした分の書き直し）を `lazyById` で1件ずつ `PushTaskToCalendar` → `DeleteOrphanEvents::handle($connection)`（専用カレンダーを全件読み、`suiri_task_id` が本人の今あるタスクに当たらない予定を `deleteEvent`。印のない予定は消さない） | `tries`／`backoff` は config（片づけが失敗したら全体を試し直す。書き出しは置き換えなので二重にならない）・`failed()` は user_id とエラーの種類だけ。`GoogleAuthBroken` ならあきらめる |
| `Jobs\RemoveTaskFromCalendar($userId, $eventId)` | `TaskObserver::deleted`（`afterCommit`） | 持ち主の専用カレンダーから `deleteEvent` | `tries`／`backoff`・`failed()` は user_id だけ |
| `Jobs\PullCalendarChanges(?$connectionId)` | `routes/console.php` の `Schedule::job(new PullCalendarChanges)->cron('*/'.config('suiri.calendar.pull_every_minutes').' * * * *')` | id なし → `dispatchEach()`（active で専用カレンダーのある連携ごとに出し直す）、id あり → `ApplyCalendarChanges::handle` | `ShouldBeUnique`（`uniqueId`＝id か `all`・`uniqueFor`＝間隔の秒） |
| `cache:prune-expired` | `routes/console.php` の `Schedule::command('cache:prune-expired')->everyFiveMinutes()` | `cache` 表の期限切れの行を消す | — |
| `passport:purge --revoked`・`passport:purge --expired --hours=…` | `routes/console.php`（毎日） | 取り消した通行証・引換券・更新の札と、更新の札の期限（`ai_connect.tokens.refresh_days`＋1日）を過ぎたものを消す | — |
| `ai:prune-apps` | `routes/console.php`（毎日）・`Console\Commands\PruneAiApps` | 通行証が1つもないまま `ai_connect.prune_unused_apps_days` 日たった AI の自己登録を消す | — |

| オブザーバー | 結ぶ所 | 見る出来事 |
|---|---|---|
| `Observers\TaskObserver` | `CalendarServiceProvider::boot` の `Task::observe` | `saving`・`created`・`updated`（`SENT_COLUMNS`＝title・due_on・starts_at・duration_minutes・done_at・kind・prediction_id）・`deleted`。`saveQuietly()`・クエリの一括 `update()`・DB の cascade では動かない（取り込み・解除・退会はこれを使って書き戻しを避けている） |

---

## 7. 壊さない決まり → 守っているコード → テスト

元は `.claude/rules/domain.md` の「壊さない決まり」・`privacy.md`・`google.md`。機械のチェックは `bin/check` の `RULES`・`WORD_RULES`・`SIZE_LIMITS`。

| 決まり | 守っているコード | テスト・チェック |
|---|---|---|
| 1. 予想の文と確かさは保存後に書き換えない。答え合わせの画面で最初の文と書いた日時を見せる | `Prediction::FROZEN`・`Prediction::booted`（updating で `LogicException`）・`routes/web/cases.php` に直すルートなし・`prediction-card` に直すボタンなし・`components/case/prediction-original`・`ResolvePrediction`／`SyncPredictionCheck` は FROZEN 以外だけ書く | `tests/Feature/Models/ModelRulesTest.php`・`tests/Feature/Cases/PredictionTest.php`・`ResolvePredictionTest.php`・`tests/Feature/Tasks/VerifyTaskTest.php`（期日を写しても文と確かさは変わらない） |
| 2. 他人の記録を持たない・見せない（全記録が `user_id` を持ち、持ち主で絞る） | `Policies\Concerns\OwnerOnly`＋ルートの `->can()`・Query は全部 `$user->…()` から・FormRequest の `Rule::exists(...)->where('user_id', …)`（`attic_note_id`・`case_file_id`・`changed_case_file_id`）・`UpdateFitRequest::authorize`・`CopiesOwnerFromCase`・`Prediction::booted`・`SceneAttemptReader`・`ApplyCalendarChanges`（`where('user_id')`）・`TaskListQuery::justDone`・他人を表す名前は `bin/check`（コードの名前の決まり） | `tests/Feature/Policies/OwnerOnlyTest.php`・`Cases/OwnershipTest.php`・`Tasks/TaskOwnershipTest.php`・`Review/AtticNoteTest.php`・`WeeklyReflectionTest.php`・`ReviewPageTest.php`・`NorburyTest.php`・`CalibrationTest.php`・`Guide/SceneCardTest.php`・`Calendar/PullCalendarChangesTest.php`・`PullRemovedEventsTest.php`・`DisconnectCalendarTest.php`・`Account/ExportUserDataTest.php` |
| 3. カレンダーに本文を送らない（タスクの題名・日時・戻るリンクと、見分けるための番号 `suiri_task_id` だけ。確かめるタスクは決まった題名） | `TaskEvent::toEvent`・`summary`・`link`・`CalendarApi::insertCalendar`（名前とタイムゾーンだけ）・`listEvents` の `fields`（受け取る項目も絞る）・`bin/check`「Google カレンダーに本文を送らない」（`Services/Google`・`Jobs` で本文の列を止める） | `tests/Feature/Calendar/PushTaskToCalendarTest.php`（送る鍵の一覧）・`tests/Unit/Google/TaskEventTest.php`・`CalendarApiTest.php` |
| 4. ログ・応答に本文を出さない（id・種類・件数・エラーの種類だけ） | `Log::` を使う所は全部 id と `class_basename($e)` だけ：`GoogleLoginController::callback`・`ConnectCalendarController::callback`・`DisconnectCalendar::handle`・`GoogleTokens::revoke`・`markBroken`・`GoogleCalendarAgenda::remember`・`TaskObserver::safely`・`PushTaskToCalendar::failed`・`RemoveTaskFromCalendar::failed`。題名を GET の URL に載せない：`tasks.draft`・`cases.draft`（POST → `withInput`）・`CaseController::create` は URL の `origin`・`practice` だけ受け取る | `bin/check`「ログに入力の本文を出さない」（専用のテストはない）・`tests/Feature/Cases/CreateCaseTest.php`・`Tasks/ManageTasksTest.php`（draft） |
| 5. 休んでも何も減らない（日数の続き・得点・順位を作らない。言葉は words.md の 3） | 表に該当の列がない・`DailyPractice`（提案だけ）・`WeeklyStats::percent`（分母 0 は null）・`suiri.review.empty_week` | `tests/Feature/Review/ReviewPageTest.php`（その言葉が出ない）・`WeeklyStatsTest.php`・`bin/check`「画面で使わない言葉」 |
| 6. ワトソンのひとことは保存を止めない・一度に1つ・閉じられる | `RedirectsToCase::backToCase`（保存のあとに警告）・`WatsonNotes::forCase`（1つだけ）・`DismissHint` | `tests/Feature/Cases/WatsonNoteTest.php`・`ClueAndHypothesisTest.php`・`CreateCaseTest.php`・`tests/Unit/Watson/*` |
| 7. 相棒モードは見せ合うまで互いの見立てを隠す | `CaseFile::hidesHypotheses`・`CaseBoardQuery::handle`・`StorePredictionRequest::authorize`・`RevealHypotheses` | `tests/Feature/Cases/PartnerModeTest.php` |
| 8. 日付だけの列は `Y-m-d` の文字列で比べる | `Prediction`・`Task`・`WeeklyReflection` は日付の列をキャストしない・`Prediction::isDue`・`TaskListQuery`・`DuePredictionsQuery`・`ReviewWeek::param` | `tests/Feature/Tasks/TaskListTest.php`・`Today/TodayPageTest.php`・`Models/ModelRulesTest.php` |
| 9. 回数制限は名前つき | `AppServiceProvider::boot` の `RateLimiter::for` | `bin/check`「回数制限は名前つきで」（1-4 の※も見る） |
| 10. 運営者が記録を読む管理画面を作らない | 招待はコマンド（`invite:*`） | `tests/Feature/Auth/InviteCommandTest.php` |
| 11. ログイン後の応答は取り置かせない・守りの札 | `SecurityHeaders::handle` | `tests/Feature/Legal/PublicPagesTest.php` |
| 12. トークンは暗号化して保存し、JSON・書き出しに出さない | `CalendarConnection::casts`（`encrypted`）・`#[Hidden]`・`ExportUserData`（calendar は 2 項目だけ） | `tests/Feature/Models/ModelRulesTest.php`・`Calendar/DisconnectCalendarTest.php`・`Account/ExportUserDataTest.php`・`tests/Unit/Google/GoogleTokensTest.php` |
| 13. 退会でその人の行をすべて消す（revoke してから） | `DeleteAccount::handle`・外部キーの cascade | `tests/Feature/Account/DeleteAccountTest.php`・`Models/ModelRulesTest.php` |
| 14. カレンダーから読んだ予定（今日の予定・確かめる日の提案のこれからの予定）は保存しない（題名と時刻だけ・5分だけ覚える） | `GoogleCalendarAgenda::remember`（`Cache::remember`）・`CheckDateSuggestionsQuery`（題名は画面に出すだけ）・`PruneExpiredCache` | `tests/Feature/Calendar/AgendaTest.php`・`UpcomingTest.php`・`PruneExpiredCacheTest.php`・`tests/Feature/Cases/CheckDateSuggestionsTest.php`（全部の表に題名が入らない） |
| 15. Google が止まっていても保存は成功させ、書き出しはあとで再送 | `TaskObserver::safely`・`afterCommit`・Job の `tries`／`backoff`・受付から Google を直接呼ばない（`bin/check`「コントローラーから外の API を呼ばない」） | `tests/Feature/Calendar/PushTaskToCalendarTest.php`・`TaskObserverTest.php` |
| 16. 確かめるタスクは丸で済みにしない。答え合わせ待ちの予想の確かめるタスクは消さず、期日を空にしない | `Task::answeredByPrediction`・`checksPendingPrediction`・`CompleteTaskRequest`・`ReopenTaskRequest`・`DeleteTaskRequest`・`DeleteTask`・`UpdateTaskRequest` | `tests/Feature/Tasks/CompleteTaskTest.php`・`VerifyTaskTest.php`・`tests/Feature/Calendar/PullRemovedEventsTest.php` |
| 17. 本番の記録を消さない（表を足す・直すのは移行だけ。見本はローカルだけ） | `DemoSeeder::run`（`environment('local')`）・`bin/check`「記録が消えるコマンドを手順に書かない」・フック `bin/hooks/guard-bash` | `tests/Feature/Demo/DemoSeederTest.php` |
| 18. 外部のフォント・CDN・解析を読み込まない | — | `bin/check`「外部のファイル（CDN・書体）を読み込まない」 |
| 19. AI の窓口は読むだけ（書き込み・答え合わせ・○×・状態・直す・消すの道具を置かない） | `SuiriServer::$tools`（4つ）・各道具の `#[IsReadOnly]` | `tests/Feature/Ai/ReadOnlyToolsTest.php` |
| 20. AI の窓口から仕事の場面・他人の記録・見せ合う前の見立てを見せない。見つからない・他人・仕事の場面は同じ文 | `Queries\Ai\VisibleCases`・`CaseSheetQuery`・`CaseListQuery`・`DuePredictionsQuery`・`AtticNotesQuery` | `tests/Feature/Ai/ReadToolsTest.php`・`DueAndAtticToolsTest.php` |
| 21. AI の戻り先は chatgpt.com・claude.ai・claude.com だけ。同意を飛ばさない・同意前の人に許可させない。通行証は1時間 | `config/mcp.php` の `redirect_domains`・`routes/web/ai.php`（`auth`・`agreed`）・`AiConnectServiceProvider` | `tests/Feature/Ai/RegisterAppTest.php`・`AuthorizeTest.php`・`McpAuthTest.php`・`DiscoveryTest.php` |

---

## 8. テストの地図

`src/tests/`。sqlite in-memory・`withoutVite()`（`tests/TestCase.php`）。テストのメソッドは 458（データの組を数えると 536 件。2026-10-31）。動かし方は `docker compose exec -T app php artisan test --compact`（1つだけは `--filter=名前`）。

偽物：Google ログインは `Socialite::fake('google', GoogleUser::fake([...]))`、カレンダーは `FakeCalendarApi`（テストでは singleton。`tests/Feature/Calendar/CalendarHelpers.php` の `fakeApi()`・`connectedUser()`・`configureGoogle()`）、HTTP は `Http::fake()`（`Http::preventStrayRequests()` で偽物にしていない外への問い合わせは失敗）。事件の準備は `tests/Feature/Cases/Concerns/BuildsCases.php`（`member()`・`caseOf()`・`board()`）。

| フォルダ | ファイル | 確かめていること |
|---|---|---|
| `Feature/Account` | `DeleteAccountTest`・`ExportUserDataTest` | 確認の言葉・全部の表から消える・revoke が失敗しても進む／JSON の中身・トークンと他人のデータが入らない |
| `Feature/Ai` | `AiAccountDataTest`・`AiCopyTest`・`AiLegalTextTest`・`AiSettingsTest`・`AuthorizeTest`・`DiscoveryTest`・`DueAndAtticToolsTest`・`McpAuthTest`・`PruneAiAppsTest`・`ReadOnlyToolsTest`・`ReadToolsTest`・`RegisterAppTest`（共通の準備は `Concerns/ConnectsAi`：テスト用の鍵・自己登録・PKCE・通行証の行） | 退会で許可の行が消える・書き出しに通行証の番号が出ない／写す文の中身・仕事の場面で出ない／ポリシー・規約の文／設定の枠・つないでいる AI は本人の分だけ・やめるとすぐ取り消し・他人の AI は 404／未ログインは Google へ・同意前は /agree・許可とやめる／案内の中身／答え合わせ待ちと棚の絞り方／通行証なしは 401・範囲のない通行証は 403・よその Origin は 403・窓口の説明の中身／使われない自己登録の掃除／道具は読むだけの4つ／仕事の場面・他人・見せ合う前を見せない／戻り先の絞り込み |
| `Feature/Auth` | `AgreementTest`・`GoogleLoginTest`・`InviteCommandTest` | 同意の画面への転送と2つのチェック・ログアウト／PKCE・準備中・招待制・sub での照合・未確認メール・同じメールの別アカウント／`invite:*` |
| `Feature/Calendar` | `AgendaTest`・`CalendarPanelTest`・`ConnectCalendarTest`・`DeleteOrphanEventsTest`・`DisconnectCalendarTest`・`PruneExpiredCacheTest`・`PullCalendarChangesTest`・`PullRemovedEventsTest`・`PushTaskToCalendarTest`・`TaskObserverTest`・`UpcomingTest` | 今日の予定の並び・覚える時間・空を返す場合／設定の枠の3状態／つなぐ前の説明・送り出しの引数・戻りの3つの確かめ・専用カレンダーの使い回し／つなぎ直しと全件のときに、もうないタスクの予定だけを消す（Http::fake）／解除と revoke／期限切れの覚え／取り込み（動かす・終日・消す・他人・差分の印・410・15分の予定）／ID だけの cancelled・全件のときだけ Google にない予定を外す・確かめるタスクを動かすと予想の確かめる日も動く／書き出しの中身と予定 ID・再送／見張りがいつ仕事を出すか／これからの予定（S-4）の並び・始まった予定を除く・覚える・空を返す場合・本物は1回の問い合わせ（Http::fake） |
| `Feature/Cases` | `AtticSuggestionsTest`・`CaseBoardStepsTest`・`CaseBoardTest`・`CaseLayoutTest`・`CaseListTest`・`CheckDateSuggestionsTest`・`CloseCaseTest`・`ClosedCaseWritesTest`・`ClueAndHypothesisTest`・`CreateCaseTest`・`CreateStepsTest`・`OwnershipTest`・`PartnerFlowTest`・`PartnerModeTest`・`PredictionTest`・`ResolvePredictionTest`・`StarterSuggestionsTest`・`WatsonNoteTest` | 提案（S-5）：本人の重なる知識だけ・3件まで・古くても選べる／提案（S-4）：すぐ選べる日・重なる予定だけ・3件まで・つないでいない人は読まない・題名がどの表にも入らない／いまの段に合わせてカードを1枚だけ出す・送ったあと同じカードに戻る（`CaseBoardStepsTest`）／一覧と事件の画面の並び・いまやることのカード・仕事の場面の言葉／入力欄の開き方／一覧の見せ方：空のとき・進み具合の一言・場面でしぼる（`CaseListTest`）／閉じる・棚に入る・開き直す／閉じた事件への書き込みは 403（答え合わせ・取り下げはできる）／手がかり・見立て・○×・状態の記録／作る画面・draft・練習メニュー・W-5／1問ずつの作る画面：問いの順・入力の名前・きっかけ・相棒モード・子ども向けの言葉（`CreateStepsTest`）／他人は 403（データの組で操作ごと）／渡し合いの部品：付箋 → 書く → ○ → 見せ合う、見せ合う前はもう片方の文を HTML に入れない（`PartnerFlowTest`）／相棒モード／予想と確かめるタスク・書き換え禁止・取り下げ（答え合わせ待ちだけ。済んだ予想は 403、Action は false）／答え合わせ（Action は鍵をかけて読み直し、取り下げ済み・済みなら false）／提案（S-1〜S-3）：場面・見立ての数・親子の人での出し分け・穴埋めのまま保存できない・閉じた事件は出さない／ワトソンの優先・閉じる |
| `Feature/Demo` | `DemoSeederTest` | ローカルだけ・二重にならない |
| `Feature/Guide` | `GuidePagesTest`・`PracticeSuggestionTest`・`SceneCardTest` | 手引きの全画面・404・`?kid`／今日の練習の部品／カードの表に推理が入らない・推理の保存と上書き・他人の推理が見えない |
| `Feature/Legal` | `PublicPagesTest` | 公開ページ・`config/legal.php` の値・守りの札・HSTS・no-store |
| `Feature/Models` | `ModelRulesTest` | 予想の書き換え禁止・持ち主の写し・○× の引き方・トークンの暗号化・小さな判定・全ファクトリーと退会の cascade |
| `Feature/Policies` | `OwnerOnlyTest` | 記録の種類ごとの持ち主だけ・`{case}` の結びつけ |
| `Feature/Review` | `AtticNoteTest`・`CalibrationTest`・`NorburyTest`・`ReviewPageTest`・`WeeklyReflectionTest`・`WeeklyStatsTest` | 棚の CRUD と 403／確かさの集計と画面／ノーベリ帳／週の住所・404・責めない一言／週の問い／週の数字の境目 |
| `Feature/Settings` | `KidWordsTest`・`UpdateSettingsTest` | 子ども向けの言葉の条件（ふだんの場面だけ・手引きは `?kid` が先）／設定の保存と検証 |
| `Feature/Tasks` | `CompleteTaskTest`・`ManageTasksTest`・`SimilarTasksTest`・`TaskListTest`・`TaskOwnershipTest`・`TaskRouteMiddlewareTest`・`VerifyTaskTest` | 済み・戻す・確かめるタスクの扱い／作る・直す・消す・draft・戻り先／似た実績／タブの振り分けと並び／他人は 403／直す・消すにも回数制限と持ち主の確かめが付く／確かめるタスクの期日を直すと予想の確かめる日も変わる・期日を空にできない・消せない（403。直す画面に予想の取り下げ）・答え合わせ済みなら記録のまま |
| `Feature/Today` | `JustEndedEventTest`・`TodayPageTest` | さっき終わった予定（S-6）：3時間以内だけ印・「気になる」を目立たせる／最初の画面・きっかけ・答え合わせ待ち・予定とタスクの混ぜ方・二重に出さない・期日切れの数・部品への受け渡し |
| `Unit/Enums` | `LabelTest` | 共通・仕事の場面・子ども向けの言葉、全値に言葉がある、確かさの選択肢 |
| `Unit/Google` | `CalendarApiTest`・`GoogleTokensTest`・`TaskEventTest` | REST の呼び方（409・404/410・503・401・ページ・410・fields）／更新・invalid_grant・revoke／予定の形と戻し方 |
| `Unit/Guide` | `DailyPracticeTest`・`GuideContentTest`・`ReasoningStepsTest` | 同じ日は同じ提案／教材の JSON の形・キーの重なり・技能のキー・誇張の言葉がない／推理の段 |
| `Unit/Review` | `CalibrationTest` | 区間・件数の境目・平均 |
| `Unit/Suggest` | `WordOverlapTest` | 言葉の重なり（漢字2文字・カタカナ3文字・全角英数字・体操着と体育は重ならない・どこにでも出る言葉は数えない） |
| `Unit/Watson` | `TextSignsTest`・`WatsonNotesTest` | しるしの当たり外れ（データの組）／W-1〜W-6 の出る条件・閉じた規則・`$skip` |

---

## 9. よくある作業 → 触るファイル

### 9-1. 新しい画面を足す

1. 住所：`src/routes/web/{機能}.php`（新しい機能ならファイルを置くだけで読まれる）。`middleware(['auth', 'agreed'])`、書き込みは `throttle:writes`、持ち主は `->can()`。クロージャ禁止。
2. 受付：`src/app/Http/Controllers/{機能}/`（CRUD は `{モデル}Controller`、ほかは `{動詞}{名詞}Controller` の `__invoke`）。
3. 検証：`src/app/Http/Requests/{機能}/{Store|Update}{モデル}Request.php`（状態の確かめは `authorize()`）。項目名は `src/lang/ja/validation.php`。
4. 用事・組み立て：`src/app/Actions/{機能}/`（`handle()` 1つ）・`src/app/Queries/{機能}/`。
5. 画面：`src/resources/views/{機能}/{動作}.blade.php`、部品は `src/resources/views/components/{分類}/`。言葉は `config/suiri.php` の `labels`。下のナビに出すなら `suiri.nav`。
6. テスト：`src/tests/Feature/{機能}/{動作}Test.php`（他人の記録が見えないテストを1つ以上）。
7. 文書：`docs/design/basic-design.md` の 2・`docs/design/components.md`（部品を足したら）・この索引の 1 章と 2 章。画面のスクリーンショット。`npm run build`。

### 9-2. 事件に新しい項目（列）を足す

1. 移行：`src/database/migrations/` に新しい1ファイル（既存の移行は直さない。`php artisan migrate`）。
2. モデル：`src/app/Models/CaseFile.php` の `#[Fillable]`・`casts()`。種類の列なら `src/app/Enums/` に列挙を足し、`config/suiri.php` の `labels`（＋`by_scene`・`kid`）に言葉を。
3. ファクトリー：`src/database/factories/CaseFileFactory.php`。見本：`src/database/seeders/Demo/*`。
4. 入力：`StoreCaseRequest`／`UpdateCaseRequest` の `rules()`・`prepareForValidation()`、`config/suiri.php` の `limits`、`CreateCase::handle`。
5. 表示：`CaseBoardQuery`／`CaseListQuery`、`components/case-step/head`・`edit-case-card`（直す）・`case/summary`（一覧）など。
6. 書き出し：`src/app/Actions/Account/ExportUserData.php`（cases は `toArray()` なので列は自動で入るが、関係を足したら `with` に足す）。
7. 本文の列なら：`bin/check` の「ログに入力の本文を出さない」「Google カレンダーに本文を送らない」の列の一覧に足す。ワトソンに読ませるなら `WatsonNotes::allTexts`／`reasoningTexts`。W-5 の確かめは `RedirectsToCase::backToCase` の `$texts`。
8. 文書：`docs/design/data-model.md`・`words.md` と `.claude/rules/domain.md`（両方そろえる）・プライバシーポリシー（取る情報が増えるなら）。

### 9-3. ワトソンの規則を足す

1. `src/config/watson.php` の `rules` に、優先度の位置に入れる（キーは 30 字まで・あとから変えない）。探す言葉は `detect`。
2. `src/app/Services/Watson/WatsonNotes.php` の `match()` の `match ($rule)` に1行。文を探す処理は `TextSigns` にメソッドを足す。
3. 閉じるルートは `whereIn('rule', array_keys(config('watson.rules')))` なので足すだけで通る。
4. いまの一手と同じことを言うなら `CaseBoardQuery::handle` の `$skip`。
5. テスト：`src/tests/Unit/Watson/WatsonNotesTest.php`・`TextSignsTest.php`、画面は `tests/Feature/Cases/WatsonNoteTest.php`。
6. 文書：`docs/design/requirements.md` の 4-4。

### 9-4. 教材（手引き）を直す

1. `src/database/data/guide/{skills,scenes,practices,reasoning,overview}.json`（項目は `docs/design/data-model.md` の 4。書き方・引用の決まりは `.claude/rules/guide-content.md`）。読み込みは `GuideLibrary::load`（ファイルの更新日時がキャッシュのキーなので、直せば読み直される）。
2. 名推理カードの表に出してはいけない欄は `SceneController::BACK_ONLY`。推理の段の目印は `ReasoningSteps::MARKS`。
3. 練習メニューのキーを変えたら：事件の `practice_key`（`StoreCaseRequest` が `GuideLibrary::practices()` のキーで確かめる）と `DemoSeeder` の見本に影響。
4. テスト：`src/tests/Unit/Guide/GuideContentTest.php`（形・件数・キー・誇張の言葉）・`tests/Feature/Guide/*`。`bin/check` が教材の JSON の言葉も見る。

### 9-5. カレンダーに送る項目を変える

1. 予定の中身：`src/app/Services/Google/TaskEvent.php`（`toEvent`・`times`・`summary`・`link`）。決まった文は `config/suiri.php` の `calendar`。
2. 予定に出る列が増えるなら `TaskObserver::SENT_COLUMNS`。
3. 受け取る項目（今日の予定）は `suiri.calendar.agenda_fields` と `GoogleCalendarAgenda::item`。スコープは `suiri.calendar.scopes`／`read_scope`（変えたら `ConnectCalendar::check` も効く）。
4. 説明をそろえる：`suiri.calendar.consent`（つなぐ前の画面）・`resources/views/legal/privacy.blade.php`・`config/legal.php` の `processors`・`.claude/rules/google.md`・`privacy.md`。
5. 本文を送らない決まり：`bin/check` の「Google カレンダーに本文を送らない」。
6. テスト：`tests/Feature/Calendar/PushTaskToCalendarTest.php` の送る鍵の一覧・`tests/Unit/Google/TaskEventTest.php`・`CalendarApiTest.php`・`tests/Feature/Calendar/ConnectCalendarTest.php`（説明の画面）・`tests/Feature/Legal/PublicPagesTest.php`（ポリシー）。
7. worker を再起動（`docker compose restart worker`）。

### 9-6. 言葉（表示）を変える

`src/config/suiri.php` の `labels`（列挙・`words`・`by_scene`・`kid`）→ `docs/design/words.md` と `.claude/rules/domain.md` を両方直す → `tests/Unit/Enums/LabelTest.php`。コードの名前は変えない。

---

## 10. 機械のチェック（`bin/check`）

| 呼ばれる所 | 中身 |
|---|---|
| `bin/hooks/guard-bash`（コマンドの前） | SSH・記録が消えるコマンド・強制プッシュ・チェックを飛ばすオプション・`.env` の追加・ホストでの php/composer。本番の ssh の中では `passport:keys --force`（鍵の作り直し）も止める（`passport:keys` だけは初回の手順として通す） |
| `bin/hooks/after-edit`（ファイルを直した直後） | pint・`RULES`・`WORD_RULES`・`SIZE_LIMITS`・コメントの抜け（`bin/code_comments.py`） |
| `bin/hooks/before-stop`・`.githooks/pre-commit`（`bin/check staged`） | 決まり・秘密の値・画面のビルド・整形・テスト・ドキュメント（リンク・`docs/guide.html` の作り直し忘れ） |
| 表 | `RULES`（太らない MVC・`env()`・`@php`・CDN・影・ログの本文・カレンダーの本文・記録が消えるコマンド）・`WORD_RULES`（画面の言葉・他人を表すコードの名前・推論の3つの型の言葉は手引きだけ・ドキュメントの言葉）・`SIZE_LIMITS`／`ALLOW_SIZE`（行数）・`ALLOW`／`WORD_ALLOW`（理由つきの例外） |
