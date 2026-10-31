# R6 既存プロダクトから使い回すもの と Laravel 13 の太らない MVC 設計

調べた日：2026-10-31　／　調べた人：R6 担当（読むだけ。既存のリポジトリは1文字も書き換えていない）

## 0. 先に結論（3行）

1. **土台はほぼ Baker Street English（以下 BSE）から写せる。** Docker の3つの箱（nginx・php-fpm・MySQL）、2026-10 の PHP の重大な脆弱性への対策ずみの PHP／nginx の設定、CloudFront の後ろで動かす3点セット、Google ログインの受付係とそのテスト、決まりを機械で止める仕組み（`bin/check`・Claude Code のフック・git の pre-commit）、ドキュメントを1つの HTML にする道具は「名前を変えれば使える」。作り直すのは**物語（ドメイン）に結びついた所だけ**（言葉の対応表・画面の決まり・`bin/check` の禁止語）。
2. **BSE にも「太る」芽がある。** 実測で `voice.js` 981 行、`QuestionBuilder.php` 290 行、`casebook.blade.php` 292 行、`InvestigationController.php` 203 行（public メソッド 7 つ）。検証はコントローラーの中の `$request->validate()`（10 か所）で、FormRequest・Policy は 0、Blade コンポーネントは 1 つだけ。新しいアプリでは**最初から**「FormRequest・Policy・Action・Blade コンポーネント・機能ごとのルートファイル・行数の上限を `bin/check` で止める」を入れる。
3. **新しいアプリの雛形がすでにある**：`04.product/suiri-techo`（推理手帳。2026-10-31 12:42 作成、Laravel v13.39.0、`10.232.0.0/24`、ポート 8086・DB 3311、`worker` の箱あり。`infra/` は BSE と同じ）。**雛形に付いてくる `src/CLAUDE.md`・`src/AGENTS.md`（Laravel Boost の案内。「ホストに PHP を入れる」とある）は BSE の決まり（コマンドはコンテナ経由）とぶつかる**ので消し、直下の `CLAUDE.md` にする。

---

## 1. 使い回しの設計図

### 1-1. 調べたプロダクトと、それぞれから学ぶこと

| プロダクト | 中身 | 新しいアプリに持っていくもの |
|---|---|---|
| BSE `04.product/baker-street-english` | Laravel 13.36 / PHP 8.4 / MySQL 8.4 / Tailwind v4 / Vite 8。Google ログイン（Socialite 5.31）。本番は CloudFront → EC2 | ほぼ全部の「型」。決まりの機械チェック、ドキュメント一式、本番の手順書 |
| oshi-meter / subsc-manager | 小さな Laravel（コントローラー 2〜4 個、`Route::resource`、`_form.blade.php` を作成・編集で共用） | `Route::resource` と `_form` の共用は良い型。**引き継がないもの**：`throttle:10,1`（名前なしの回数制限。BSE の決まり 7 で禁止）、持ち主の確認を各メソッドで手書き（`authorizeOwner`。Policy にする）、検証をコントローラーの private メソッドで（FormRequest にする） |
| pixel-office `04.product/pixel-office` | AdonisJS 7（Node）。Laravel ではない | 手順書（講座どおりの AWS 配置・Google の同意画面の公開）と、守りの考え方（CloudFront だけが知る秘密のヘッダー `ORIGIN_SECRET` でオリジンを守る、ログイン後の戻り先を `safePath()` で絞る、法律のページの数字をコードと合わせるテスト）。コードは使えない |

### 1-2. 土台（Docker・infra）

判定：◎ そのまま使える／○ 名前を変えれば使える／△ 作り直す（骨組みは使う）

| 新しいアプリのパス | 元のパス（BSE からの相対） | 判定 | 手直しの中身 |
|---|---|---|---|
| `docker-compose.yml` | `docker-compose.yml` | ○ | `name:`、ネットワーク名 `bse-net`→`<app>-net`、`subnet` を空いている帯に（このマシンでは `10.231`＝BSE、`10.232`＝suiri-techo が使用中）。ログの上限 `x-logging`（10MB×5）は残す。雛形 suiri-techo はすでに対応ずみで、`worker` の箱（`schedule:work` と `queue:work`）を足している。カレンダーへの書き込みを後回し（キュー）にするなら必要 |
| `.env.example`（直下。compose が読む） | `.env.example` | ○ | `DB_DATABASE`/`DB_USERNAME` の名前、ローカルの `WEB_LISTEN`・`DB_PORT` |
| `infra/php/Dockerfile` | `infra/php/Dockerfile` | ◎ | `php:8.4-fpm-bookworm`＋composer 2.8＋Node 22＋`intl pdo_mysql zip bcmath`。google/apiclient v2.20.1 の要件は `php ^8.1` で、追加の拡張は要らない見込み（4 章）。**修正版（8.4.27 以上）のイメージで作り直す**。本番の速さが要るなら opcache を足す（BSE は入れていない） |
| `infra/php/php.ini` | `infra/php/php.ini` | ◎ | `display_errors=off`、`upload_max_filesize=2M`・`post_max_size=8M`、`session.upload_progress.enabled=0`、`session.use_strict_mode=1`、時刻は Asia/Tokyo |
| `infra/php/zz-security.conf` | `infra/php/zz-security.conf` | ◎ | `php_admin_flag[session.upload_progress.enabled]=off`（`.user.ini` で上書きできない形） |
| `infra/nginx/default.conf` | `infra/nginx/default.conf` | ○ | `multipart/form-data` を 415 で止める行は残す（ファイルを上げる機能を作るまで）。PWA の `sw.js`・`manifest.webmanifest` の行は PWA にしないなら消す。足すとよいもの：`server_tokens off;`、CloudFront の秘密のヘッダーの確かめ（1-7） |
| `infra/mysql/Dockerfile`・`my.cnf` | 同じ | ◎ | `mysql:8.4`、utf8mb4 / utf8mb4_unicode_ci |
| `.gitignore`（直下） | `.gitignore` | ◎ | `.env`、`tools/docs-html/node_modules/`、`__pycache__/` |
| `src/.gitignore` | `src/.gitignore` | ○ | 最後の `notes-input` の行（BSE 専用）を消す |
| `src/.env.example` | `src/.env.example` | ○ | 先頭の「本番で変える4行＋SESSION_SECURE_COOKIE」の注意書き、ログを日ごと 90 日（`LOG_STACK=daily`・`LOG_DAILY_DAYS=90`）、`GOOGLE_CLIENT_ID/SECRET` の説明は残す。`APP_NAME`・`APP_URL`（ローカルのポート）・`DB_*` を変える。カレンダー用に足すキーは 1-8 |
| `src/vite.config.js` | `src/vite.config.js` | ◎ | Tailwind v4 の Vite プラグイン。外部のフォントを読まない方針のコメントも残す |

### 1-3. 決まりを機械で確かめる仕組み

BSE は「読むだけの決まり」に「出口の検品係」を足している。呼ばれる場面は4つ：コマンドの前（`guard-bash`）・ファイルを直した直後（`after-edit`）・作業を終える前（`before-stop`）・コミットの前（`.githooks/pre-commit`）。どれも中身は `bin/check` の呼び出しで、**止めたときは理由と「→ 直し方」を返す**のが良い所。

| 新しいアプリのパス | 元のパス | 判定 | 手直しの中身 |
|---|---|---|---|
| `bin/check`（Python、316 行） | `bin/check` | △ | 骨組み（`sh`・`git_files`・`check_secrets`・`run`・`container`・`tests`・`pint_test`・`report`・`main` の4つのモード rules / docs / staged / worktree・`ALLOW` の例外表）は**そのまま**。`RULES` のうち物語の禁止語（猫先生・正の字・`@section('wide')`・`dom.js` の `each`・メールアドレス）は消し、「外部の CDN を読まない」「名前なしの回数制限を書かない」「使わないと決めた言葉を書かない」「記録が消えるコマンドを手順に書かない」は残す。`check_sw_version`（PWA）と `notes_check`（例文の解説）は使わなければ消す。**新しく足す**：行数の上限 `check_size`、太らない MVC の禁止パターン（5-6 章） |
| `bin/code_comments.py`（132 行） | `bin/code_comments.py` | ◎ | 「クラス・関数・if・ループ・try の手前にコメント」がないと止める。対象が `src/app/**` などなので `app/Actions`・`Requests`・`Policies` も自動で入る |
| `bin/hooks/after-edit` | 同じ | ◎ | PHP なら pint で整形 → `bin/check rules <file>` → 違反を `{"decision":"block"}` で AI に返す |
| `bin/hooks/before-stop` | 同じ | ◎ | `bin/check worktree`。2 回目（`stop_hook_active`）は止めずに知らせるだけ（同じ失敗のくり返しを防ぐ） |
| `bin/hooks/guard-bash` | 同じ | ○ | 文字列の中身（コミットのメッセージ・ヒアドキュメント）を消してから見る作り、強制プッシュ・`--no-verify`・`core.hooksPath` の変更・`.env` の追加・`migrate:fresh` 等・ホストでの php/composer を止める部分はそのまま。**本番 SSH の例外 `PROD_SSH`（`bse-prod-key.pem` と BSE の Elastic IP が直書き）は消して始める**。新しいサーバーで AI に「見る・反映」を許すかは利用者がその時に決める（許されたら鍵の名前 `<app>-prod-key.pem` と新しい Elastic IP で書き直す） |
| `.githooks/pre-commit` | 同じ | ◎ | `exec bin/check staged`。クローンしたら1回 `git config core.hooksPath .githooks`（guard-bash はこの設定の変更を止めるので、利用者が手で打つ） |
| `.claude/settings.json` | 同じ | ◎ | PreToolUse（Bash）・PostToolUse（Edit/Write/MultiEdit、60 秒）・Stop（300 秒）の3つ |
| `.claude/rules/*.md`（先頭の `paths:` で自動読み込み） | `.claude/rules/` の9つ | △（構成は ◎） | そのまま使える：`code-comments.md`。名前を変えれば：`deploy.md`・`docs.md`・`tests.md`。作り直す：`domain.md`（言葉の対応表）・`ui-design.md`・`accounts-legal.md`。新しく作る：`architecture.md`（太らない MVC。5 章）、`google-calendar.md`（スコープ・トークンの扱い・外部送信の公表） |
| `.claude/skills/` | `word-content`・`scene-images` | — | BSE 専用。使い回さない（新しいアプリで手順ものが出てきたら同じ形で作る） |
| `CLAUDE.md`（＋`AGENTS.md` はリンク） | `CLAUDE.md`（6KB） | △ | **型を使う**：①1段落の説明と技術 ②作業 → 自動で読まれる決まりの表 ③機械で確かめていること（フック4つ） ④どの作業でも守ること（日本語・コメント・コマンドはコンテナ経由・URL とテスト用アカウント・本番で AI がしてよいこと・設定値は config・外部 CDN なし・test/pint/ビルド/スクリーンショット） ⑤よく使うコマンド |

