---
paths:
  - "src/app/Mcp/**"
  - "src/app/Queries/Ai/**"
  - "src/app/Actions/Ai/**"
  - "src/app/Support/Ai/**"
  - "src/app/Providers/AiConnectServiceProvider.php"
  - "src/app/Http/Middleware/RejectForeignOrigin.php"
  - "src/app/Http/Controllers/Settings/RevokeAiAppController.php"
  - "src/app/Console/Commands/PruneAiApps.php"
  - "src/routes/ai.php"
  - "src/routes/web/ai.php"
  - "src/config/ai_connect.php"
  - "src/config/mcp.php"
  - "src/config/auth.php"
  - "src/database/migrations/*oauth*"
  - "src/resources/views/oauth/**"
  - "src/resources/views/components/settings/ai-*.blade.php"
  - "src/resources/views/components/case/ai-copy.blade.php"
  - "src/resources/views/legal/partials/ai-connect.blade.php"
  - "src/resources/js/parts/ai-copy.js"
  - "src/tests/Feature/Ai/**"
---

# ChatGPT・Claude とつなぐ窓口（読むだけの MCP）

アプリの中では AI を使わない。利用者が**自分の** ChatGPT・Claude から、推理手帳の記録を**読むだけ**の窓口（`POST /mcp`。MCP＝AI とアプリをつなぐ決まり）を置いている。AI は「ほかの見立て・確かめ方・まだ集めていない手がかり」の候補を出す役で、正誤は決めない。人間向けの説明は `docs/guide/12-ai-connect.md`、要件は `docs/design/requirements.md` の 4-11。

部品は Laravel 公式の laravel/mcp（窓口）と Laravel Passport（通行証＝アクセストークンを出す OAuth）。つなぐ流れは OAuth 2.1（認可コード＋PKCE S256）と動的クライアント登録（AI が自分で `POST /oauth/register` する）。ログインは今までどおり Google だけ。

## 壊さない決まり

