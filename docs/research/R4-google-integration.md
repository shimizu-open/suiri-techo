# R4 Google 連携の技術調査（ログイン・カレンダー連携・審査・子どもの利用）

- 調査日: 2026-10-31
- 対象: Laravel 13（PHP 8.4）の Web アプリ。Google でログインし、アプリのタスク（日時つき）を利用者の Google カレンダーと連携する。公開は AWS（CloudFront → EC2 の Docker）。利用者は数人〜数十人、運営は個人。
- 書き方: 公式ページ（developers.google.com / support.google.com / laravel.com など）を実際に開いて確かめたものだけを出典にした。パッケージの版と動きは Packagist と GitHub のソースコード（タグ指定）で確かめた。確かめられなかったことは「**未確認**」と書いた。
- 用語: 「スコープ」＝アプリが利用者に求める許可の範囲。「リフレッシュトークン」＝利用者がいないときにアプリがアクセストークン（短命の鍵）を取り直すための長命の鍵。

---

## 0. 先に結論（要約）

1. **ログインは `openid email profile` だけにする。** この3つだけなら審査は不要で、テストユーザー登録・警告画面・7日で切れる制限もかからない（[Manage App Audience](https://support.google.com/cloud/answer/15549945)）。Socialite は state（CSRF 対策）を標準でかけ、`enablePKCE()` で PKCE も足せる。
2. **カレンダー連携は「使う人だけ、あとから」許可をもらう（段階的な許可）。** 最小のスコープは `calendar.app.created`。これで「アプリ専用のカレンダーを作り、そこに予定を書く・読む・消す」まではできる。ただし**利用者のふだんの（primary の）予定を読むことはできない**。
3. **スコープの分類（機密かどうか）は公式ページに一覧がない。** Google は「Cloud Console の画面で表示される」としている。カレンダーのスコープは制限付き（restricted）の一覧には入っていない（確認済み）。`calendar.app.created` を「非機密」と表示されたとする開発者の報告はあるが、公式には**未確認**。プロジェクト作成後に Data Access 画面で自分で確かめるのが最初の作業になる。
4. **Testing（テスト中）のままは使えない。** 基本の3スコープ以外を1つでも求めると、リフレッシュトークンが7日で切れる。数人〜数十人なら「In production（本番）にして、機密スコープなら未審査のまま使う」が現実的（未審査の上限は通算100人）。審査を受けるのは利用者が増えるときでよい。
5. **同期は「アプリが正（元データ）・専用カレンダーに書き出す」から始める。** イベントIDをアプリ側で決める（UUIDの16進文字）と二重登録を防げる。プッシュ通知（watch）は最初は要らない。
6. **子ども**: 日本では13歳から自分で Google アカウントを管理できる。13歳未満、またはファミリーリンクで管理されているアカウントは、外部アプリへのログインを保護者が管理する。子どもにはまずログインだけ（カレンダー連携なし）で使ってもらうのが安全。
7. **ICS購読URL方式**は審査もトークンも要らないが、Google カレンダー側の更新が遅く（公式の頻度は**未確認**）、一方通行。補助手段として置く価値はある。
8. Google Cloud 側は、プロジェクト作成と Calendar API の有効化は gcloud でできる。**Google Auth Platform（Branding/Audience/Clients/Data Access）と OAuth クライアント（Web application）の作成はコンソールでしかできない**。

---

## 1. ログイン（Laravel Socialite）

### 1.1 版と入れ方

- 最新版は **laravel/socialite v5.31.0（2026-08-31）**。要件は `php ^8.1`、`illuminate/* ^6.0 … ^13.0`（Laravel 13 に対応）。出典: Packagist `https://repo.packagist.org/p2/laravel/socialite.json`（2026-10-31 取得）。
- 入れ方: `composer require laravel/socialite`（[Laravel 13.x Socialite](https://laravel.com/docs/13.x/socialite)）。
- 設定は `config/services.php` の `google` キーに `client_id` / `client_secret` / `redirect` を書く。`redirect` が相対パスなら完全なURLに直される（同ページ）。

### 1.2 基本の流れ（公式ドキュメントの形）

- ルートは2つ。「Google へ送り出す」ルートと「Google から戻ってくる」ルート。送り出しは `Socialite::driver('google')->redirect()`、戻りは `Socialite::driver('google')->user()`（同ページ）。
- 戻りで受け取った利用者をDBで探し、なければ作って `Auth::login($user)` する（同ページの `updateOrCreate` の例）。
- Google のドライバーが最初から求めるスコープは `openid`, `profile`, `email`（ソースコード `src/Two/GoogleProvider.php` の `$scopes`、タグ v5.31.0: https://github.com/laravel/socialite/blob/v5.31.0/src/Two/GoogleProvider.php ）。つまり**何も足さなければログイン専用の最小構成になる**。

### 1.3 利用者を識別するキーは `sub`

- Google の OpenID Connect の説明: `sub` は「すべての Google アカウントの中で一意で、再利用されない」識別子。「`email` を一意の識別子に使わず、常に `sub` を使う」と明記（[OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect)）。
- Socialite の `getId()` は `sub` を返す（`GoogleProvider::mapUserToObject` で `'id' => Arr::get($user, 'sub')`）。→ `users.google_sub` に保存し、ここで突き合わせる。

### 1.4 審査が要らないこと（openid / email / profile のみ）

- 「基本のログイン用スコープ（名前・メール・プロフィール、または OpenID Connect の同等物）だけを求めるなら、テストユーザーの一覧・警告画面・7日の期限切れはかからない」（[Manage App Audience](https://support.google.com/cloud/answer/15549945)）。
- 非機密スコープだけなら「アプリ審査は必須ではない」。ただしアプリ名とロゴを同意画面に出したい場合はブランド審査を受ける（[OAuth API verification FAQs](https://support.google.com/cloud/answer/9110914)）。
- 補足: 「アプリ名は審査済みの場合にだけ同意画面に表示される」（[Manage OAuth App Branding](https://support.google.com/cloud/answer/15549049)）。ログインだけなら機能上の問題はない。

### 1.5 安全対策（state・PKCE ほか）

| 対策 | Socialite での状態 | 根拠 |
|---|---|---|
| **state（CSRF 対策）** | 既定で有効。送り出し時にセッションへ乱数を保存し、戻りで `hash_equals` で照合。食い違えば `InvalidStateException`。`stateless()` を呼ぶと無効になるので**Web画面では呼ばない** | `AbstractProvider::redirect()` / `hasInvalidState()`（v5.31.0 ソース）。Google も「戻ってきた state がセッションの値と一致することを確かめる」よう求める（[OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect)） |
| **PKCE** | `enablePKCE()` を呼ぶと `code_challenge`（S256）を付け、`code_verifier` をセッションに保存してトークン交換時に送る。**送り出しと戻りの両方で `enablePKCE()` を呼ぶ必要がある**（戻りで呼ばないと verifier が送られない） | `AbstractProvider::enablePKCE()` / `getTokenFields()`（v5.31.0 ソース）。公式ドキュメントのページには記載なし |
| Google 側の PKCE 対応 | Google の OpenID 設定に `code_challenge_methods_supported: ["plain","S256"]` | https://accounts.google.com/.well-known/openid-configuration （2026-10-31 取得） |
| PKCE を使う理由 | 秘密鍵を持てるサーバー型（confidential client）でも PKCE は「推奨（RECOMMENDED）」 | RFC 9700（OAuth 2.0 Security BCP, 2025年1月）2.1.1 https://www.rfc-editor.org/rfc/rfc9700.html |
| `with()` の注意 | `state` や `response_type` など予約語を渡さない | [Laravel 13.x Socialite](https://laravel.com/docs/13.x/socialite) |

その他、実装で押さえること:

- **アカウント選択**: 家族で同じ端末を使う可能性があるので、ログインでは `with(['prompt' => 'select_account'])` を付けるとよい（`select_account` は「アカウント選択を促す」: [OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect)）。
- **リダイレクトURIはHTTPS**: OAuth クライアントのリダイレクトURIは HTTPS 必須（localhost は例外）、IPアドレス不可、ワイルドカード・フラグメント不可（[Manage OAuth Clients](https://support.google.com/cloud/answer/15549257)）。CloudFront の裏で Laravel が `http` のURLを作ってしまうと不一致になるので、`.env` に完全な `https://…` のURLを書き、Laravel の信頼するプロキシ設定（`$middleware->trustProxies(at: '*')`、[Laravel 13.x Requests](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies)）も入れる。CloudFront がどの転送ヘッダーを付けるかは AWS 側の調査で確かめること（本調査では**未確認**）。
- **ボタンの見た目**: 「Sign in with Google」ボタンの指針に従うことは審査の条件。文言は「Sign in with Google」「Continue with Google」などで、日本語化してよい。G ロゴの色・大きさは変えない（[Sign in with Google branding guidelines](https://developers.google.com/identity/branding-guidelines)）。
- **テスト**: `Socialite::fake('google', User::fake([...]))` で Google に繋がずにテストできる（[Laravel 13.x Socialite](https://laravel.com/docs/13.x/socialite#testing)）。

---

## 2. カレンダーのスコープの分類

### 2.1 スコープの一覧（公式の説明文）

出典: [Choose Google Calendar API scopes](https://developers.google.com/workspace/calendar/api/auth)（最終更新 2026-09-03）。**このページには機密・制限付き・非機密の列はない**（表の列は Scope と Meaning の2つだけ）。

| スコープ（`https://www.googleapis.com/auth/` の後ろ） | できること（公式説明の訳） | 分類（確かめられた範囲） |
|---|---|---|
| `calendar` | アクセスできるすべてのカレンダーの閲覧・編集・共有・完全削除 | 機密（制限付きではない）と考えるのが妥当。下の注を参照 |
| `calendar.readonly` | アクセスできるすべてのカレンダーの閲覧・ダウンロード | 同上 |
| `calendar.events` | すべてのカレンダーの予定の閲覧・編集 | 機密（開発者フォーラムで「sensitive と表示」と報告。下の注） |
| `calendar.events.readonly` | すべてのカレンダーの予定の閲覧 | 機密と考えるのが妥当 |
| `calendar.events.owned` | 自分が所有するカレンダーの予定の閲覧・作成・変更・削除 | **未確認**（機密と考えるのが妥当） |
| `calendar.events.owned.readonly` | 自分が所有するカレンダーの予定の閲覧 | **未確認**（機密と考えるのが妥当） |
| `calendar.app.created` | 副カレンダー（secondary calendar）を作り、その上の予定を閲覧・作成・変更・削除 | **未確認**。非公式に「非機密と表示された」報告あり（下の注） |
| `calendar.calendarlist.readonly` | 登録しているカレンダーの一覧の閲覧 | **未確認**。非公式に「非機密」とする記述あり |
| `calendar.freebusy` / `calendar.events.freebusy` | 空き時間の閲覧 | **未確認** |

注（分類について確かめられたこと）:

- 分類は Cloud Console の Data Access 画面で表示される。「Non-Sensitive: 特定の読み取り専用データへのアクセス」「Sensitive: 利用者の個人データへのアクセス」「Restricted: 非常に機密性の高い利用者データへのアクセス」（[Manage App Data Access](https://support.google.com/cloud/answer/15549135)）。スコープ一覧ページも「機密スコープはコンソールの同意画面設定で sensitive の印が付く」とだけ書き、個別の分類は載せていない（[OAuth 2.0 Scopes for Google APIs](https://developers.google.com/identity/protocols/oauth2/scopes)）。
- Google は機密スコープの例として「Google カレンダーに保存された予定を読むこと」を挙げている（[Sensitive scope verification](https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification)）。→ 予定を読めるスコープは機密と考えるのが妥当。
- **制限付き（restricted）の一覧に Calendar のスコープはない**。一覧は Gmail・Drive・Fit・Chat・Data Portability・Photos Ambient・Health（[Restricted scopes](https://support.google.com/cloud/answer/13464325)）。→ カレンダーでは、毎年の再審査や第三者セキュリティ評価（制限付きスコープの条件: [Restricted scope verification](https://developers.google.com/identity/protocols/oauth2/production-readiness/restricted-scope-verification)）は要らない。
- 非公式情報（出典にはするが公式ではない）:
  - 2026-10-04、ある開発者の記録に「コンソールは `calendar.app.created` を non-sensitive の下に並べている」（https://github.com/thedavidweng/adp-calendar/issues/3 ）。一方、別の開発者は `calendar.app.created` を機密スコープの一つとして扱っている（根拠の記載なし。https://github.com/rhysdunne/screenshot-to-calendar/issues/43 ）。
  - Google 開発者フォーラムでは「`calendar.app.created` と `calendar.freebusy` が Data Access のスコープ選択一覧に出てこない」という報告がある（回答なし。https://discuss.google.dev/t/oauth-verification-pending-since-22-apr-2026-first-trust-safety-email-never-received/392005 ）。一覧にないスコープは「Manually add scopes」の欄で手で足せる（[Manage App Data Access](https://support.google.com/cloud/answer/15549135)）。
  - `calendar.calendarlist.readonly` を non-sensitive とする記述（https://github.com/agilityengineers/SmartScheduler/pull/50 、根拠の引用なし）。

### 2.2 API の操作ごとに使えるスコープ（公式リファレンス）

| 操作 | 受け付けるスコープ（どれか1つ） | 出典 |
|---|---|---|
| `calendars.insert`（専用カレンダーを作る） | `calendar` / `calendar.app.created` / `calendar.calendars` | [calendars.insert](https://developers.google.com/workspace/calendar/api/v3/reference/calendars/insert) |
| `calendars.delete`（副カレンダーを消す。primary は不可） | `calendar` / `calendar.app.created` / `calendar.calendars` | [calendars.delete](https://developers.google.com/workspace/calendar/api/v3/reference/calendars/delete) |
| `events.insert` | `calendar` / `calendar.events` / `calendar.app.created` / `calendar.events.owned` | [events.insert](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert) |
| `events.patch` | `calendar` / `calendar.events` / `calendar.app.created` / `calendar.events.owned` | [events.patch](https://developers.google.com/workspace/calendar/api/v3/reference/events/patch) |
| `events.list` | `calendar.readonly` / `calendar` / `calendar.events.readonly` / `calendar.events` / `calendar.app.created` / `calendar.events.freebusy` / `calendar.events.owned` / `calendar.events.owned.readonly` / `calendar.events.public.readonly` | [events.list](https://developers.google.com/workspace/calendar/api/v3/reference/events/list) |
| `calendarList.list` | `calendar.readonly` / `calendar` / `calendar.calendarlist` / `calendar.calendarlist.readonly` | [calendarList.list](https://developers.google.com/workspace/calendar/api/v3/reference/calendarList/list) |

### 2.3 最小のスコープで何ができるか

- **「アプリ専用のカレンダーを作ってそこに予定を書く」→ `calendar.app.created` 1つでできる。** 作成・予定の追加・変更・削除・一覧（そのカレンダーに限る）がすべて受け付けられる（上の表）。作ったカレンダーの所有者は認証した利用者になり、そのカレンダーは本人のカレンダー一覧に追加される（[Calendars and events](https://developers.google.com/workspace/calendar/api/concepts/events-calendars) の要旨。検索結果経由で確認）。
  - 利点: 利用者がスマホの Google カレンダーでその専用カレンダーに予定を足した場合も、アプリはそれを読める（`events.list` が `calendar.app.created` を受け付けるため）。つまり**専用カレンダーの中なら双方向の同期ができる**。
- **「利用者の今日の予定を読む」→ `calendar.app.created` ではできない。** primary（ふだんの）カレンダーを読むには、狭い順に `calendar.events.owned.readonly`（自分が所有するカレンダーの予定。primary を含む）→ `calendar.events.readonly`（登録している他人のカレンダーも含む）→ `calendar.readonly` のどれかが要る。どれも「予定を読む」ので機密と考えるのが妥当。
- 利用者にカレンダーを選ばせたい場合だけ `calendar.calendarlist.readonly` を足す（最初は不要）。

Google 自身も「できるだけ狭いスコープを選ぶ。限定され、説明がはっきりしたスコープほど利用者は許可しやすい」としている（[Choose Google Calendar API scopes](https://developers.google.com/workspace/calendar/api/auth)）。

---

## 3. 審査（OAuth app verification）

### 3.1 公開状態ごとの違い

| 状態 | 使える人 | 機密スコープを求めたとき | リフレッシュトークン | 出典 |
|---|---|---|---|---|
| **Testing（テスト中）** | テストユーザーに登録した人だけ（最大100人） | 未審査の警告が出る | **基本の3スコープ以外を求めると7日で切れる** | [Manage App Audience](https://support.google.com/cloud/answer/15549945)、[Using OAuth 2.0](https://developers.google.com/identity/protocols/oauth2#expiration) |
| **In production・未審査** | Google アカウントを持つ誰でも | 「未審査のアプリ」画面が出る。**新しい利用者は通算100人まで** | 通常の条件（7日の制限はない） | [Manage App Audience](https://support.google.com/cloud/answer/15549945)、[Unverified apps](https://support.google.com/cloud/answer/7454865) |
| **In production・審査済み** | 誰でも | 警告なし、上限なし（承認されたスコープのみ） | 通常の条件 | [Manage App Audience](https://support.google.com/cloud/answer/15549945) |
| 非機密スコープだけ | 誰でも | 審査は必須ではない | 通常の条件 | [OAuth API verification FAQs](https://support.google.com/cloud/answer/9110914) |

### 3.2 「7日で切れる」の正確な条件

- 公式の文（要旨）: 外部（External）の利用者向けに同意画面を設定し、公開状態が「Testing」のプロジェクトは、**求めるスコープが「名前・メールアドレス・プロフィール（`userinfo.email`, `userinfo.profile`, `openid` と OpenID Connect の同等物）」の部分集合でない限り**、7日で切れるリフレッシュトークンを発行される（[Using OAuth 2.0 to Access Google APIs – Refresh token expiration](https://developers.google.com/identity/protocols/oauth2#expiration)）。
- 同じ内容を Audience のページは「テストユーザーの許可は同意から7日で切れる」と書く（[Manage App Audience](https://support.google.com/cloud/answer/15549945)）。
- つまり**機密かどうかではなく、「基本の3つ以外を1つでも求めたら」7日**。`calendar.app.created` が非機密だったとしても Testing のままでは7日で切れる。→ カレンダー連携をするなら In production にする必要がある。

### 3.3 その他、リフレッシュトークンが使えなくなる条件（公式の一覧）

[Using OAuth 2.0 – Refresh token expiration](https://developers.google.com/identity/protocols/oauth2#expiration) より:

- 利用者がアプリのアクセスを取り消した。
- **6か月使われなかった。**
- 利用者がパスワードを変え、かつトークンに Gmail のスコープが含まれる（本アプリは該当しない）。
- 生きているリフレッシュトークンの数の上限を超えた。**「1つの Google アカウントにつき1つの OAuth クライアントIDあたり100個」**。超えると最も古いものが予告なく無効になる。
- 期間限定の許可（time-based access）が切れた。管理者がサービスを制限した（`admin_policy_enforced`）。など。

→ 毎回のログインで `access_type=offline` を求めるとトークンが増え続けるので、**ログインはオフライン許可を求めず、カレンダー連携のときだけ求める**のがよい。

### 3.4 未審査で In production にした場合

- 機密・制限付きスコープを求めるクライアントは、審査が終わるまで「Unverified apps」の警告を見せる（[Manage App Audience](https://support.google.com/cloud/answer/15549945)）。
- 上限: 「未審査アプリ画面を表示したあとの新しい利用者は**通算100人**」。プロジェクトの存続期間を通じた数で、**リセットも変更もできない**（同ページ。[Unverified apps](https://support.google.com/cloud/answer/7454865) の表も「100 new users in total」）。
- 警告画面から先へ進む具体的な操作（「詳細」→「（安全ではないページ）に移動」）の文言は、公式ページではスクリーンショットだけで本文になかった（**未確認**）。導入時に実際の画面を撮って利用者に配るとよい。
- **個人利用の例外**: 「個人利用のアプリ（100人未満）なら、利用者は未審査の警告画面を押して進める。100人を超えて広げるには審査が必要」（[When is verification not needed](https://support.google.com/cloud/answer/13464323)）。数人〜数十人の本アプリはこれに当てはまる。
- 未登録のスコープを使うと警告画面になるので、使うスコープは Data Access に登録しておく（[Manage App Data Access](https://support.google.com/cloud/answer/15549135)）。

### 3.5 審査を受ける場合に要るもの・期間

[Sensitive scope verification](https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification) より:

- **公開されたホームページ**（ログインしないと見られないページは不可）。
- **プライバシーポリシー**（利用者から見える場所に、ホームページと同じドメインで置く）。リンクはホームページ・利用規約とともに「外部向けの本番アプリでは必須」（[Manage OAuth App Branding](https://support.google.com/cloud/answer/15549049)）。
- **ドメインの所有確認**: 承認済みドメインを Google Search Console で確認する（プロジェクトの Owner/Editor のアカウントで）。
- **スコープごとの理由の説明**。
- **デモ動画**: 利用者が許可を始めて与えるまでの流れを全部見せる。YouTube に「限定公開」で上げる。同意画面は英語で、アプリ名・アドレスバーの OAuth クライアントID・各スコープの機能が映っていること。
- 先にブランドの確認（Verify Branding → Publish branding）。自動のブランド審査は通常数分。
- **期間**: 公式は「機密スコープの審査は最大10日」。ただし開発者フォーラムには「2026年4月に申請して4か月音沙汰なし」という報告もある（https://discuss.google.dev/t/oauth-verification-pending-since-22-apr-2026-first-trust-safety-email-never-received/392005 、1件の報告であり一般化はできない）。
- 審査の有無にかかわらず、すべてのアプリは Google API Services User Data Policy に従う。プライバシーポリシーの公開、必要な許可だけを求めること、機密データの「限定的な利用（Limited Use）」（広告に使わない、原則として人が読まない等）が求められる（[Google API Services User Data Policy](https://developers.google.com/terms/api-services-user-data-policy)）。

### 3.6 ドメインの注意（CloudFront の既定ドメイン）

- 「プロジェクトで使うドメインは、ブランド画面でもクライアント設定でも、すべて承認済みドメインに先に登録する」「リダイレクトURIより先に承認済みドメインを足す」（[Manage OAuth App Branding](https://support.google.com/cloud/answer/15549049)）。
- `cloudfront.net` は Public Suffix List に載っている（https://publicsuffix.org/list/public_suffix_list.dat 、2026-10-31 取得）。そのため `dxxxx.cloudfront.net` は形の上では「登録可能なドメイン」になるが、**Google の承認済みドメインとして受け付けられるか、Search Console で確認できるかは未確認**。審査やブランド表示まで考えるなら独自ドメインを用意するのが確実。プロジェクト作成直後に、実際のリダイレクトURIを入れて受け付けられるか試すこと。

### 3.7 数人〜数十人の個人アプリにとっての現実的な選び方

1. ログイン（基本3スコープ）は審査不要。最初から In production にしてよい。
2. カレンダー連携は `calendar.app.created` から。**Data Access 画面でこのスコープが「非機密」と表示されれば、In production で警告なし・7日制限なし・審査不要で使える**。
3. 「機密」と表示された場合は、In production・未審査で使う（個人利用の例外、通算100人まで）。連携ボタンを押した人だけが警告画面を一度見る。Testing は7日ごとの再連携が必要になるので選ばない。
4. 「今日の予定を読む」（機密）は第2段階。希望者だけ追加で許可をもらう。
5. 審査は「利用者が100人に近づく」「一般公開する」ときに受ける。そのときに要るもの（独自ドメイン・プライバシーポリシー・動画）は早めに準備しておくと楽。

---

## 4. 段階的な許可（incremental authorization）

### 4.1 仕組み

- `include_granted_scopes=true` を付けると、新しく発行されるトークンが「以前に許可されたスコープも含む」。Google はこれを段階的な許可と呼ぶ（[OAuth 2.0 for Web Server Applications](https://developers.google.com/identity/protocols/oauth2/web-server)）。Google は「段階的な許可をベストプラクティスとして推奨」「タスクに必要なスコープだけを求める」「許可を求める前に理由を説明する」「ログイン時に多くのスコープをまとめて求めない」としている（[Granular permissions](https://developers.google.com/identity/protocols/oauth2/resources/granular-permissions)、最終更新 2026-10-30）。
- **利用者はスコープを一部だけ許可できる**（細かい同意画面）。「求めたスコープを全部もらえたと仮定してはいけない」。トークン応答の `scope` に実際に許可されたスコープが入る（同ページ、[Web Server Applications](https://developers.google.com/identity/protocols/oauth2/web-server)）。

### 4.2 リフレッシュトークンを確実にもらう条件

- `access_type=offline` が必要（既定は `online`）。
- リフレッシュトークンは**最初の認可のときにだけ返る**。`prompt` を省くと同意画面は最初の一度だけになる。新しいリフレッシュトークンを確実に出させるには `prompt=consent` を付ける（[Web Server Applications](https://developers.google.com/identity/protocols/oauth2/web-server)）。
- → カレンダー連携の送り出しで `access_type=offline` と `prompt=consent` を両方付ける。それでも `refreshToken` が空なら、連携失敗として「もう一度」を促す。

### 4.3 Socialite での書き方（推奨形）

```php
// 連携の送り出し（ログイン済みの人だけ）
public function redirect(Request $request)
{
    return Socialite::driver('google')
        ->redirectUrl(route('google.calendar.callback')) // ログインとは別の戻り先
        ->scopes(['https://www.googleapis.com/auth/calendar.app.created']) // 既定の openid/profile/email に足される
        ->with([                                    // with() は「上書き」なので1回にまとめる
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'login_hint' => $request->user()->email, // ログイン中のアカウントを選ばせる
        ])
        ->enablePKCE()
        ->redirect();
}

// 連携の戻り
public function callback(Request $request, CalendarConnector $connector)
{
    $g = Socialite::driver('google')
        ->redirectUrl(route('google.calendar.callback')) // 送り出しと同じURLでないとトークン交換が失敗する
        ->enablePKCE()                                    // 戻りでも必要
        ->user();

    // 1) 別の Google アカウントで許可されていないか
    abort_unless($g->getId() === $request->user()->google_sub, 403);
    // 2) カレンダーのスコープが本当に許可されたか（一部だけ許可される場合がある）
    // 3) refreshToken があるか
    $connector->connect($request->user(), $g); // 中で 2) 3) を確認し、暗号化して保存
    return redirect()->route('settings.calendar');
}
```

根拠（Socialite v5.31.0 のソース）:
- `scopes()` は既存のスコープに**足す**（`array_merge`）。`setScopes()` は置き換え（[Laravel 13.x Socialite](https://laravel.com/docs/13.x/socialite#access-scopes)）。
- `with()` は `$this->parameters = $parameters;` で**置き換える**。何度も呼ぶと前の値が消える（`AbstractProvider::with()`）。
- `redirectUrl($url)` で戻り先を変えられる。トークン交換では `redirect_uri` に同じ値を送る（`getTokenFields()`）。
- 戻りの `user()` は `token` / `refreshToken` / `expiresIn` / `approvedScopes`（トークン応答の `scope` を空白で分けたもの）を持つ（`userInstance()`）。
- `login_hint` はメールアドレスか `sub` を渡すとアカウント選択を省ける（[OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect)）。それでも利用者は別アカウントを選べるので、`sub` の一致確認は必須。

---

## 5. Google API PHP クライアント（google/apiclient）を Laravel で使う

### 5.1 版と状態

- **google/apiclient v2.20.1（2026-09-25）**。要件 `php ^8.1`、`google/auth ^1.53`、`google/apiclient-services ~0.350`、`guzzlehttp/guzzle ^7.8.2||^8.0`。google/apiclient-services の最新は **v0.465.0（2026-10-26）**、google/auth は v1.55.3（2026-10-28）。出典: Packagist（2026-10-31 取得）。
- **「完成しておりメンテナンスモード。重大なバグとセキュリティ問題には対応するが新機能は足さない」**（README、https://github.com/googleapis/google-api-php-client/blob/v2.20.1/README.md ）。
- README は「本番の前に google/apiclient-services を最新版に**固定（pin）**することを強く勧める」「使わない200以上のサービスは `Google\Task\Composer::cleanup` で消せる」と書く（同 README）。

```json
{
  "require": {
    "laravel/socialite": "^5.31",
    "google/apiclient": "^2.20",
    "google/apiclient-services": "0.465.0"
  },
  "scripts": {
    "pre-autoload-dump": "Google\\Task\\Composer::cleanup"
  },
  "extra": {
    "google/apiclient-services": ["Calendar"]
  }
}
```

（Laravel の composer.json には既に `scripts` があるので、そこへ `pre-autoload-dump` を足す。サービスを足し直すときは `vendor/google/apiclient-services` を消してから `composer update`。）

### 5.2 トークンの保存（暗号化）

- Laravel の `encrypted` キャストは保存時に暗号化し、読むときに戻す。`encrypted:array` もある。**暗号文は長くなるので列は `TEXT` 以上**。暗号化した値では検索できない。鍵は `APP_KEY`。鍵の入れ替えは「穏やかな入れ替え（graceful rotation）」の手順がある（[Laravel 13.x Eloquent: Mutators & Casting – Encrypted Casting](https://laravel.com/docs/13.x/eloquent-mutators#encrypted-casting)）。
- 推奨: 連携情報は `users` に混ぜず、別テーブル `google_connections` にする（ファイルとモデルが太らない、解除で丸ごと消せる）。

```php
// app/Models/GoogleConnection.php（抜粋）
protected function casts(): array
{
    return [
        'token'          => 'encrypted:array', // access_token, refresh_token, expires_in, created, scope
        'granted_scopes' => 'array',
        'revoked_at'     => 'datetime',
        'last_synced_at' => 'datetime',
    ];
}
```

- `APP_KEY` が漏れるとトークンも読めるので、`APP_KEY` と DB を同じ場所に置かない運用（.env の管理）が前提。

### 5.3 期限切れの更新（自動と明示）

ソースコード（`src/Client.php`、v2.20.1）で確かめた動き:

- `setAccessToken($token)` に `access_token` / `refresh_token` / `expires_in` / `created` を入れておくと、`isAccessTokenExpired()` は「残り30秒を切ったら期限切れ」と判定する。
- API 呼び出し時の `authorize()` は、期限切れでリフレッシュトークンがあれば自動で取り直す仕組みを持つ。新しいトークンは `setTokenCallback()` で受け取れる（README「Updating Tokens」）。
- ただし**既定の HTTP クライアントは `http_errors => false`**。`fetchAccessTokenWithRefreshToken()` は失敗しても例外を投げず、`['error' => 'invalid_grant', ...]` のような配列を返す。自動更新に任せると、失敗が「401 で API 呼び出しが落ちる」形でしか見えない。

→ 推奨: **呼び出しの前に自分で更新する**小さなクラス（`GoogleTokenManager`）を作る。

```php
public function clientFor(GoogleConnection $c): \Google\Client
{
    $client = $this->factory->make();      // client_id/secret, retry 設定済み
    $client->setAccessToken($c->token);

    if ($client->isAccessTokenExpired()) {
        $new = $client->fetchAccessTokenWithRefreshToken($c->token['refresh_token']);
        if (isset($new['error'])) {
            if ($new['error'] === 'invalid_grant') {
                $c->markRevoked();                 // 状態を「要再連携」に
                throw new GoogleConnectionRevoked($c);
            }
            throw new GoogleTokenRefreshFailed($new['error']); // 一時的な失敗 → ジョブを後で再試行
        }
        $c->update(['token' => $client->getAccessToken()]); // refresh_token は欠けていれば元の値が補われる
    }
    return $client;
}
```

（`fetchAccessTokenWithRefreshToken()` は応答に `refresh_token` がなければ元の値を入れ直す。`created` も付ける。ソースで確認。）

### 5.4 取り消された場合（invalid_grant）

- 更新時の `invalid_grant` は「トークンが期限切れか無効にされた」ことを意味する。リフレッシュトークンを失ったら、利用者はもう一度同意の流れを通る必要がある（[Web Server Applications](https://developers.google.com/identity/protocols/oauth2/web-server)）。
- 401（authError）は「リフレッシュトークンで取り直す。それも失敗したら OAuth の流れへ」（[Calendar API errors](https://developers.google.com/workspace/calendar/api/guides/errors)）。
- 実装: 連携を「要再連携」にし、同期ジョブを止め、画面上部に「Google カレンダーとの連携が切れました。もう一度つなぐ」を出す。タスク自体はアプリ側に残るので失われない。

### 5.5 利用者が連携を解除する機能（revoke）

- PHP では `$client->revokeToken()`。エンドポイントは `https://oauth2.googleapis.com/revoke`。**アクセストークンを取り消すと、対応するリフレッシュトークンも取り消される**。取り消しは「そのプロジェクトに以前与えたすべてのスコープ」を外し、完全に効くまで少し時間がかかることがある（[Web Server Applications](https://developers.google.com/identity/protocols/oauth2/web-server)）。
- 注意: すべてのスコープが外れるので、解除後の次回ログインでは同意画面がもう一度出る（ログイン自体はできる）。
- 解除の手順（推奨）: ①「専用カレンダーも消しますか？」を聞く（消すなら `calendars.delete`、`calendar.app.created` で可能）→ ② `revokeToken(refresh_token)` → ③ `google_connections` を削除し、タスクの `google_event_id` を空にする。②が失敗しても③は行う（ローカルに鍵を残さない）。
- 利用者が Google アカウント側で取り消した場合は、次回の更新で `invalid_grant` になるので 5.4 の扱いになる。

### 5.6 再試行

- `new Google\Client(['retry' => ['retries' => 3]])` とすると、500・503・`rateLimitExceeded`・`userRateLimitExceeded` を指数的に間を空けて再試行する（既定は再試行なし: `maxAttempts = 1`。`src/Task/Runner.php` v2.20.1 で確認）。
- Calendar API も「時間に関するエラーは切り詰めた指数バックオフで」と勧める（[Calendar API quota](https://developers.google.com/workspace/calendar/api/guides/quota)）。

### 5.7 代わりの方法（参考）

- 使う API は数個（calendars.insert/delete、events.insert/patch/delete/list）なので、google/apiclient を使わず Laravel の HTTP クライアントで REST を直接呼ぶこともできる。トークン更新は Socialite の `refreshToken()`（`GoogleProvider::refreshToken()` が `Token` を返す）でも可能。ただし Socialite の更新は Guzzle の既定（4xx で例外）で動くので、例外を `invalid_grant` 判定に変える処理が要る。
- 本調査の推奨は **google/apiclient を自前のインターフェース（`CalendarGateway`）の裏に隠す**形。型付きのモデル（Event など）と再試行が使え、テストでは偽物の実装に差し替えられる。

---

## 6. 同期の設計

### 6.1 基本方針

- **アプリが正（元データ）**。Google カレンダーは「見る場所・通知を受ける場所」。
- **専用カレンダー方式**（例:「ホームズ手帳」）。利点:
  1. 最小スコープ（`calendar.app.created`）で済む。
  2. 利用者のふだんの予定を誤って書き換える・消すことがない。
  3. Google カレンダー上で表示・非表示・色を一括で切り替えられる。
  4. 解除時に「カレンダーごと消す」だけで後片付けが終わる。
  5. 増分同期（syncToken）の対象が1つのカレンダーに絞れる。

### 6.2 アプリ → カレンダー（書き出し）

- **イベントIDをアプリが決める**: `events.insert` の `id` は base32hex の文字（小文字 a〜v と数字 0〜9）、長さ5〜1024、カレンダー内で一意（[events.insert](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert)）。**UUID からハイフンを除いた32文字の16進（0-9a-f）はこの条件を満たす**。ULID は w〜z を含みうるので不可。
  - 同じIDでもう一度 insert すると 409（duplicate）。「新しく作るなら新しいID、既存を変えるなら update」（[Calendar API errors](https://developers.google.com/workspace/calendar/api/guides/errors)）。→ 「まず insert、409 なら patch」で**二重登録を防げる（冪等）**。
  - 削除済みの予定と同じIDで作り直したときの動き（409 になる・status を戻せば復活する等）は**未確認**。削除したタスクのIDは使い回さない。
- **extendedProperties.private にアプリのIDを入れる**: 例 `{"app": "holmes", "taskId": "<uuid>"}`。private は「このカレンダー上の予定のコピーに固有」。キーは最大44文字（超えると黙って捨てられる）、値は最大1024文字（超えると黙って切られる）、1予定あたり300個・合計32kBまで。`events.list` の `privateExtendedProperty=taskId%3D…` で探せる（[Extended properties](https://developers.google.com/workspace/calendar/api/guides/extended-properties)）。
- **時刻**: `start`/`end` は必須。終わりは含まない。`dateTime` は RFC3339 でオフセット必須（`timeZone` を指定すれば省ける）。終日は `date`（yyyy-mm-dd）。繰り返し予定では `timeZone` が必須（[events.insert](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert)）。→ DB は UTC、送るときに `Asia/Tokyo` を付ける。
- **通知を Google に任せる**: `reminders.useDefault=false` と `reminders.overrides`（最大5件、`email` か `popup`、0〜40320分）で、スマホへの通知を Google カレンダーに出させられる（[Events resource](https://developers.google.com/workspace/calendar/api/v3/reference/events)）。アプリ側でプッシュ通知を作らずに済む。
- **更新**: `events.patch` は送らなかった項目を変えないが、配列項目は丸ごと置き換える。**patch は3クォータ単位を使う**ので Google は get＋update を勧めている（[events.patch](https://developers.google.com/workspace/calendar/api/v3/reference/events/patch)）。本アプリの規模では patch で問題ない。
- **削除**: タスク削除 → `events.delete`。既に消えていれば 410（deleted）で「何もしなくてよい」（[Calendar API errors](https://developers.google.com/workspace/calendar/api/guides/errors)）。404 も「済み」として扱う。
- **キュー**: タスクの保存後（トランザクション確定後）にジョブを積む。同じタスクのジョブは重ならないようにする（ShouldBeUnique など）。失敗はタスクに `google_sync_error` として残し、画面に小さく出す。

### 6.3 カレンダー → アプリ（取り込み）

- **増分同期（syncToken）**: 最初に全件を取り、最後のページにある `nextSyncToken` を保存。次回はそのトークンで変更分だけ取る。ページがあるときは同じトークン＋`pageToken` で最後まで読む（[Synchronize resources efficiently](https://developers.google.com/workspace/calendar/api/guides/sync)）。
- syncToken と一緒に使えないパラメータ: `iCalUID`, `orderBy`, `privateExtendedProperty`, `q`, `sharedExtendedProperty`, `timeMin`, `timeMax`, `updatedMin`。`showDeleted=false` も不可（[events.list](https://developers.google.com/workspace/calendar/api/v3/reference/events/list)）。増分の結果には削除された予定（`status: cancelled`）が必ず含まれる（[sync ガイド](https://developers.google.com/workspace/calendar/api/guides/sync)）。
- **410 Gone**: トークンが無効になった印。「保存しているデータを消して、トークンなしで全件同期し直す」（同ガイド、[errors](https://developers.google.com/workspace/calendar/api/guides/errors) の `fullSyncRequired` / `updatedMinTooLongAgo`）。
- 取り込みの扱い（推奨）:
  - `extendedProperties.private.taskId` がある予定 → アプリのタスクの時刻だけ反映（Google で時間をずらしたら、アプリも追従）。消されていたら「カレンダーから消されました」と表示し、アプリ側を自動では消さない（事故防止）。
  - `taskId` がない予定（利用者が専用カレンダーに直接足したもの）→ 第1段階では無視、または「取り込み候補」として表示。
- **今日の予定を読む（第2段階、機密スコープ）**: primary に対して `events.list` を `timeMin`＝今日0時、`timeMax`＝明日0時（どちらもオフセット付き RFC3339）、`singleEvents=true`（繰り返しを1件ずつに展開）、`orderBy=startTime` で呼ぶ。`timeMax` は開始時刻、`timeMin` は終了時刻で絞られる。1ページ既定250件、最大2500件（[events.list](https://developers.google.com/workspace/calendar/api/v3/reference/events/list)）。**保存せず表示するだけ**にし、数分キャッシュする。syncToken は使わない。

### 6.4 プッシュ通知（watch）は要るか

- watch は HTTPS で、正しい SSL 証明書（自己署名不可、ホスト名一致）のある受け口が要る。通知には本文がなく「変わった」という合図だけで、結局 API を呼び直す。チャンネルは自動更新されず、期限前に新しいIDで watch し直す必要がある（[Push notifications](https://developers.google.com/workspace/calendar/api/guides/push)）。
- 本アプリは「アプリが正」で、取り込みは専用カレンダーの差分だけ。**最初は不要**。「画面を開いたとき」と「スケジューラで15分ごと」に増分同期すれば足りる。

### 6.5 API の回数制限

- 1プロジェクト1分あたり 10,000 リクエスト、1利用者1プロジェクト1分あたり 600 リクエスト（1分の滑らかな窓で計算）。超えると 403 / 429（`usageLimits`）。日ごとの課金しきい値は1プロジェクト1日 1,000,000 リクエストで、それ未満は課金されない。「通常の利用は追加費用なし」、超過分の課金は「2026年後半に予定」（[Calendar API quota](https://developers.google.com/workspace/calendar/api/guides/quota)、最終更新 2026-09-11）。
- 数十人・1人1日数十回の規模では制限に届かない。ただし「1つのカレンダーに短時間で続けて書く」ことには別の運用上の制限がありうる（同ページ。数値の記載なし）→ 一括登録は少し間を空ける。

### 6.6 エラーの扱い（まとめ）

| 応答 | 扱い（[Calendar API errors](https://developers.google.com/workspace/calendar/api/guides/errors)） |
|---|---|
| 400 | 直さない限り失敗し続けるので再試行しない。ログに残す |
| 401 | トークンを取り直す。だめなら再連携へ |
| 403/429 rateLimitExceeded 等 | 指数バックオフで再試行 |
| 404 | 指数バックオフ。専用カレンダー自体が消されていたら作り直す |
| 409 duplicate | 既存を update/patch |
| 410 fullSyncRequired | 保存データを消して全件同期 |
| 410 deleted | 何もしない |
| 412 | 取り直して変更をかけ直す |
| 500 | 指数バックオフ |

---

## 7. Google アカウントの年齢と、子どもの利用

### 7.1 年齢

- 日本は国別の一覧に載っておらず、「一覧にないすべての国では、13歳が Google アカウントを自分で管理できる最低年齢」（[Google アカウントの年齢制限](https://support.google.com/accounts/answer/1350409?hl=ja)）。→ **日本では13歳から**。
- 13歳未満は保護者がファミリーリンクで子ども用アカウントを作って管理する。13歳以上は本人がアカウントを作れ、あとから保護者が管理を加えられる（[お子様の Google アカウントを作成する](https://support.google.com/families/answer/7103338?hl=ja) の要旨、検索結果経由）。
- 13歳以上18歳未満の管理を止めるには保護者の承認が要る。止めると本人と保護者に通知が届く（[13歳以上のお子様に対する管理機能](https://support.google.com/families/answer/9092383?hl=ja)）。

### 7.2 管理されている子どものアカウントで「Google でログイン」できるか

- 確かめられたこと: 保護者はファミリーリンクで「子どもがサードパーティのアプリやサイトに自分のアカウントへのアクセスを与えられるか」を管理できる（[既存アカウントへの管理の追加](https://support.google.com/families/answer/9055704)）。設定場所は「お子様を選ぶ →［管理］→［アカウント設定］→［サードパーティ製アプリの管理］」で、「サードパーティ製アプリのアクセスを管理」から個別に取り消せる（[Third-party sites & apps with access to your child's account](https://support.google.com/families/answer/9204736)）。「子どもが一部のアプリに Google アカウントでログインできない」場合の答えも「ファミリーリンクでサードパーティのアクセスを管理できる」（[Manage your child's Google Play apps](https://support.google.com/families/answer/7103028)）。
- 具体的な承認の流れ（子どもが許可を求め、保護者が承認する。データをあまり求めないアプリは一括で許可できる切り替えがある）は、2021年のニュース記事でしか確かめられなかった（Android Police, 2021-06-10: https://www.androidpolice.com/2021/06/10/google-accounts-for-kids-can-now-finally-sign-into-third-party-apps/ ）。**現在の画面の文言・選択肢は未確認**。カレンダーのような機密スコープを子どものアカウントで許可できるかも**未確認**。
- Google の利用者データポリシー: 主に子ども向けのアプリは Google Sign-In や Google アカウントのデータを使う API を使うべきでない。子どもと大人が混在するアプリは Google ログインを必須にせず、ログインしなくても全部使えるようにする。COPPA（米国、13歳未満）への言及あり（[Google API Services User Data Policy – Child-directed apps](https://developers.google.com/terms/api-services-user-data-policy)）。本アプリは大人（保護者・施術者など）向けの一般アプリで、子ども向けではない、という位置づけを保つことが大事。

### 7.3 子どもが使うときの現実的な方法

1. **13歳以上で自分のアカウントがある場合**: そのままログインだけで使う（基本3スコープ）。カレンダー連携は本人が望むときだけ。ファミリーリンクで管理されていれば、初回に保護者の承認が要る可能性がある → **導入前に一度、子どものアカウントで試す**。
2. **13歳未満、または管理が厳しい場合**: 保護者がファミリーリンクでアプリへのアクセスを許可する。うまくいかなければ、カレンダー連携は使わず「ICS購読URL」（8章）で予定を見せる。
3. どちらでも、子どものデータは最小限にする（ログイン情報とアプリ内の記録だけ。カレンダーは読まない）。プライバシーポリシーに「13歳未満の利用は保護者の同意のもとで」と書く。

---

## 8. ICS（iCal）購読URL方式との比較

### 8.1 仕組み

アプリが `https://…/calendar/<推測できない長いトークン>.ics` で予定一覧（iCalendar 形式）を出し、利用者が Google カレンダーの「他のカレンダー」→「URLで追加」で登録する（[公開カレンダーを追加する](https://support.google.com/calendar/answer/37100?hl=ja)）。

### 8.2 長所・短所

| 観点 | ICS 購読URL | OAuth（calendar.app.created） |
|---|---|---|
| 審査・スコープ | 不要。Google のトークンも持たない | スコープの分類次第（2章・3章） |
| 方向 | アプリ → カレンダーの一方通行（読み取り専用） | 双方向（専用カレンダー内） |
| 反映の速さ | **遅い**。Google が URL を取りに来る頻度は公式に書かれていない（**未確認**）。第三者の説明では「数時間〜24時間程度」「手動で更新できない」 | 即時（API で書き込む） |
| 登録の手間 | **パソコンのブラウザでしか登録できない**（「Android、iPhone、iPad 向けの Google カレンダー アプリでは、カレンダーに登録できません」: 同ページ） | アプリのボタンから連携 |
| 他のカレンダーアプリ | Apple・Outlook なども購読できる | Google のみ |
| 安全性 | URL を知っている人は誰でも見られる → 長い乱数トークン、再発行ボタン、予定の題名に個人情報を書かない | トークンを暗号化して保持する責任 |
| 実装の重さ | 軽い（1つのルート＋ICSの組み立て） | 重い（トークン管理・同期・エラー処理） |

- 第三者の説明（公式ではない）: https://knowledge.cheqroom.com/helpcenter/how-often-does-google-update-its-calendar-feeds （「最大12時間」）、https://nocal.app/help/ics-and-subscriptions/why-subscribed-calendars-dont-update-instantly （「数時間、時に24時間程度」）。
- 逆向きの代替: Google カレンダーの各カレンダーには「iCal 形式の非公開URL（Secret address）」があり、利用者がこれをアプリに貼れば、OAuth なしでアプリが予定を読める。ただし「あなただけが知っているべきで、他人に教えないこと」とされ（[Share your calendar](https://support.google.com/calendar/answer/37648)）、組織の管理者が無効にしている場合もある。アプリが受け取るのは「その人の予定を全部読める鍵」なので、暗号化して保存し、解除で消す必要がある。Google のポリシー上、この使い方が問題ないかは**未確認**。

### 8.3 位置づけ

- ICS は「審査を避ける代わり」としては、反映の遅さのため**タスク管理の主役には向かない**（タスクの時刻を変えても数時間反映されない可能性）。
- 「Google 以外のカレンダーを使う人」「カレンダー連携の許可を出せない人（子どもなど）」向けの**補助手段**として置く価値がある。

---

## 9. Google Cloud 側の準備手順

### 9.1 gcloud でできること

```bash
# 学習用アプリ用と決めている Google アカウントでログインしてから
gcloud projects create <project-id> --name="<表示名>"
gcloud config set project <project-id>
gcloud services enable calendar-json.googleapis.com   # Calendar API の有効化
gcloud services list --enabled                         # 確認
```

- Calendar API のサービス名は `calendar-json.googleapis.com`（公式クイックスタートの有効化リンク `https://console.cloud.google.com/flows/enableapi?apiid=calendar-json.googleapis.com`: [Calendar API quickstart](https://developers.google.com/workspace/calendar/api/quickstart/php)）。
- 請求先アカウントの紐づけが要るかは**未確認**（通常の利用は追加費用なし、超過分の課金は2026年後半に予定: [quota](https://developers.google.com/workspace/calendar/api/guides/quota)）。

### 9.2 gcloud ではできないこと（コンソールで行う）

- **OAuth クライアント（Web application）は gcloud で作れない。**
  - `gcloud iap oauth-brands` / `gcloud iap oauth-clients` は IAP OAuth Admin API の一部で、**2025-01-22 から非推奨**。「新しい OAuth のブランドとクライアントは Google Cloud コンソールで作成・管理できる」（[IAP OAuth Admin API deprecation](https://docs.cloud.google.com/iap/docs/deprecations/migrate-oauth-client)）。もともと IAP 用。
  - `gcloud iam oauth-clients create` は **Workforce Identity Federation 用**（外部IdPの従業員を Google Cloud にログインさせる）で、「通常の OAuth 2.0 クライアントIDは APIs & Services > Credentials で管理」と明記（[Manage OAuth application integrations](https://docs.cloud.google.com/iam/docs/workforce-manage-oauth-app)）。
  - Google Auth Platform のヘルプにも、クライアント作成はコンソールの手順しか書かれていない（[Manage OAuth Clients](https://support.google.com/cloud/answer/15549257)）。

### 9.3 Google Auth Platform の各画面で入れる値

出典: [Get started with the Google Auth Platform](https://support.google.com/cloud/answer/15544987)、[Manage OAuth App Branding](https://support.google.com/cloud/answer/15549049)、[Manage App Audience](https://support.google.com/cloud/answer/15549945)、[Manage OAuth Clients](https://support.google.com/cloud/answer/15549257)、[Manage App Data Access](https://support.google.com/cloud/answer/15549135)、[Calendar API quickstart](https://developers.google.com/workspace/calendar/api/quickstart/php)。

1. **Get started（初回）**: アプリ名（Google や他社の名前と紛らわしくないもの）、ユーザーサポートメール（自分のアカウントか管理する Google グループ）、Audience は **External**（Internal は Workspace 組織内だけ）、連絡先メール、User Data Policy に同意 → Create。
2. **Branding**:
   - アプリのホームページ、プライバシーポリシー、利用規約のURL（外部向け本番アプリでは必須。同じドメインに置く）。
   - 承認済みドメイン（リダイレクトURIより先に登録）。
   - ロゴ: **最初は上げない**。外部向け本番アプリでは、ロゴの追加・変更は審査を通るまで同意画面に出ない（審査の対象になる）。上げるなら 1MB 以下、JPG/PNG/BMP、正方形（120×120 推奨）。
   - 開発者の連絡先メール。
3. **Audience**: 公開状態を **Testing → 「Publish app」で In production** にする（7日問題を避けるため）。Testing のうちはテストユーザー（最大100人）を登録する。
4. **Clients → Create client**: 種類 **Web application**、名前（例: `web-prod` と `web-local` を分ける）、**承認済みのリダイレクトURI**:
   - `https://<本番ドメイン>/auth/google/callback`（ログイン）
   - `https://<本番ドメイン>/google/calendar/callback`（カレンダー連携）
   - `http://localhost:<port>/auth/google/callback` ほか（開発用。localhost は HTTP 可）
   - JavaScript 生成元はサーバー側の流れでは不要。
   - **クライアントシークレットは作成時に一度だけ全体を表示・ダウンロードできる**。以後は末尾4文字だけ。シークレットは最大2つまで（入れ替え用）。**6か月使われないクライアントは自動削除**（30日前にメール）。削除後30日以内なら復元できる。
5. **Data Access → Add or remove scopes**: `openid`、`.../auth/userinfo.email`、`.../auth/userinfo.profile`、`https://www.googleapis.com/auth/calendar.app.created`。一覧に出なければ「Manually add scopes」に貼る。**ここで表示される分類（Non-sensitive / Sensitive）を記録する**（2章の未確認点を解消する）。第2段階で `calendar.events.owned.readonly` を足す。
6. **Verification Center**: 機密スコープの審査が必要になったときだけ使う（3.5）。

`.env` に入れる値（例）: `GOOGLE_CLIENT_ID`、`GOOGLE_CLIENT_SECRET`、`GOOGLE_REDIRECT_URI`（ログイン用の完全URL）、`GOOGLE_CALENDAR_REDIRECT_URI`（連携用の完全URL）。

---

## 10. アプリへの示唆

### 10.1 採用するスコープ

- **ログイン**: `openid` `email` `profile`（Socialite の既定のまま）。全員。
- **カレンダー連携（第1段階）**: `https://www.googleapis.com/auth/calendar.app.created` だけ。希望者のみ。
- **今日の予定の表示（第2段階・任意）**: `https://www.googleapis.com/auth/calendar.events.owned.readonly`（primary を含む「自分が所有するカレンダー」だけ読める、読み取り系で最も狭い）。登録している他人のカレンダーも必要になったときだけ `calendar.events.readonly` に広げる。
- `calendar`（全権）と `calendar.events` は使わない。

### 10.2 ログインと連携の流れ

1. 「Google でログイン」→ `/auth/google/redirect`（`prompt=select_account`、PKCE、state）→ `/auth/google/callback` で `sub` を照合し `users` を作成・更新 → ログイン。オフライン許可は求めない。
2. 設定画面「Google カレンダーとつなぐ」の前に、何をするか（専用カレンダー「〇〇」を作り、タスクを書き込む。ふだんの予定は見ない）を1画面で説明する。
3. `/google/calendar/redirect`（`calendar.app.created`、`access_type=offline`、`prompt=consent`、`include_granted_scopes=true`、`login_hint`、PKCE）→ `/google/calendar/callback`。
4. 戻りで ①`sub` がログイン中の人と同じ ②`approvedScopes` に `calendar.app.created` がある ③`refreshToken` がある、を確かめる。どれかが欠けたら保存せずにやり直しを案内。
5. 専用カレンダーを `calendars.insert`（タイムゾーン Asia/Tokyo）で作り、`calendar_id` を保存 → 既存タスクを一括で書き出すジョブを積む。
6. 「連携を解除」: 専用カレンダーを消すか選ぶ → revoke → ローカルの鍵を削除。
7. `invalid_grant` を受けたら「要再連携」にして同期を止め、画面上部で知らせる。

### 10.3 同期方式

- アプリが正。タスクの作成・変更・削除のたびにキューで専用カレンダーへ書き出す。
- イベントID＝タスクのUUID（ハイフン抜き32文字）。insert → 409 なら patch。`extendedProperties.private` に `app` と `taskId`。
- 予定の通知は Google カレンダーの `reminders.overrides`（popup）に任せる。
- 専用カレンダーの差分取り込みは syncToken で、「画面表示時」と「15分ごとのスケジュール」。410 なら全件同期し直し。Google 側での時刻変更はタスクに反映、削除は印を付けるだけ。
- 今日の予定（第2段階）は保存せず、`timeMin`/`timeMax`/`singleEvents=true`/`orderBy=startTime` で取得して数分キャッシュ。
- watch（プッシュ通知）は使わない。
- 補助として ICS 購読URL（長いトークン付き、再発行可能）を用意すると、連携できない人（子どもなど）や Google 以外のカレンダーの人にも予定を見せられる。

### 10.4 審査の方針

- プロジェクト作成直後に Data Access で `calendar.app.created` の分類を確かめ、記録する。
  - 非機密 → In production、審査なしで運用。
  - 機密 → In production・未審査で運用（個人利用、通算100人まで）。連携時の警告画面の写真付き説明を用意。
- Testing のままでは運用しない（7日で切れる）。
- ロゴは上げない（審査の対象になるため）。
- 利用者が100人に近づく、または一般公開するときに審査（独自ドメイン、同じドメインのプライバシーポリシー、Search Console の確認、英語の同意画面を映した YouTube 限定公開の動画）。公式は最大10日だが、数か月かかった報告もあるので余裕を見る。
- `cloudfront.net` のドメインで承認済みドメイン・リダイレクトURIが受け付けられるか最初に試す。難しければ独自ドメインを取る。
- OAuth クライアントは6か月使わないと自動削除されるので、運用を止めていた期間があれば最初にコンソールで確認する。

### 10.5 子どもの利用

- アプリは「大人（保護者・施術者など）向けの一般アプリ」として作る（子ども向けとしない）。
- 子どもはログインだけで使い始め、カレンダー連携は任意。導入前に子どものアカウントで一度ログインを試し、ファミリーリンクの承認が要るかを確かめる。
- 連携できない場合は ICS 購読URLで代替。
- プライバシーポリシーに年齢の扱い（13歳未満は保護者の同意のもとで）を書く。

### 10.6 実装に使うパッケージと版（2026-10-31 時点）

| パッケージ | 版 | 用途 |
|---|---|---|
| laravel/socialite | `^5.31`（最新 5.31.0） | ログインと連携の OAuth の流れ |
| google/apiclient | `^2.20`（最新 2.20.1、メンテナンスモード） | Calendar API の呼び出し、トークン更新、revoke |
| google/apiclient-services | `0.465.0` に固定＋`Composer::cleanup` で `Calendar` だけ残す | Calendar の型付きクラス |
| （自動で入る）google/auth | 1.55.x | トークン処理 |

### 10.7 コードの骨組み（クラスの分け方、1ファイル1役割）

```
app/
  Http/Controllers/Auth/
    GoogleLoginController.php          # redirect / callback（ログインだけ）
    LogoutController.php
  Http/Controllers/GoogleCalendar/
    ConnectController.php              # 連携の redirect / callback
    DisconnectController.php           # 連携解除
    TodayEventsController.php          # 第2段階：今日の予定
  Http/Controllers/CalendarFeedController.php   # ICS 購読URL（任意）
  Models/
    User.php                           # google_sub（unique）, name, email, avatar
    GoogleConnection.php               # token（encrypted:array）, granted_scopes, calendar_id,
                                       # sync_token, status, revoked_at, last_synced_at, last_error
  Services/Google/
    GoogleClientFactory.php            # Google\Client を設定込みで作る（client_id/secret, retry）
    GoogleTokenManager.php             # 期限前の更新、invalid_grant → 要再連携、保存
    CalendarConnector.php              # 連携時の検証（sub・スコープ・refreshToken）と保存、解除
    AppCalendarProvisioner.php         # 専用カレンダーの作成・存在確認・作り直し
    TaskEventMapper.php                # Task → Event の配列（純粋な変換。単体テストしやすい）
    Contracts/CalendarGateway.php      # upsertEvent / deleteEvent / listChanges / listToday
    GoogleCalendarGateway.php          # 上の実装（google/apiclient を使うのはここだけ）
  Jobs/
    PushTaskToGoogle.php               # 作成・変更（ShouldBeUnique、afterCommit）
    DeleteTaskFromGoogle.php
    PullAppCalendarChanges.php         # syncToken 差分、410 で全件同期
    BackfillTasksToGoogle.php          # 連携直後の一括書き出し
  Observers/TaskObserver.php           # 保存・削除でジョブを積む
  Exceptions/
    GoogleConnectionRevoked.php
    GoogleTokenRefreshFailed.php
config/
  services.php                         # google: client_id, client_secret, redirect
  google.php                           # スコープの定数、専用カレンダー名、タイムゾーン、再試行回数
database/migrations/
  …_add_google_sub_to_users_table.php
  …_create_google_connections_table.php     # token は TEXT
  …_add_google_sync_columns_to_tasks_table.php  # google_event_id, google_synced_at, google_sync_error
routes/web.php                         # auth/google/*, google/calendar/*
tests/
  Feature/Auth/GoogleLoginTest.php     # Socialite::fake
  Feature/GoogleCalendar/ConnectTest.php
  Unit/Google/TaskEventMapperTest.php
  Fakes/FakeCalendarGateway.php        # Google に繋がずに同期をテスト
```

- Google の API を直接呼ぶのは `GoogleCalendarGateway` と `GoogleTokenManager` だけにする。コントローラーとジョブはインターフェース越しに使う。
- 1ファイルが太らないよう、「変換（Mapper）」「通信（Gateway）」「鍵の管理（TokenManager）」「業務の流れ（Job）」を分ける。

---

## 未確認事項の一覧（次に確かめること）

1. `calendar.app.created`・`calendar.events.owned.readonly`・`calendar.calendarlist.readonly` の分類（Non-sensitive / Sensitive）→ Data Access 画面で確認。
2. `xxxx.cloudfront.net` を承認済みドメイン・リダイレクトURIに使えるか → コンソールで試す。
3. 未審査警告画面から先へ進む操作の正確な文言 → 実際の画面で確認。
4. ファミリーリンクで管理されたアカウントが、機密スコープを含む許可を出せるか、現在の承認画面の選択肢 → 子どものアカウントで試す。
5. Google カレンダーが購読URL（ICS）を取りに来る頻度（公式記載なし）。
6. 削除済みの予定と同じイベントIDで insert したときの動き。
7. CloudFront がオリジンに渡す転送ヘッダー（`X-Forwarded-Proto` 等）と Laravel の信頼するプロキシ設定の組み合わせ（AWS 側の調査で）。
8. Calendar API の利用に請求先アカウントが要るか（2026年後半の課金開始予定との関係）。

（出典は各節の本文中にURLで示した。非公式の情報はその都度「非公式」「参考」と明記した。）