### 1-4. アプリのコード

| 新しいアプリのパス | 元のパス（`src/` から） | 判定 | 手直しの中身 |
|---|---|---|---|
| `bootstrap/app.php` | `bootstrap/app.php` | ○ | `trustProxies(at: '*')`、`append(SecurityHeaders::class)`、`redirectGuestsTo(...)`、JSON を求める要求のエラーは JSON、`health: '/up'` を写す。BSE 専用の `goal` の別名は消す。ルートを機能ごとのファイルに分ける書き方は 5-3 |
| `app/Providers/AppServiceProvider.php` | 同じ | △ | 写すのは2つ：**名前つきの回数制限**（`RateLimiter::for('auth', …)` を種類ごと）と、**本番だけ `URL::forceScheme('https')`**。パスワード再設定メールの日本語化は、メールとパスワードのログインを作るときだけ |
| `app/Http/Middleware/SecurityHeaders.php` | 同じ | ○ | nosniff・SAMEORIGIN・Referrer-Policy・Permissions-Policy・CSP（Report-Only から始める）・本番では CloudFront の後ろでも HSTS。マイクを使わないなら `microphone=()` に。YouGlish の許可は消す |
| `app/Http/Controllers/GoogleAuthController.php` | 同じ（106 行） | ○ | 1-8 で説明。BSE 専用の `TrialClaim`（お試しの引き継ぎ）と行き先 `goal.edit`・`casebook` を差し替える |
| `config/services.php` の `google` | 同じ | ◎ | `redirect` の既定が相対パス `'/auth/google/callback'` なのが要点（1-6） |
| モデル・コマンドの書き方 | 例 `app/Models/StudyDay.php`・`app/Console/Commands/ImportWords.php` | ◎（型） | Laravel 13 の属性 `#[Fillable]`・`#[Hidden]`・`#[Signature]`・`#[Description]`。クラスの手前に「何の記録か・だれが書くか」 |
| `resources/views/errors/*.blade.php`（404・403・419・429・500・503＋`layout`） | 同じ | ○ | 見た目を新しいアプリに合わせる。「auth やセッションに触らない」決まりは残す |
| `resources/views/pages/privacy|terms|external` と `config/legal.php` | 同じ | △ | 形（運営者の情報は config の1か所、空欄は運営者が埋める・AI は埋めない）を使い、中身は新しく書く。Google の同意画面の公開には `/privacy`・`/terms` の URL が要る（pixel-office の GOOGLE_LOGIN.md と同じ考え） |
| `tests/TestCase.php` | 同じ | ◎ | `withoutVite()`（テストでビルドが要らない） |
| `tests/Feature/GoogleAuthTest.php` | 同じ（104 行） | ○ | Socialite を Mockery の偽物にして ①設定がないとボタンが出ず 404 ②新しい人は登録 ③確認済みメールは既存に結びつく ④未確認メールは乗っ取れない、を確かめる。Socialite 5.31 の `Socialite::fake()` に替えてもよい |
| `phpunit.xml` | 同じ | ◎ | sqlite in-memory、`BCRYPT_ROUNDS=4`、`SESSION_DRIVER=array` |

### 1-5. ドキュメントと道具

| 新しいアプリのパス | 元のパス | 判定 | 手直しの中身 |
|---|---|---|---|
| `tools/docs-html/build.mjs`（→ `docs/guide.html`） | 同じ | ○ | 題名「Baker Street English 解体新書」2か所、一時フォルダ名 `bse-docs-html`、localStorage のキー `bse-guide-*`、付録にする設計書の一覧 `CHAPTERS` を直す。marked 15.0.12 だけを使い、画像は WebP にして中に埋め込む（ffmpeg が要る） |
| `tools/docs-html/flow.mjs`（→ `docs/flow.html`） | 同じ | ○ | 題名・キー名に加えて、**住所の表の指紋が `src/routes/web.php` 1つ前提**。ルートを機能ごとのファイルに分けるなら `routes/web.php` と `routes/web/*.php` をまとめて指紋にする（`bin/check` の判定も同じく） |
| `tools/docs-html/refresh_excerpts.py`・`sync_excerpts.py` | 同じ | ◎ | 流れ解説のコードの抜き出しを、元のファイルのコメントに合わせ直す道具 |
| `tools/docs-html/package.json`・`package-lock.json` | 同じ | ○ | `name` を変える |
| `docs/README.md`・`docs/guide/NN-*.md`・`docs/ai/codemap.md`・`docs/design/*.md`・`docs/flow-src/` | 同じ | △（型は ◎） | 2 章の「ドキュメント一式の型」で作り直す |

注意：BSE の `docs/guide.html` は 11.8MB、`docs/flow.html` は 3.9MB（画像を中に埋め込むため）。作り直すたびに git の履歴がこの大きさずつ増える。スクリーンショットは必要な所だけにし、横幅を縮める（build.mjs は 1600px、flow.mjs は 1400px の WebP にしている）。

### 1-6. CloudFront の後ろで動かす設定（3点セット＋1）

本番の形は「ブラウザ —https→ CloudFront —http(80)→ EC2 の nginx → php-fpm」。アプリから見ると要求は http で届くので、何もしないと Laravel が http の URL を作り、フォームの送信先や Google の戻り先が http になる。BSE は次の4つで解いている（`.claude/rules/deploy.md` に「消さない」と書いてある）。

| # | 場所 | 中身 | なぜ |
|---|---|---|---|
| 1 | `bootstrap/app.php` | `$middleware->trustProxies(at: '*');` | CloudFront が付ける `X-Forwarded-For` から本当のアクセス元の IP を取る（回数制限を IP ごとに数えるのに要る）。送り元を限定しないのは「EC2 には CloudFront からしか届かない（セキュリティグループで CloudFront のプレフィックスリストに絞る）」前提だから |
| 2 | `AppServiceProvider::boot` | 本番だけ `URL::forceScheme('https')` | 講座（week6 の4回目、ステップ13）の説明では、CloudFront は「元は https だった」ことを示す `X-Forwarded-Proto` を付けない。だから 1 だけでは http の URL ができてしまい、URL を必ず https で作るこの行が要る。1 と 2 は役目が違うので両方入れる（講座も BSE も同じ） |
| 3 | `SecurityHeaders` | `$request->isSecure() \|\| app()->isProduction()` のとき HSTS | アプリからは https に見えないので、本番なら付ける |
| 4 | `config/services.php` | `'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback')` | 相対パスにしておくと Socialite が今の URL の決まり（2 と、CloudFront が渡す Host）で絶対 URL にするので、本番でも `https://app.<ドメイン>/auth/google/callback` になる。ただし CloudFront のオリジンリクエストポリシーが Host を渡す **AllViewer** であることが前提（AllViewerExceptHostHeader だとオリジンの名前の URL になる。推論）。いちばん確実なのは、本番の `src/.env` に `GOOGLE_REDIRECT_URI=https://app.<ドメイン>/auth/google/callback` と絶対 URL で書くこと（ローカルは相対のまま） |

本番の `src/.env` は `APP_ENV=production`・`APP_DEBUG=false`・`APP_URL=https://…`・`LOG_LEVEL=error`・`SESSION_SECURE_COOKIE=true`（BSE の `rules/deploy.md`）。

### 1-7. セキュリティの強化（BSE の最近のコミットから）

`git log` で確かめたコミット（新しい順）：

| コミット | 中身 | 新しいアプリ |
|---|---|---|
| `9a87fd7` | `post_max_size` を 8M、`upload_max_filesize` を 2M に下げる（BSE のコミットでは「php2shell 勧告」と呼んでいる php.net の 2026-10-21 の告知の緩和策。BSE はファイルを上げない） | ◎ そのまま |
| `ab8b707` | nginx で `multipart/form-data` を 415 で拒否、FPM のプールで `upload_progress` を `php_admin_flag` で固定 off、`session.use_strict_mode` | ◎ そのまま（ファイルを上げる機能を作るときはその経路だけ通す） |
| `546ef84` | `session.upload_progress.enabled = 0`（PHP を直した版に上げるまでの応急処置） | ◎。php.ini のコメントの CVE-2026-109618/109619 は php.net の 2026-10-21 の告知と一致（4 章）。根本の対策は**PHP を修正版（8.4.27 / 8.5.12 以上）に上げる**こと（`docker compose build --pull app`） |
| `33dcb1c` | CloudFront が http で届けても本番では HSTS | ◎（1-6 の 3） |
| `db883e7`・`6911d28` | guard-bash：本番への読むだけの SSH を許す、ssh-keygen は止めない | ○（新しいアプリは本番 SSH の例外なしから始める） |