1. **書き込みの道具を足さない。** 道具は読むだけの4つ（`list_cases`・`get_case`・`list_due_predictions`・`search_attic`。どれも `#[IsReadOnly]`）。答え合わせ・取り下げ・○×・見立ての状態・直す・消す・作る・タスク・カレンダー・設定の道具は置かない（当たり外れは本人が現実で確かめる。予想の文と確かさは保存後に変えない）。足したくなったら、コードより先に要件定義書の 4-11 を直す判断をオーナーに仰ぐ。`tests/Feature/Ai/ReadOnlyToolsTest` が道具の名前の一覧を確かめる。
2. **仕事の場面を見せない。** 見える事件の決まりは `App\Queries\Ai\VisibleCases` の1か所（本人の事件で `scene` が `work` でないもの）。予想・屋根裏の棚は「事件が見えるか」で絞る（棚の事件と結んでいない知識は見せる）。仕事の場面・他人の事件・ない番号は、どれも同じ文（`ai_connect.messages.case_not_found`）で返し、あるかどうかを漏らさない。窓口の説明にも「仕事の場面の事件は見えない（ないのではない）」と書く。
3. **見せ合う前の相棒の見立てを隠す。** 相棒モードで見せ合う前（`CaseFile::hidesHypotheses()`）は、`get_case` の見立てと予想を空にして `hidden_note` を付け、`list_cases` の見立てと予想の数を出さず、`list_due_predictions` にその事件の予想を出さない。写すボタンも見立てを写さない。
4. **戻り先は3つだけ。** `config/mcp.php` の `redirect_domains` は `https://chatgpt.com`・`https://claude.ai`・`https://claude.com` だけ（laravel/mcp の初めの値の `*`＝どこへでも戻せる に戻さない）。よその住所の自己登録は 400。足すときは、同意の画面の行き先の会社（`ai_connect.consent.companies`）とプライバシーポリシーの文も一緒に直す。`RejectForeignOrigin` もこの一覧を使う。
5. **通行証は1時間・更新の札は30日**（`ai_connect.tokens`。Passport の既定の1年にしない）。取り消したものと期限の切れたものは毎日消す（`routes/console.php` の `passport:purge`）。使われなかった自己登録は `ai:prune-apps` が7日で消す。数字はプライバシーポリシーが config から引くので、変えたら同意の画面と `docs/guide/12-ai-connect.md` も見直す。
6. **同意を飛ばさない・同意前の人に許可させない。** Passport の住所は `Passport::ignoreRoutes()` で止め、`routes/web/ai.php`（`auth`・`agreed`）と `routes/ai.php` に**同じルート名のまま**置き直す（laravel/mcp の案内と埋め込み禁止の札が名前で動くため）。`skipsAuthorization()` は上書きしない（自己登録の AI は持ち主がいないので、上書きすると全部の AI が同意なしで通る）。デバイスの許可（`$deviceCodeGrantEnabled`）は切る。同意の画面は自前の `views/oauth/authorize`（laravel/mcp の見本は外部の書体と回る動きを使うので使わない）。
7. **client という名前は OAuth の決まりの所だけ。** Passport の表（`oauth_clients`）・`client_id`・`Passport::client()` などの決まった名前だけ使ってよい（`bin/check` の `WORD_ALLOW` に理由つき）。自前のクラス・変数・画面の言葉では、つないでくる相手を「AI」「app」と呼ぶ（`ConnectedAppsQuery`・`RevokeAiAccess`・`$appId`）。
8. **道具は受付（コントローラー）と同じく薄く。** `DB::`・`Http::` を書かず、組み立ては `Queries/Ai/*`、取り消しは `Actions/Ai/RevokeAiAccess`。道具の説明文・窓口の説明・決まった文は `config/ai_connect.php`（説明文は「〜のときに使う。」で始め、最後に `tool_tail`＝正誤を判定しない一文。`bin/check` の使わない言葉の決まりもここに効く）。
9. **写すボタンはアプリから送らない。** 事件の画面の「AI に相談する用に写す」（`<x-case.ai-copy>`・`Support\Ai\ConsultText`）は、端末のクリップボードに写すだけ。仕事の場面の事件では出さない。頼み方の文に「正誤は判定しない・名前などの個人情報を書かない」を必ず入れる。
10. **ログ・書き出し・退会。** ログに本文と通行証を書かない。データの書き出し（`ExportUserData`）の `ai_connections` は名前・戻る先の住所・最後につながった日時だけで、通行証の番号は出さない。退会（`DeleteAccount`）は Passport の行（通行証・更新の札・引換券）を明示して消す（Passport の表には親を消すと子も消える外部キーがない）。

## 置き場所

| もの | 場所 |
|---|---|
| AI の側の住所（案内・自己登録・通行証・窓口） | `src/routes/ai.php`（laravel/mcp がミドルウェアのグループなしで読む。窓口は `RejectForeignOrigin`・`auth:api`・`CheckToken::using('mcp:use')`・`throttle:mcp`） |
| 利用者のブラウザで開く住所（同意の画面・つなぐのをやめる） | `src/routes/web/ai.php`（`auth`・`agreed`・`throttle:writes`） |
| 窓口と道具 | `src/app/Mcp/Servers/SuiriServer.php`・`src/app/Mcp/Tools/*`・`src/app/Mcp/Concerns/DescribedInConfig.php` |
| Passport の設定・回数制限 | `src/app/Providers/AiConnectServiceProvider.php`（`mcp`・`oauth`・`oauth-register`） |
| 画面 | 同意の画面 `views/oauth/authorize`（値は `Support\Ai\ConsentView`）・設定の枠 `components/settings/ai-panel`（`ai-app-row`・`ai-steps`）・写すボタン `components/case/ai-copy` |
| 言葉と数字 | `src/config/ai_connect.php`・`src/config/mcp.php` |
| テスト | `src/tests/Feature/Ai/*`（共通の準備は `Concerns/ConnectsAi`。外の AI には届かない） |

## 本番

- 通行証に署名する鍵は、本番で**初回だけ** `docker compose exec -T app php artisan passport:keys` で作る（`storage/` に置かれ、git に入らない。`--force` で作り直すと、つないでいる人が全員つなぎ直しになるので付けない）。手順は `.claude/rules/deploy.md` と `docs/guide/10-deploy-aws.md`。
- 手元から ChatGPT・Claude にはつなげない（AI の会社のサーバーから https で届く必要がある）。手元は機能テストで確かめ、本物は本番で確かめる（`docs/guide/12-ai-connect.md`）。