ほかに写すもの：名前つきの回数制限（種類ごとに別々に数える。まとめると「お試し直後の登録が 429」のような事故が起きた）、Google の未確認メールで既存アカウントに結びつけない、ログイン後に `session()->regenerate()`、エラー画面はセッションに触らない、ログは 90 日で消す（プライバシーポリシーと合わせる）、秘密の値を git に入れない検査（`AKIA…`・秘密鍵・`.env` の値）。

pixel-office から足すとよいもの：**CloudFront のオリジンにカスタムヘッダー（秘密の値）を付け、nginx で一致しない要求を 403 にする**。セキュリティグループのプレフィックスリストだけだと「ほかの人の CloudFront」からも届くので、それを止められる（pixel-office は `ORIGIN_SECRET` でこれをしている）。ログイン後の戻り先は自分のサイトの中だけ（Laravel の `redirect()->intended()` は同じサイトの URL を前提にしている）。

### 1-8. Google ログインの実装（BSE）と、カレンダー連携の足し算

BSE の `GoogleAuthController`（106 行・メソッド3つ）の流れ：

1. `redirect()`：鍵が未設定なら 404（`enabled()` が false）→ `Socialite::driver('google')->redirect()`
2. `callback()`：`try` で `Socialite::driver('google')->user()`。失敗（キャンセル・時間切れ）はログに残してログイン画面へ
3. `google_id` で探す → なければ同じメールの利用者に結びつける（**Google が確認済みのメールのときだけ**。項目名が `email_verified` と `verified_email` の2通りあるので両方見る）→ それもなければ新しく登録（パスワードは null）
4. `Auth::login($user, remember: true)` → `session()->regenerate()` → 新しい人と前からの人で行き先を分ける

新しいアプリは**Google ログインだけ**にするなら、メールとパスワードの登録・再設定・メール変更（BSE の `AuthController`・`PasswordResetController`・`AccountController` の一部）は作らなくてよい。ログインの鍵が Google だけになるので、3 の「既存アカウントへの結びつけ」も要らなくなり、さらに短くなる。

カレンダー連携で足すもの（版とスコープは 4 章）：

| 足すもの | 中身 |
|---|---|
| スコープ | ログインは `openid email profile`（Socialite の既定）。カレンダーは**ログインとは別のボタン**（「カレンダーとつなぐ」）で後から追加の同意をもらう。最小のスコープ（4 章） |
| オフラインの鍵 | `Socialite::driver('google')->scopes([...])->with(['access_type' => 'offline', 'prompt' => 'consent'])->redirect()` で refresh token を受け取る |
| 保存 | 別の表（例 `calendar_connections`：user_id・refresh_token・access_token・expires_at・scopes・calendar_id）。トークンの列は Eloquent の `encrypted` キャストで暗号化して保存（APP_KEY で暗号化される）。`#[Hidden]` で JSON に出さない |
| 呼び出しの係 | `app/Services/Google/CalendarClient.php`（google/apiclient を包む。期限切れなら refresh token で更新し、保存し直す）。受付係からは直接呼ばない |
| 失敗に強く | 書き込みはキュー（suiri-techo の `worker`）。Google が止まっていてもアプリの保存は成功させ、あとで再送。連携を解除されたら（`invalid_grant`）接続を「切れた」にして画面で知らせる |
| 解除 | 「つなぐのをやめる」で Google のトークンを取り消し（revoke）、表の行を消す |
| 公表 | プライバシーポリシーと外部送信のページに、Google に送る中身（予定の題名・日時）と受け取る中身を書く |
| テスト | GoogleAuthTest と同じく偽物を使う。CalendarClient はインターフェースにして、テストではメモリに書くだけの偽物に差し替える（`AppServiceProvider` の結びつけ。BSE の `MemoryScheduler` と同じ形） |

### 1-9. テストの構成（BSE）

- 61 件、sqlite in-memory。`tests/Feature/` 9 ファイル（機能ごと：Account・CaseStory・GoogleAuth・LessonFlow・InvestigationRules・Recitation・Pwa…）、`tests/Unit/` 2 ファイル、共通の準備は `tests/Concerns/SeedsStarterWords.php`（trait）。
- テストのメソッドの手前にも「何を確かめるテストか」を日本語で書く（`code_comments.py` が止める）。
- 決まり：`study_days.date` は文字列で扱う（sqlite と MySQL の違い）、`#[Fillable]` にない列は `forceCreate`、「risky」は閉じていない `@section` を疑う（`rules/tests.md`）。
- 大きいファイル：`LessonFlowTest.php` 347 行。新しいアプリはテストも機能ごとのフォルダに分ける（`tests/Feature/Tasks/…`）。

---

## 2. BSE の docs の構成と「ドキュメント一式の型」

### 2-1. BSE の docs の全体（読む人 × ファイル）

| 読む人 | ファイル | 中身・作り方 |
|---|---|---|
| 人間（初心者） | `docs/guide/01〜10`（解体新書、計約 6,000 行）と `docs/README.md`（目次） | 図とスクリーンショットつき。どの章も「この章でわかること」→ 節（**役割を一言でいうと → たとえると → 中身**）→「次に読む章」 |
| 人間（ブラウザで） | `docs/guide.html`（`node tools/docs-html/build.mjs`） | README・guide・設計書を1つの HTML に。画像を埋め込み、オフラインで読める。左の目次・ダークモード |
| 人間（コードを追う） | `docs/flow.html`（元は `docs/flow-src/NN-*.html` の 14 章・308 歩） | 利用者の行動（ユーザーストーリー）ごとに、`web.php` → ミドルウェア → 受付係 → 係 → 表 → 画面 → JS を本物のコードで1歩ずつ。部品は `who`（browser/server/db/js/mw）・`code`・`data`・`analogy`（たとえ）・`point`・`recap` |
| AI | `CLAUDE.md`（いつも読まれる。短い） | 道案内の表と、どの作業でも守ること |
| AI | `.claude/rules/*.md`（`paths:` に当たるファイルを開いたときだけ読まれる） | 分野ごとの決まり。`domain.md` が**言葉の対応表**（物語の言葉＝表・モデル＝画面・ルート）＋係の分担＋壊さない決まり＋入口 |
| AI | `docs/ai/codemap.md`（227 行） | 「機能 → ファイル → 関数」の索引。URL・メソッド・ルート名・受付係::メソッド・ビューの表、係の一覧、テーブル、設定、テスト、機械チェック。**行番号は書かない（シンボル名で grep）** |
| 作り直す人・AI | `docs/design/rebuild-plan.md`（設計書。目的 → 要望 → 機能要件 → 非機能要件 → 現状の分析 → あるべき形 → 手順 → 決めてほしいこと）、`docs/design/reading-method.md`（学び方の根拠。出典つき） | 状態（確定・日付）を先頭に書く。手順の表に「確かめ方」「戻し方」の列 |
| 調査 | `docs/research/`（公開前の調査レポートと図解） | 今回の調査（R1〜）もここに置ける |

解体新書の章立て（BSE）：01 鳥観図 / 02 画面と機能 / 03 仕組み（アーキテクチャ） / 04 データ / 05 レッスンとゲームの仕組み / 06 発音記号と抑揚 / 07 全ファイル案内 / 08 動かし方・よくある作業・困ったとき / 09 用語集 / 10 本番（AWS の EC2）に載せる。図は `docs/images/*.svg`（全体構成・1回のリクエストの旅・ER 図・流れ図。コードを見ながら手で描いた SVG）。

### 2-2. 品質を保っている仕組み（そのまま持っていく）

1. `bin/check docs`：Markdown のリンク切れ、`docs/images` の使っていない画像。
2. 指紋（source-fingerprint）：元の文書と画像と道具そのものから作ったハッシュを HTML に埋め、作り直し忘れを止める。
3. 住所の表の指紋：`web.php` が変わったのに流れ解説を直していなければ止める（コメントと空行は数えない）。
4. コードの抜き出しの一致：`flow-src` の `<pre><code>` の各行が、本物のファイルにそのままあるかを確かめる。
5. `rules/docs.md`：読み手は初心者・役目 → たとえ → 中身・使わないと決めた言葉を使わない（決まりのファイルを指して書き、言葉そのものは書かない）・運営者の体験や気持ちを作って書かない・コードの決まりを変えたら codemap と rules も直す。

### 2-3. 新しいアプリの「ドキュメント一式の型」（提案）

オーナーが欲しいと言っている「言葉のルール」「設計書」「部品説明書」を、BSE の型に当てはめる。

```
CLAUDE.md                     AI がいつも読む（短く。道案内の表＋どの作業でも守ること）
AGENTS.md -> CLAUDE.md
.claude/rules/
  glossary.md                 【言葉のルール（AI 用）】画面の言葉＝表・モデル・ルート・ビューの名前の対応表と、使わない言葉
  architecture.md             太らない MVC の決まり（5 章）
  google-calendar.md / deploy.md / docs.md / tests.md / code-comments.md / ui-design.md / accounts-legal.md
docs/
  README.md                   目次（だれ向けに何があるか・読む順番・図の一覧）
  guide/                      【解体新書（人間用）】
    01-birdseye.md            鳥観図：一言で・だれの何を解決するか・ホームズの見方と機能の対応
    02-screens.md             画面と機能（スクリーンショット・ボタンを押すと何が起きるか）
    03-architecture.md        仕組み：Docker の箱・MVC の層・1回のリクエストの旅・全 URL・守りの仕掛け
    04-data.md                データ：表と関係（ER 図）・だれが書くか・消さない記録
    05-google.md              Google ログインとカレンダー連携（同意・トークン・同期・切れたとき）
    06-parts.md               【部品説明書】Blade コンポーネント・Action・Service・FormRequest・Policy の1つずつ
    07-file-guide.md          全ファイル案内（1ファイル1行の役割）
    08-operations.md          動かし方・決まりのチェック・困ったとき
    09-glossary.md            用語集（技術の言葉。言葉のルールとは別）
    10-deploy-aws.md          本番（講座の手順どおり。3 章）
  words.md                    【言葉のルール（人間用）】画面の言葉・コードの名前・意味・使わない言い方
  design/
    requirements.md           【設計書①】目的・要望・機能要件・非機能要件・決めてほしいこと
    architecture.md           【設計書②】層・ディレクトリ・名前の付け方・行数の上限と理由
    data-model.md             【設計書③】表の設計（列・型・索引・消し方）
    calendar-sync.md          【設計書④】カレンダー連携の設計（スコープ・同期の向き・失敗のとき）
  ai/codemap.md               機能 → ファイル → 関数（AI 用索引）
  flow-src/NN-*.html → flow.html   プログラムの流れ解説（利用者の行動ごと）
  guide.html                  解体新書と設計書を1つにした HTML
  images/                     SVG の図・スクリーンショット
  research/                   今回の調査（R1〜）
```

「言葉のルール」は2つに分ける：AI が読む `.claude/rules/glossary.md`（`paths:` で `src/app/**`・`src/routes/**`・`src/resources/views/**`・`src/database/migrations/**` を開いたときに読まれる）と、人間が読む `docs/words.md`。中身は同じ表（画面の言葉／表・モデル／ルート・ビュー／意味／使わない言い方）で、**片方を直したらもう片方も直す**を `rules/docs.md` に書く。BSE の `domain.md` の表がそのまま手本になる。

「部品説明書」（06-parts.md）は BSE にない章。BSE では 03 の「受付係の一覧」「係の一覧」と 07 の全ファイル案内に分かれている。新しいアプリでは部品の数が増える（FormRequest・Policy・Action・コンポーネント）ので、1つの部品につき「役目・使う場所・受け取るもの（props・引数）・返すもの・例・テスト」を同じ形で並べる章を立てる。Blade コンポーネントは `@props` の一覧をそのまま表にできる。

設計書は BSE の `rebuild-plan.md` の章立て（目的 → 要望 → 機能要件 → 非機能要件 → 現状 → あるべき形 → 手順 → 決めてほしいこと）を新しく作る場合に合わせて「現状の分析」を「先行調査（`docs/research/` へのリンク）」に置き換える。手順の表に「確かめ方」「戻し方」の列を必ず付ける。

---

## 3. デプロイ手順：講座 ↔ pixel-office ↔ BSE と、新しいアプリの手順書の章立て

元にしたもの：受講している AWS 講座の手順（4回分。以下 T1〜T4。会員向け教材なので、ここでは要点を自分の言葉で書き、文章は写していない）、`pixel-office/docs/DEPLOY_AWS.md`（以下 PO）、BSE の `docs/guide/10-deploy-ec2.md`（以下 BSE-G）と `.claude/rules/deploy.md`。（行番号つきのノートは講座の教材の写しを含むため、このリポジトリには入れていない）

### 3-1. 講座のステップ ↔ pixel-office ↔ BSE（設定値の要点つき）

講座の形：ブラウザ → Route 53 → CloudFront（https）→ http:80 → EC2（Elastic IP）→ Docker（web・app・db）。講座の中の名前は `memo-*`、新しいアプリでは `<app>-prod-*`。

| # | 講座：作るもの・設定値の要点（出典） | pixel-office DEPLOY_AWS.md | BSE 10-deploy-ec2.md |
|---|---|---|---|
| 0 | 準備：ドメインと Route 53 のホストゾーン、IAM ユーザー、`gh`。**Budgets 月 30 USD・80% でメール**。リージョンは東京（T1 L.100-152） | §0 全体像・名前の表（Budgets なし） | 10-1〜10-3・名前の表（Budgets なし） |
| 1 | VPC：「VPC のみ」、**`10.0.0.0/20`**、IPv6 なし（T1 L.153-188） | §1（講座と同じ） | A（「VPC など」で一度に。DNS ホスト名オン） |
| 2 | サブネット×4：public **`10.0.0.0/24`（1a）・`10.0.1.0/24`（1c）**、private **`10.0.8.0/24`・`10.0.9.0/24`**。パブリックだけ IPv4 自動割り当てオン（T1 L.189-240） | §1（同じ4つ） | A（AZ 1つ・パブリック1つ） |
| 3 | IGW をアタッチ、public 用ルートテーブルに `0.0.0.0/0 → IGW`、パブリックだけ関連付け（T1 L.241-289） | §1 | A |
| 4 | キーペア：**ED25519・.pem**、`chmod 400`（T2 L.51-98） | §3（chmod 600） | C |
| 5 | SG：**22＝マイ IP、80＝0.0.0.0/0**、443 は開けない（CloudFront → EC2 は http）。プレフィックスリストは講座に記載なし（T2 L.100-138） | §2（80＝**`com.amazonaws.global.cloudfront.origin-facing`**、TURN 用ポート） | B（80＝プレフィックスリスト） |
| 6 | EC2：**AL2023（Arm）・t4g.small**（1GB だと composer が止まることがある。x86 なら t3.small）、public-1a、**20 GiB gp3**、2/2 まで待つ（T2 L.140-202） | §3（t4g.small・8 GiB） | D（t3.small・20 GiB gp3） |
| 7 | Elastic IP を関連付け。片付けで解放（T2 L.204-228） | §4 | E |
| 8 | SSH、`dnf` で git・docker、docker グループ、compose と **buildx を新しく**（T2 L.230-358） | §5（版を固定） | G（**スワップ 2GB**） |
| 9 | GitHub：`.env` が入っていないか確かめて push、EC2 で ED25519 の**読むだけのデプロイキー**、`git@github.com:` で clone（T3 L.51-136） | §6 冒頭 | H |
| 10 | 本番 .env 2枚（直下：DB の4つ・`WEB_LISTEN=80`／`src/.env`：`APP_ENV=production`・`APP_DEBUG=false`・`APP_URL=https://app.<ドメイン>`）。パスワードマネージャに控える。`up -d --build` → `composer install --no-dev` → `key:generate` → 書き込み権限 → `migrate --force`（T3 L.138-271） | §6（`chmod 600 .env`・ORIGIN_SECRET・ヘルスチェック） | 10-4（`LOG_*`・`SESSION_SECURE_COOKIE=true`・`npm ci && npm run build`・`curl -i http://localhost/up`） |
| 11 | ACM：**us-east-1** で（CloudFront が選べるのはここの証明書だけ）、`app.<ドメイン>`、**DNS 検証**（T4 L.50-71） | §8 に1行 | **なし**（`*.cloudfront.net` のまま） |
| 12 | `origin.<ドメイン>` の **A レコード（エイリアスなし・EIP・TTL 300）**をオリジンに。**HTTP only:80／Redirect HTTP to HTTPS／7メソッド／CachingDisabled／AllViewer**（Cookie が届かないと 419）、代替ドメイン名と証明書。`app` の **A エイリアス → CloudFront**（T4 L.73-149） | §8・§9 | F・10-9（Route 53 なし） |
| 13 | `forceScheme('https')`（本番だけ）と `trustProxies(at: '*')`。役目が違うので両方（T4 L.150-245） | TRUST_PROXY・ORIGIN_SECRET | 10-2・rules/deploy.md |
| 後 | 運用（`.env` を変えたら `config:cache`）・トラブル・**片付け**（CloudFront 無効 → 削除 → Route 53 → EC2 → EIP 解放 → VPC）・19 項目のチェックリスト（T4 L.291-364） | §10 更新・§12 トラブル・§11 バックアップ（DLM 毎日 7日分）。片付けなし | 10-5 更新・10-10 困ったとき・10-6 SES・10-7 Google。片付けは1行 |

講座から外れている・足している点で、**新しいアプリが採るもの**：

1. **80 番は CloudFront のプレフィックスリストに絞る**（PO・BSE）。`trustProxies(at: '*')` の前提が「CloudFront からしか届かない」なので必須。代わりに講座の「`http://<EIP>` をブラウザで開いて確かめる」はできなくなるので、確かめは EC2 の上で `curl -i http://localhost/up`、最後に手元から `curl -m 5 http://<EIP>/` が時間切れになることを見る（BSE-G）。
2. **オリジンは講座どおり `origin.<ドメイン>` の A レコード**。PO・BSE は EC2 のパブリック DNS を使うが、それには VPC の「DNS ホスト名」がオンでなければならず、講座の「VPC のみ」で作るとオフのことがある。なお PO は構成表と §8 でオリジンの書き方が食い違っている。
3. **オリジンリクエストポリシーは AllViewer**（講座・BSE）。PO の §8 は AllViewerExceptHostHeader で、構成表とも食い違う。Laravel は Host から URL を作るので AllViewer にする（1-6 の 4）。
4. 足すもの：HSTS（アプリ側）、スワップ 2GB、`restart: unless-stopped`、ログの上限、`SESSION_SECURE_COOKIE=true`・`LOG_LEVEL=error`、バックアップ（DLM＋大きな更新の前の `mysqldump`）、更新の手順（`git pull --ff-only` → composer → migrate → `npm ci && npm run build` → `optimize:clear`）、余裕があれば CloudFront の秘密のヘッダー（PO の ORIGIN_SECRET）。
5. 講座にあって PO・BSE にない **Budgets と片付けの手順**は、新しい手順書に必ず入れる。

### 3-2. 新しいアプリのデプロイ手順書の章立て（提案：`docs/guide/10-deploy-aws.md`）

各章は BSE の形（**役目を一言 → たとえ → 手順 → 確かめ方**）。値は 3-1 の講座の値を使い、**講座から変える所だけ理由を書く**。名前の表は 0 章に1回だけ出し、ウィザードが付けた長い名前を `<app>-prod-<種類>` に直す手順を入れる（BSE のコミット `dbf7475` と同じ）。

| 章 | 講座のまま | 足す・変える所 |
|---|---|---|
| 0 始める前に | 前提・料金・Budgets | 名前の表（`<app>-prod-vpc`・`-subnet-public-1a`…・`-igw`・`-rtb-public`・`-sg`・`-key`・`-ec2`・`-eip`・`-cf`） |
| 1〜3 VPC・サブネット・IGW とルート | CIDR・4サブネット・ルート | — |
| 4 キーペア | ED25519・.pem | — |
| 5 セキュリティグループ | 22＝マイ IP、443 なし | **80＝CloudFront のプレフィックスリスト**（理由：trustProxies の前提。直接のアクセスを断つ） |
| 6〜7 EC2・Elastic IP | AL2023 Arm・t4g.small・20 GiB gp3 | — |
| 8 SSH と Docker | git・docker・compose・buildx | **スワップ 2GB** |
| 9 GitHub とデプロイキー | 読むだけの鍵 | `~/.ssh/config` |
| 10 本番の .env と起動 | 2枚の .env・起動の順 | `chmod 600`、`LOG_LEVEL=error`・`LOG_STACK=daily`・`SESSION_SECURE_COOKIE=true`・`GOOGLE_*`（`GOOGLE_REDIRECT_URI` は https の絶対 URL）、`npm ci && npm run build`、`config:cache`、**worker の箱が動いているか**。確かめは EC2 の上で `curl -i http://localhost/up` |
| 11 ACM | us-east-1・DNS 検証 | — |
| 12 CloudFront と Route 53 | origin レコード・7メソッド・CachingDisabled・AllViewer・A エイリアス | （任意）オリジンのカスタムヘッダー（秘密の値）を付け、nginx で確かめる |
| 13 プロキシの後ろ | forceScheme・trustProxies | HSTS（アプリに入れてあるので確かめるだけ） |
| 14 Google の本番設定 | （講座になし） | 本番用 OAuth クライアント（Web application）、承認済みのリダイレクト URI `https://app.<ドメイン>/auth/google/callback`、**Calendar API を有効にする**、同意画面（External・ホームページ・`/privacy`・`/terms`・承認済みドメイン）、スコープ、公開状態（4 章：テストのままだと refresh token が 7 日で切れる） |
| 15 公開前の確認 | チェックリスト | 鍵マーク、http → https、ログイン、カレンダーの取り込み、手元から `curl -m 5 http://<EIP>/` が時間切れ |
| 16 更新の手順 | `git pull` | `--ff-only` → composer → migrate → build → `optimize:clear` → `php artisan queue:restart`。`migrate:fresh` は禁止 |
| 17 バックアップ | （講座になし） | DLM（毎日・7日分）＋大きな更新の前の `mysqldump --single-transaction`、戻し方 |
| 18 困ったとき | 講座の表 | 419（AllViewer）・`redirect_uri_mismatch`（https・Host・末尾の `/`）・トークン切れ・ディスクがいっぱい |
| 19 片付け | 講座の順番 | Google の OAuth クライアントも消す |

---

## 4. Laravel 13 と周りの版（2026-10-31 に Web で確認）

公式ドキュメント・packagist・GitHub のソース・php.net で確かめた。くわしい出典は `research/R6-web-notes.md`。

| もの | 確かめたこと | 出典 |
|---|---|---|
| Laravel 13 | 2026-03-17 リリース、最新は **v13.39.0（2026-10-27）**。composer の要件は **`php: ^8.3`**（対応 8.3〜8.5）。バグ修正は 2027 年第3四半期まで、セキュリティ修正は **2028-03-17** まで | laravel.com/docs/13.x/releases、packagist.org/packages/laravel/framework |
| 13 の主な変更 | 破壊的変更は少ない。**PHP の属性が増えた**（コントローラーに `#[Middleware('auth')]`・`#[Authorize(...)]`、ジョブに `#[Tries]`・`#[Backoff]`・`#[Timeout]`、Eloquent にも）。CSRF のミドルウェアが **`PreventRequestForgery`** に改名され `Sec-Fetch-Site` の確かめが加わった。`Queue::route()`。セッションの保存形式が `json`、`config/cache.php` の `serializable_classes => false`（オブジェクトをキャッシュに入れない） | docs/13.x/releases、docs/13.x/upgrade |
| 新しい構成 | 11 以降と同じ。Kernel はなく、ミドルウェア（`append()`・`web(append: [...])`・`alias()`）・`trustProxies(at: '*', headers: ...)`・例外は `bootstrap/app.php`。スケジュールは `routes/console.php`。config は 10 ファイル（ほかは `config:publish`） | docs/13.x/middleware、requests#configuring-trusted-proxies、structure、github.com/laravel/laravel/blob/13.x/bootstrap/app.php |
| ルートの分け方 | docs にあるのは `withRouting(then: fn () => Route::middleware('web')->group(base_path('routes/…')))`。`web:` に配列を渡す方法はソースでは動くが docs になく、**ないファイルを黙って読み飛ばす**。`web.php` から `require` するのはふつうの PHP（ないとすぐエラーになる） | docs/13.x/routing#routing-customization、framework の ApplicationBuilder.php |
| Socialite | 最新 **v5.31.0（2026-08-31）**、Laravel 13 対応。`->scopes([...])` は既定（openid・profile・email）に**足す**。`->with(['access_type' => 'offline', 'prompt' => 'consent'])` はそのまま Google の認可 URL に渡る（ソースで確認）。戻り値に `token`・`refreshToken`・`expiresIn`・`approvedScopes`。`refreshToken($rt)` で更新（Google は新しい refresh token が来なければ元のものを引き継ぐ）。テスト用に `Socialite::fake()` | packagist、docs/13.x/socialite、socialite v5.31.0 の GoogleProvider.php |
| google/apiclient | 最新 **v2.20.1（2026-09-25）**、要件 `php: ^8.1`。**メンテナンスモード**（新機能は足さない）。Calendar だけ残すには `"scripts": {"pre-autoload-dump": "Google\\Task\\Composer::cleanup"}` と `"extra": {"google/apiclient-services": ["Calendar"]}`。PHP の Calendar クイックスタートは公式から消えている | github.com/googleapis/google-api-php-client（README・releases） |
| カレンダーのスコープ | 読むだけなら `calendar.events.readonly`（全カレンダーの予定を読む）か `calendar.readonly`。アプリ専用のカレンダーを作って書くだけなら **`calendar.app.created` 1つ**で足りる。Calendar のスコープは restricted ではなく sensitive 扱い（ただし**スコープごとの区分は未確認**。Cloud Console で追加したときの印で確かめる） | developers.google.com/workspace/calendar/api/auth、support.google.com/cloud/answer/13464325 |
| 同意画面の公開状態 | **Testing**：テストユーザー最大 100 人、**許可と refresh token が 7 日で切れる**（name・email・profile だけなら別）。本番（In production）で sensitive のスコープを使うには Google の確認が要る（同じドメインのプライバシーポリシー・ホームページ・Search Console での所有確認・デモ動画。最大 10 日）。refresh token は 6 か月使わないと切れる | support.google.com/cloud/answer/15549945、developers.google.com/identity/protocols/oauth2、…/sensitive-scope-verification |
| PHP | 8.4：アクティブサポートは **2026-12-31** まで、セキュリティは 2028-12-31 まで、最新 8.4.27。8.5：2025-11-20 リリース、セキュリティは 2029-12-31 まで、最新 8.5.12。公式 Docker の `php:8.4-fpm`・`php:8.5-fpm` はどちらもあり、2026-10-28 に更新ずみ | php.net/supported-versions.php、hub.docker.com/_/php |
| PHP の重大な脆弱性 | 2026-10-21：**CVE-2026-109618**（multipart の解析中のメモリ不足を使う RCE）と **CVE-2026-109619**（前のリクエストのセッションなどの状態が残る RCE）。修正版は **8.5.12 / 8.4.27 / 8.3.36 / 8.2.35**。すぐ上げられないときの緩和策として `post_max_size` を小さく（アップロードなしなら 8M）・multipart の制限・`session.upload_progress.enabled=0`（ini_set や .user.ini では効かない）。10-23 の追加告知（CVE-2026-115560 として公表予定）でも `session.upload_progress.enabled=0` を勧めている。**BSE の対策（1-7）はこの勧告どおり**。なお「php2shell」という呼び名は php.net と GitHub の告知には出てこない（同じものかは**未確認**） | php.net（2026-10-21・10-23 の告知）、github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w、GHSA-6wmf-4rv5-p3rp |
| MySQL 8.4 LTS | 方針は Premier 5 年＋Extended 3 年。8.4.0 GA が 2024-04-30 なので Premier 2029-04、Extended 2032-04（日付は endoflife.date。Oracle の文書では**未確認**）。次の LTS は 9.7 | dev.mysql.com/doc/refman/8.4/en/mysql-releases.html、endoflife.date/mysql |

新しいアプリへの当てはめ：

- **PHP は修正版以上のイメージにする**。雛形の `FROM php:8.4-fpm-bookworm` は版を固定していないので、`docker compose build --pull app` で 8.4.27 以上になる。確かめは `docker compose exec -T app php -v`。版を固定するならパッチ版のタグ（`php:8.4.27-fpm-bookworm` など）。**8.4 のアクティブサポートは 2 か月後に終わる**ので、新しく作るなら 8.5（Laravel 13 は対応）も選べる。BSE と同じ手順で運用するなら 8.4 のままでもセキュリティ修正は 2028 年末まで続く（8.5 でテストの道具が動くかは雛形で `php artisan test` を回して確かめる。**未確認**）。
- **ルートの分割は `web.php` から `require`**（ないファイルで黙らない・読む人がたどれる）。`then:` でもよい。`web:` の配列は使わない。
- **権限は Laravel 13 の `#[Authorize]` 属性も候補**（コントローラーのメソッドの上に Policy の名前を書く形）。最初の機能（6-2 の 7）で動きを確かめてから決まりにする。
- **カレンダーのスコープは R7 の MVP（指定した1つのカレンダーの予定を取り込む）なら `calendar.events.readonly`**。どのカレンダーかを一覧から選ばせるなら、一覧を読むスコープ（`calendar.calendarlist.readonly` など）も要る見込み（**未確認**：calendarList.list の必要スコープを公式リファレンスで確かめる）。アプリから書き込むようになったら、利用者の予定を書き換えずに済む `calendar.app.created`（アプリ専用のカレンダー）を足す。
- **7 日問題の扱いを先に決める**：家族など数人だけで使うなら Testing のまま（テストユーザーに入れる）でも動くが、7 日ごとにつなぎ直しになる。画面で「つながりが切れました → つなぎ直す」を出す作り（`invalid_grant` を受けたら接続を「切れた」にする）は、どちらにしても要る。
- google/apiclient はメンテナンスモードなので、`CalendarClient` の中だけで使い、外にはインターフェースしか見せない（将来、HTTP で直接呼ぶ形に替えられる）。

---

## 5. 太らない MVC の決まり（提案）

### 5-1. BSE の実測（`wc -l`、コメント込みの行数）

BSE はクラス・関数・分岐の手前に必ずコメントを書くので、行数はコメント込みで数えている（新しいアプリも同じ決まりにするので、上限もコメント込みで決める）。

| 種類 | ファイル数 | 真ん中の値 | いちばん大きい | 150 行超 | 200 行超 | 300 行超 |
|---|---|---|---|---|---|---|
| コントローラー | 15 | 61 | `InvestigationController.php` 203（public 7） | 2 | 1 | 0 |
| サービス（係） | 16 | 100.5 | `Investigation/QuestionBuilder.php` 290 | 4 | 1 | 0 |
| モデル | 12 | 34.5 | `User.php` 183 | 1 | 0 | 0 |
| コマンド | 5 | 64 | `LoadSentenceNotes.php` 190 | 2 | 0 | 0 |
| Blade | 52 | 35.5 | `pages/privacy.blade.php` 292、`casebook.blade.php` 292 | 6 | 3 | 0 |
| JS | 14 | 52 | `voice.js` 981、`parts/investigation.js` 344 | 3 | 2 | 2 |
| テスト | 13 | 73 | `LessonFlowTest.php` 347 | 3 | 2 | 1 |
| CSS | 1 | — | `app.css` 505 | — | — | — |

読み取れること：PHP は「係に分ける」決まりのおかげで真ん中が 100 行以下に収まっている。太ったのは **JS（1ファイルに機能を足し続けた `voice.js`）・ホームの Blade・問題を作る係**。決まりを書いただけでは止まらず、機械で止めていなかった所が太った。BSE の `bin/check` には行数の検査がない。

### 5-2. 層と置き場所

```
ブラウザ → routes/web/{機能}.php → ミドルウェア → Controller（受付）
              ├─ FormRequest（形の検証・入力をそろえる）
              ├─ Policy（この人がしてよいか）
              ├─ Action（1つの用事：作る・直す・終える・つなぐ）── Model / DB
              │      └─ Service（外とのやりとり：Google カレンダー）
              └─ view() → Blade の画面 → Blade コンポーネント（表示の部品）
```

| 層 | 置き場所 | 受け持つこと | してはいけないこと |
|---|---|---|---|
| ルート | `routes/web.php` は読み込みだけ、中身は `routes/web/{機能}.php` | 住所・ルート名・ミドルウェア・`->can()` | クロージャで処理を書く |
| 受付（Controller） | `app/Http/Controllers/{機能}/` | 受け取る → Action を呼ぶ → 画面か転送を返す | DB の問い合わせを組み立てる、`validate()`、外の API を呼ぶ |
| 形の検証 | `app/Http/Requests/{機能}/` | `rules()`・`messages()`・`attributes()`・`prepareForValidation()` | DB に書く |
| 権限 | `app/Policies/` | 持ち主か・してよいか | 検証・書き込み |
| 用事（Action） | `app/Actions/{機能}/` | 1クラス＝1つの用事、`handle()` 1つ。トランザクション・記録の書き込み | 画面の都合（HTML・リダイレクト）を知る |
| 外とのやりとり（Service） | `app/Services/{相手}/` | Google カレンダーなど外の API を包む。差し替えられるようインターフェースを置く | 画面・リクエストを知る |
| 読み出しのまとめ（任意） | `app/Queries/` または ViewModel | 画面に出す一覧・集計を組み立てる（受付が太るときだけ） | 書き込み |
| モデル | `app/Models/` | 関係・キャスト・スコープ・小さな判定（`isDone()` など） | 複数の表にまたがる処理 |
| 画面 | `resources/views/{機能}/{動作}.blade.php` | 並べるだけ | `@php` で計算する、DB に触る |
| 表示の部品 | `resources/views/components/{分類}/` | 名前つきの部品（`<x-ui.button>`・`<x-task.card>`）。props を `@props` で宣言 | データを取りに行く |
| 動き | `resources/js/app.js`（読み込むだけ）＋`resources/js/parts/{機能}.js` | 1ファイル＝1つの部品の動き | 1ファイルに機能を足し続ける |
| 設定値・文言 | `config/{アプリ名}.php` | 数字・文言・選択肢 | コードに直書き |

BSE との違い：BSE は「係（Service）」に用事も外部も入れている（`Services/Investigation/InvestigationStarter` など）。新しいアプリは機能が CRUD 中心のタスク管理なので、「1つの用事＝1つの Action」に分けると、1ファイルが小さく、名前で中身が分かり、テストも1対1になる。外の API（Google）だけを Service と呼び分ける。

### 5-3. 各層の決まり

**ルート**
- `routes/web.php` は機能ごとのファイルを読み込むだけにする（例：`require __DIR__.'/web/auth.php'; require __DIR__.'/web/tasks.php'; …`）。ないファイルはすぐエラーになり、読む人も web.php からたどれる。`withRouting()` の `web:` に配列を渡す書き方はソースでは動くが docs になく、ないファイルを黙って読み飛ばすので使わない（4 章）。
- CRUD は `Route::resource('tasks', TaskController::class)->only([...])`。CRUD に当てはまらない動き（「カレンダーと同期する」「事件を閉じる」）は1アクションのコントローラー（`__invoke`）にして `Route::post('/tasks/{task}/close', CloseTaskController::class)`。
- 回数制限は名前つきだけ（`throttle:auth`）。数字の `throttle:10,1` は `bin/check` で止める（BSE と同じ）。
- 権限の書き場所は1通りに決める。候補は、Laravel 13 で入ったコントローラーのメソッドの属性 `#[Authorize('update', 'task')]`、ルートの `->can('update', 'task')`、受付の最初の1行 `Gate::authorize('update', $task)`。Laravel 11 以降の雛形の親コントローラーは空（BSE の `Controller.php` も空）で、`$this->authorize()` は使えない。最初の機能で `#[Authorize]` を試し、動けばそれを決まりにする（4 章）。

**受付（Controller）**
- リソースコントローラーの7つ（index・create・store・show・edit・update・destroy）まで。それより増えたら別のコントローラーに分ける。
- 1アクションは目安 10 行以内：FormRequest を受け取る → Action を呼ぶ → `redirect()->route(...)->with('status', …)` か `view(...)`。
- 例：
  ```php
  /** タスクを作る（POST /tasks）。形の検証は StoreTaskRequest、作るのは CreateTask に任せる */
  public function store(StoreTaskRequest $request, CreateTask $createTask): RedirectResponse
  {
      $task = $createTask->handle($request->user(), $request->validated());

      return redirect()->route('tasks.show', $task)->with('status', 'タスクを作りました。');
  }
  ```

**FormRequest**
- 1つの送信に1つ（`StoreTaskRequest`・`UpdateTaskRequest`）。日本語のメッセージと項目名は `lang/ja/validation.php` に集める（各 FormRequest に書かない）。
- 選択肢は config から作る（`Rule::in(array_keys(config('app-name.kinds')))`）。

**Policy**
- 持ち主だけが見られる・直せるを1か所に（`TaskPolicy::view/update/delete`）。oshi-meter のように各メソッドで `authorizeOwner()` を書かない。
- 「他人の記録が見えない」テストを機能ごとに1つ以上書く。

**Action**
- `app/Actions/{機能}/{動詞}{名詞}.php`、public メソッドは `handle()` 1つ。複数の表に書くときは `DB::transaction()`。
- 受付からはメソッドの引数で受け取る（Laravel が自動で作って渡す）。Action から Action を呼んでよいが、2段まで。
- 外の API は Action の中で Service（インターフェース）を通して呼ぶ。キューに回すなら Action が Job を出す。

**Blade とコンポーネント**
- 画面は `resources/views/{機能}/{index|show|create|edit}.blade.php`。作成と編集のフォームは共通の部品 `<x-task.form :task="$task" />`（oshi-meter の `_form.blade.php` と同じ考えを、コンポーネントにする）。
- 部品は無名コンポーネント（`resources/views/components/`、`@props` で受け取るものを宣言）を基本にし、計算が要るときだけクラスのコンポーネント（`app/View/Components/`）。BSE の `@include('partials.…')` は、受け取るものが見えないので使わない。
- `@php` のかたまりで計算しない（受付か Query で組み立てて渡す）。BSE の `ui-design.md` にもある「`@json` に複数行の配列を直接書かない」を引き継ぐ。

**JS**
- `app.js` は読み込むだけ、動きは `parts/{機能}.js`（BSE と同じ）。1ファイル 200 行を超えたら部品に分ける（`voice.js` 981 行の二の舞にしない）。

### 5-4. 行数の上限と、`bin/check` で止める方法

| 対象（パスの正規表現） | 上限（コメント込み） | 理由 |
|---|---|---|
| `src/app/Http/Controllers/**` | 150 行 | 7 アクション×（コメント5行＋本体10行）＋α。BSE で超えるのは 1 つ |
| `src/app/**/*.php`（そのほかのクラス） | 200 行 | BSE で超えるのは `QuestionBuilder` 1 つ |
| `src/routes/**/*.php` | 120 行 | BSE の `web.php` 116 行が全機能。機能ごとに分ければ各 30〜60 行 |
| `src/database/migrations/**` | 150 行 | 1 移行＝1つの変更 |
| `src/resources/views/**/*.blade.php` | 150 行 | BSE で超えるのは 6 つ（法律のページ2つ・ホーム・報告書・手帳・単語カード） |
| `src/resources/js/**/*.js` | 200 行 | BSE で超えるのは `voice.js`・`investigation.js` |
| `src/resources/css/**/*.css` | 400 行 | `app.css` を `@import` で分ける（Tailwind v4 は CSS の `@import` が使える） |
| `src/tests/**/*.php` | 300 行 | テストも機能ごとに分ける |
| `src/config/{アプリ}.php` | 300 行 | 文言が多ければファイルを分ける |
| 関数・メソッド 1 つ | 40 行（警告） | 正規表現では数えにくいので、最初は警告だけ |

例外は BSE の `ALLOW` と同じく「ファイル・理由つき」で表に書く（例：プライバシーポリシーの Blade は文章が長いので 400 行まで）。

`bin/check` に足すコード（骨組み。BSE の `check_rules` と同じ形で、理由と直し方を返す）：

```python
# 1ファイルの行数の上限（コメント込み）。上から順に当てはめ、最初に合ったものを使う
SIZE_LIMITS = [
    (r'^src/app/Http/Controllers/', 150, '受付は薄く：検証は FormRequest、処理は Action へ。7 アクションを超えたらコントローラーを分ける'),
    (r'^src/app/.*\.php$', 200, '1 クラス＝1 つの役目に分ける（用事は app/Actions、外の API は app/Services）'),
    (r'^src/routes/.*\.php$', 120, 'routes/web/{機能}.php に分ける'),
    (r'^src/database/migrations/', 150, '1 つの移行に 1 つの変更'),
    (r'^src/resources/views/.*\.blade\.php$', 150, '繰り返す塊を resources/views/components/ の部品にする'),
    (r'^src/resources/js/.*\.js$', 200, 'resources/js/parts/ に部品ごとに分ける'),
    (r'^src/resources/css/.*\.css$', 400, '@import で分ける'),
    (r'^src/tests/.*\.php$', 300, 'tests/Feature/{機能}/ に分ける'),
]
SIZE_ALLOW = {  # (ファイル): (上限, 理由)
    'src/resources/views/pages/privacy.blade.php': (400, '文章のページ'),
}

def check_size(files):
    problems = []
    for f in files:
        p = Path(f)
        if not p.is_file():
            continue
        for path_re, limit, fix in SIZE_LIMITS:
            if re.search(path_re, f):
                limit = SIZE_ALLOW.get(f, (limit, ''))[0]
                n = len(p.read_text(encoding='utf-8', errors='replace').split('\n'))
                if n > limit:
                    problems.append(f'{f}  【ファイルが長すぎる：{n} 行（上限 {limit}）】\n    → {fix}')
                break
    return problems
```

`main()` の rules・staged・worktree のそれぞれで `check_rules(files) + check_size(files) + code_comments.check(files)` にする。`after-edit` フックは `bin/check rules <file>` を呼ぶので、**AI がファイルを直した直後に**上限を超えたことが伝わり、その場で分けさせられる。

### 5-5. 名前の付け方

| もの | 形 | 例 |
|---|---|---|
| モデル | 単数・英語・画面の言葉と同じ意味 | `Task`・`Observation`（仮） |
| 表 | 複数・スネークケース | `tasks`・`calendar_connections` |
| リソースコントローラー | `{モデル}Controller` | `TaskController` |
| 1アクションのコントローラー | `{動詞}{名詞}Controller`（`__invoke`） | `CloseTaskController`・`SyncCalendarController` |
| FormRequest | `{Store\|Update}{モデル}Request` | `StoreTaskRequest` |
| Policy | `{モデル}Policy` | `TaskPolicy` |
| Action | `{動詞}{名詞}`（`handle()`） | `CreateTask`・`CompleteTask`・`ConnectCalendar` |
| Service | `{相手}{役目}`＋インターフェース | `Google\CalendarClient`（契約は `Contracts\Calendar`） |
| Job | `{動詞}{名詞}Job` | `PushTaskToCalendarJob` |
| ルート名 | `{表}.{動作}` | `tasks.index`・`tasks.close`・`calendar.connect` |
| ビュー | `{表}/{動作}.blade.php` | `tasks/index.blade.php` |
| コンポーネント | `components/{分類}/{名前}.blade.php` → `<x-分類.名前>` | `<x-task.card>`・`<x-ui.button>` |
| JS | `parts/{機能}.js`（ケバブケース） | `parts/task-board.js` |
| テスト | `tests/Feature/{機能}/{動作}Test.php` | `tests/Feature/Tasks/CreateTaskTest.php` |
| config | `config/{アプリ名}.php` に集める | `config('suiri.kinds')`（仮） |

画面の言葉（日本語・物語の言葉）とコードの名前の対応は、言葉のルール（`rules/glossary.md`・`docs/words.md`）の表に必ず載せてから使う（BSE の「物語の言葉＝データの単位＝コードの名前」）。

### 5-6. 機械で止める決まり（`bin/check` の `RULES` に足す案）

| 名前 | 対象 | 探す正規表現（案） | 直し方 |
|---|---|---|---|
| コントローラーで検証しない | `^src/app/Http/Controllers/` | `->validate\(\|Validator::make` | FormRequest にする |
| コントローラーで DB を組み立てない | `^src/app/Http/Controllers/` | `DB::\|->where\(\|::query\(\)` | Action か Query に移す |
| ルートにクロージャを書かない | `^src/routes/web` | `Route::\w+\([^)]*function\s*\(` | コントローラーにする |
| 名前なしの回数制限を書かない | `^src/routes/` | `throttle:\d` | `RateLimiter::for` で名前を付ける（BSE から） |
| `env()` は config の中だけ | `^src/(app\|routes\|resources)/` | `\benv\(` | config に書いて `config()` で読む（`config:cache` すると env() は null になる） |
| Blade で `@php` のかたまりを書かない | `^src/resources/views/` | `^\s*@php\s*$` | 受付で組み立てて渡す |
| 部品は `@include` でなくコンポーネント | `^src/resources/views/` | `@include\(` | `<x-…>` にする（レイアウトの `@extends`・`@yield` は可） |
| 外部の CDN を読まない | `^src/resources/(views\|css\|js)/` | BSE と同じ | npm で入れて Vite でビルド |
| 使わないと決めた言葉を書かない | `^(docs/\|README\.md\|src/resources/)` | BSE と同じ | 別の言い方にする |
| トークンを平文で保存しない | `^src/app/Models/` | （`refresh_token` があるのに `encrypted` キャストがない）※1行の正規表現では書けないので、テストで確かめる | `casts()` に `'refresh_token' => 'encrypted'` |

---

## 6. アプリへの示唆

### 6-1. 最初に作るファイル（ディレクトリ構成の案）

雛形 `04.product/suiri-techo` に足していく形で書く（名前は仮。機能名は R7 の案「タスク・カレンダーの予定・記録カード」に合わせた仮の英語名）。`←BSE` は BSE から写して直すもの。

```
suiri-techo/
├─ CLAUDE.md                     ←BSE の型（1-3）。src/CLAUDE.md・src/AGENTS.md（Laravel Boost の案内）は消す
├─ AGENTS.md -> CLAUDE.md
├─ README.md
├─ docker-compose.yml            雛形のまま（web・app・worker・db、10.232.0.0/24）
├─ .env.example  .gitignore      雛形のまま
├─ .githooks/pre-commit          ←BSE（そのまま）
├─ .claude/
│  ├─ settings.json              ←BSE（そのまま）
│  └─ rules/  glossary.md（言葉のルール）  architecture.md（5 章）  google-calendar.md  accounts-legal.md  ui-design.md
│             code-comments.md  tests.md  deploy.md  docs.md   ←BSE
├─ bin/
│  ├─ check                      ←BSE（RULES の入れ替え＋check_size を足す）
│  ├─ code_comments.py           ←BSE（そのまま）
│  └─ hooks/ guard-bash  after-edit  before-stop   ←BSE（guard-bash は本番 SSH の例外を消す）
├─ infra/                        雛形にもう入っている（BSE と同じ。2026-10 の PHP の脆弱性への対策ずみ）
├─ tools/docs-html/              ←BSE（題名・キー名・ルートの指紋を直す）
├─ docs/                         2-3 の型（README・guide/01〜10・words.md・design/・ai/codemap.md・flow-src/・images/・research/）
└─ src/
   ├─ app/
   │  ├─ Actions/         Tasks/CreateTask.php …  Cards/RecordCard.php …  Calendar/ConnectCalendar.php  ImportEvents.php …
   │  ├─ Contracts/       Calendar.php                    カレンダーの口（本物と偽物を差し替える）
   │  ├─ Services/Google/ CalendarClient.php              google/apiclient を包む
   │  ├─ Http/
   │  │  ├─ Controllers/
   │  │  │  ├─ Controller.php
   │  │  │  ├─ Auth/       GoogleAuthController.php  LogoutController.php   ←BSE を短くして
   │  │  │  ├─ Tasks/      TaskController.php（resource）  CompleteTaskController.php（__invoke）
   │  │  │  ├─ Cards/      CardController.php
   │  │  │  ├─ Calendar/   CalendarConnectionController.php
   │  │  │  ├─ TodayController.php                        今日の画面（__invoke）
   │  │  │  └─ PageController.php                         privacy・terms・external・help
   │  │  ├─ Middleware/   SecurityHeaders.php ←BSE、（任意）VerifyOriginSecret.php
   │  │  └─ Requests/     Tasks/StoreTaskRequest.php  Tasks/UpdateTaskRequest.php  Cards/…
   │  ├─ Jobs/            ImportCalendarEventsJob.php（worker の箱が動かす）
   │  ├─ Models/          User.php  Task.php  Card.php  CalendarConnection.php（トークンは encrypted）
   │  ├─ Policies/        TaskPolicy.php  CardPolicy.php
   │  └─ Providers/       AppServiceProvider.php ←BSE（名前つきの回数制限・forceScheme・Calendar の結びつけ）
   ├─ bootstrap/app.php   ←BSE（trustProxies・SecurityHeaders・redirectGuestsTo・JSON のエラー）
   ├─ config/             suiri.php（設定値・文言を集める）  services.php（google）  legal.php ←BSE の形
   ├─ lang/ja/validation.php                              日本語のメッセージと項目名を1か所に（BSE は各 validate に直書き）
   ├─ database/migrations/  factories/  seeders/
   ├─ resources/
   │  ├─ css/app.css（＋ css/parts/*.css を @import）
   │  ├─ js/app.js（読み込むだけ）  js/parts/*.js
   │  └─ views/
   │     ├─ layouts/app.blade.php
   │     ├─ components/ui/     button  field  card  flash  page-head  empty
   │     ├─ components/task/   card  form（作成と編集で共用）
   │     ├─ components/card/   three-columns（見た・考えた・次に確かめる）
   │     ├─ today.blade.php  tasks/  cards/  calendar/  auth/welcome.blade.php
   │     ├─ pages/ privacy  terms  external  help       ←BSE の形
   │     └─ errors/ 403 404 419 429 500 503 layout       ←BSE の形
   ├─ routes/
   │  ├─ web.php          読み込むだけ
   │  ├─ web/ auth.php  today.php  tasks.php  cards.php  calendar.php  pages.php
   │  └─ console.php      スケジュール（予定の取り込み）
   └─ tests/
      ├─ TestCase.php     ←BSE（withoutVite）
      ├─ Fakes/FakeCalendar.php                          Contracts\Calendar の偽物
      ├─ Feature/Auth/GoogleAuthTest.php                 ←BSE を写して直す
      ├─ Feature/Tasks/  Feature/Cards/  Feature/Calendar/  Feature/Security/（ヘッダー・他人の記録・回数制限）
      └─ Unit/
```

### 6-2. 作業の順番

1. **決める（オーナー）**：アプリの略称（AWS の名前 `<app>-prod-*` と DB 名。雛形は `suiri`）、ドメイン（`app.<ドメイン>`）、GitHub の専用リポジトリ名、ログインを Google だけにするか、カレンダーのスコープ（R7 の MVP どおり「読むだけ」なら読み取り専用のスコープ）、同意画面をテストのままにするか公開して確認を受けるか（4 章：テストのままだと refresh token が 7 日で切れる）、PHP を 8.4 のままにするか 8.5 にするか（4 章）。
2. **雛形を整える**：`src/CLAUDE.md`・`src/AGENTS.md` を消し、直下に `CLAUDE.md`（＋`AGENTS.md` のリンク）。`git init` と専用リポジトリ、`git config core.hooksPath .githooks`（利用者が手で）。
3. **検品係を先に置く**：`bin/check`（行数の上限と MVC の禁止パターンを最初から入れる）・`code_comments.py`・フック3つ・`.claude/settings.json`・pre-commit。**空のアプリで全部が通る**ことを確かめてから中身を書く（あとから入れると、それまでの違反を直す作業が出る）。
4. **言葉と設計を先に書く**：`.claude/rules/glossary.md`＋`docs/words.md`（言葉のルール）、`docs/design/requirements.md`・`architecture.md`・`data-model.md`・`calendar-sync.md`。オーナーに確かめてから作る（BSE の設計書と同じく「決めてほしいこと」を最後に並べる）。
5. **CloudFront の後ろで動く土台**：`bootstrap/app.php`（trustProxies・SecurityHeaders・redirectGuestsTo・ルートの分割）、`AppServiceProvider`（名前つきの回数制限・本番だけ forceScheme）、レイアウトとエラー画面、`components/ui/*`。
6. **Google ログイン**：Socialite を入れる → `GoogleAuthController`（BSE を短く）→ `GoogleAuthTest`（BSE を写す）→ `/privacy`・`/terms`（同意画面の公開に要る）。
7. **1つ目の機能を縦に通す（型を作る）**：タスク：移行 → モデル → Policy → FormRequest → Action → コントローラー → `routes/web/tasks.php` → ビューとコンポーネント → テスト（他人のタスクが見えない・直せないを含む）。**これを以後の機能の手本にし、`docs/guide/06-parts.md`（部品説明書）にこの時点で書き始める。**
8. **カレンダー連携**：`Contracts\Calendar`＋偽物 → `CalendarClient`（google/apiclient。Calendar だけに絞る設定は 4 章）→ つなぐ・やめる（追加の同意、refresh token を暗号化して保存）→ 取り込みの Job（worker の箱）→ テスト（偽物で）。
9. **ホームズの練習の機能**（記録カード・次に確かめること・見立ての更新。R7 などの提案による）を、7 の型で1つずつ。
10. **ドキュメントを仕上げる**：codemap、解体新書 01〜10、流れ解説（`flow-src`）、`guide.html`・`flow.html` を作る道具を回す。`bin/check docs` が通ること。
11. **公開**：3-2 の手順書どおりに AWS（Budgets から始める）→ Google の本番設定 → 公開前の確認。本番で AI がしてよいこと（見る・反映）は、そのときオーナーに決めてもらい、決まったら `guard-bash` と `rules/deploy.md` に書く。

### 6-3. 設計にそのまま使う決まり（箇条書き）

- 受付（コントローラー）は 150 行・7 アクションまで、1 アクション 10 行が目安。検証は FormRequest、権限は Policy、用事は `app/Actions` の `handle()`、外の API は `app/Services` とインターフェース。
- 行数の上限（コメント込み）：PHP クラス 200・コントローラー 150・ルート 120・Blade 150・JS 200・CSS 400・テスト 300。`bin/check` の `check_size` で止め、`after-edit` フックで直した直後に AI に返す。例外は理由つきの表に。
- 機械で止める：コントローラーの `validate()`・`DB::`、ルートのクロージャ、名前なしの `throttle:数字`、`config/` 以外の `env()`、Blade の `@php` のかたまり・`@include` の部品、外部の CDN。
- ルートは `routes/web/{機能}.php`、CRUD は `Route::resource`、それ以外は 1 アクションのコントローラー。流れ解説の道具と `bin/check` の「住所の表の指紋」はこのフォルダ全体を見るように直す。
- CloudFront の後ろの3点（trustProxies・本番だけ forceScheme・本番では HSTS）と、本番の `GOOGLE_REDIRECT_URI` は https の絶対 URL。CloudFront は AllViewer・CachingDisabled、SG の 80 番はプレフィックスリスト。
- 2026-10 の PHP の脆弱性（BSE は php2shell と呼んでいる。CVE-2026-109618/109619）への対策の設定（nginx の multipart 拒否、upload_progress の固定 off、post_max_size 8M）はそのまま。ファイルを上げる機能を作るまで外さない。PHP は修正版（8.4.27 / 8.5.12 以上）のイメージにし、次の修正版が出たら `docker compose build --pull app` で上げる。
- Google のトークンは別の表に `encrypted` キャストで保存し、`#[Hidden]`。カレンダーの同意はログインと分けて後から求める。失敗してもアプリの保存は成功させ、取り込み・書き込みはキューで再試行。
- テストは sqlite in-memory・`withoutVite()`・Socialite とカレンダーは偽物。機能ごとに「他人の記録が見えない」テストを1つ以上。
- ドキュメントは BSE の型：解体新書（役目 → たとえ → 中身）、言葉のルール（AI 用と人間用の2つを同じ表で）、設計書（確かめ方・戻し方の列つきの手順）、部品説明書（新しく 06 章）、codemap（行番号を書かない）、流れ解説（本物のコードの抜き出しを機械で照合）。
- 本番の EC2 で AI に許すことは最初はゼロ（guard-bash で SSH を止める）。オーナーが決めてから例外を足す。
