# 推理手帳 — AI 向けガイド

ホームズのものの見方を、毎日の生活で練習して記録する手帳アプリ（英語の副題 Deduction Notebook）。**観察 → 仮説 → 予想 → 確かめる → 振り返る**の手順を、タスク管理に組み込む：気になる出来事を**事件**にし、**手がかり**（見た／聞いた／なかった）から**見立て**を2つ以上立て、「**もしそうなら**〜が見えるはず」という予想に確かめる日を付けて Google カレンダーの専用カレンダーに入れ、**答え合わせ**で見立てを直す。外れは**ノーベリ帳**、わかった一般則は**屋根裏の棚**へ。使う人は、子どもと一緒に練習する保護者・その子ども（相棒）・少数の施術者を想定。登録は**一般公開**（Google アカウントがあればだれでも）が既定で、`.env` の `SUIRI_INVITE_ONLY=true` で招待制（名簿の人だけ）に戻せる（`config('suiri.invite_only')`）。アプリの中では AI を使わない（ワトソンのひとことと入力の提案は決まった規則）。利用者が自分の ChatGPT・Claude から読むだけの窓口（MCP）でつなげる（AI は候補を出す役で、正誤は決めない）。Laravel 13 / PHP 8.4 / MySQL 8.4 / Docker、画面は Blade + Tailwind CSS v4 + Vite。

設計書は `docs/design/`（`requirements.md` 何を作るか・`basic-design.md` 画面と層・`data-model.md` 表・`words.md` 言葉）。迷ったら要件定義書に戻る。

## このファイルの使い方（読み込みの道案内）

ここには「どの作業でも守ること」だけを書く。分野ごとの決まりは `.claude/rules/` にあり、その分野のファイルを読んだり直したりしたときに自動で読み込まれる（各ファイルの先頭の `paths:`）。

| 作業 | 自動で読まれる決まり |
|---|---|
| 中心の流れ・データ・言葉（`src/app` `src/routes` `src/database` `src/config/suiri.php`・`watson.php`・画面） | `rules/domain.md`（言葉＝表＝コードの名前の対応・使わない言葉・壊さない決まり） |
| 層の分け方・置き場所・行数の上限・名前の付け方（Controller・Request・Action・Query・Service・部品） | `rules/architecture.md`（太らない MVC） |
| 画面の見た目・置き方・子ども向けの言葉を出す条件（`src/resources/views` `css` `js`） | `rules/ui-design.md`（3つの原則・AI が作りがちな見た目を避ける・見た目は Blade の Tailwind のクラスで書き `app.css` に独自のクラスを足さない）。作り直しの部品（いまやることのカード・進み具合・荷札・はんこなど）の見本と段ごとの色は `docs/design/ui-kit.md`、部品の一覧の正本は `docs/design/components.md` |
| 入力の提案・書き出し（AI なし。`src/app/Queries/Cases/*Suggestions*`・`src/app/Support/WordOverlap.php`・`config/suiri.php` の `suggest`・`components/case/suggest-chips`） | `rules/domain.md`（押すと入力欄に入るだけで保存は本人。言葉は `docs/design/words.md` の「提案・書き出し」） |
| 個人情報・注意書き・退会・データの書き出し・記録の消え方（モデル・移行・`Actions/Account`・`config/legal.php`・`views/legal`・招待の名簿やキャッシュのコマンド） | `rules/privacy.md` |
| ChatGPT・Claude とつなぐ読むだけの窓口（MCP・OAuth。`src/app/Mcp`・`src/routes/ai.php`・`src/routes/web/ai.php`・`src/config/ai_connect.php`・`config/mcp.php`・`Queries/Ai`・`Support/Ai`・同意の画面 `views/oauth`・設定の枠 `components/settings/ai-*`・写すボタン `components/case/ai-copy`） | `rules/ai-connect.md`（書き込みの道具を足さない・仕事の場面を見せない・見せ合う前の相棒の見立てを隠す・戻り先は3つだけ・通行証は1時間・client という名前は OAuth の決まりの所だけ）。見せるもの・見せないものは `rules/privacy.md` |
| Google ログイン・カレンダー連携（`Services/Google`・`Jobs`・`Observers/TaskObserver`・`Calendar`・`Auth`・`routes/console.php`・連携の前の説明 `views/calendar`） | `rules/google.md` |
| 手引きの教材（`src/database/data/guide/*.json`・`Support/Guide`・`views/guide`） | `rules/guide-content.md` |
| テスト（`src/tests`）・見本のデータ（`database/seeders/DemoSeeder.php`。ローカル専用） | `rules/tests.md` |
| コードのコメント（PHP・JS・Blade すべて） | `rules/code-comments.md` |
| Docker・本番・デプロイ（`docker-compose.yml`・`infra/`・`docs/guide/10-deploy-aws.md`） | `rules/deploy.md` |
| ドキュメント（`docs/` `README.md`・`tools/docs-html`） | `rules/docs.md` |
| 「機能 → ファイル → 関数」を引きたい | `docs/ai/codemap.md`（住所・表・config・仕事・壊さない決まり・テストの索引。必要な節だけ読む）。設計の考え方は `docs/design/basic-design.md` の 2（画面と住所と受付）と 5（主なクラス） |

## 決まりは機械でも確かめている（読むだけの決まりではない）

`.claude/rules/` の決まりのうち機械で判定できるものは、`bin/check` が確かめる。止められたら、出てきた理由と「→ 直し方」に従って直す（チェックを飛ばす・弱める方向に変えない。例外は `bin/check` の表に理由つきで書く）。
- コマンドの前（フック `bin/hooks/guard-bash`）：サーバーへの SSH（通すのは本番 EC2 へ決まった鍵で入る ssh だけ。その中でも `.env` を読む・書く、記録を消す・読む、箱やボリュームを消す、`rm`、`git push`・`reset` などは止める）・記録が消えるコマンド（`migrate:fresh` 系・`docker compose down -v`）・強制プッシュ・チェックを飛ばすオプション・`.env` の追加・ホストでの php/composer を止める
- ファイルを直した直後（`bin/hooks/after-edit`）：PHP は pint で整形し、決まりの違反（太らない MVC・使わない言葉・行数の上限・コメントの抜け・`app.css` の独自のクラス）を返す
- 作業を終える前（`bin/hooks/before-stop`）：直したファイルの決まり・秘密の値・画面のビルド・整形・テスト（・ドキュメント）
- コミットの前（`.githooks/pre-commit` → `bin/check staged`）：決まり・秘密の値・整形・テスト・ドキュメントのリンクと HTML 版。クローンしたら1回 `git config core.hooksPath .githooks`
- フックの設定は `.claude/settings.json`（このフォルダで Claude Code を起動したとき有効）。`bin/check` の決まりの一覧はファイルの中の表（`RULES`・`WORD_RULES`・`SIZE_LIMITS`・CSS の `CSS_OK_AT`）

## どの作業でも守ること

- 返答・コメント・ドキュメントは日本語。コードのコメントは「何のためか」を平易な日本語で書く。**クラス・関数の手前に何をするか、if・ループ・switch/match・try の手前に何を確かめてどう分かれるか（何を試して失敗したらどうするか）を必ず書く**（`rules/code-comments.md`）。
- 受付（コントローラー）は薄く：検証は FormRequest、権限は Policy、用事は Action、外とのやりとりは Service、一覧は Query、表示の部品は Blade コンポーネント。1ファイルの行数に上限がある（`rules/architecture.md`）。
- 言葉は `docs/design/words.md` の表に従う。医療の判断に見える言葉・効果を約束する言葉・他人（お客さま）を表す言葉を画面にもコードの名前にも使わない（`rules/domain.md`）。
- **他人の記録を持たない・見せない**：すべての記録は `user_id` で絞る。予想の文と確かさは保存後に書き換えない。カレンダーに送るのはタスクの題名・日時・アプリに戻るリンクと、どのタスクの予定かを見分ける番号（`suiri_task_id`）だけ。AI の窓口で見せるのは本人の記録のうち仕事の場面を除くものだけ（読むだけ）。ログに入力の本文を書かない（`rules/privacy.md`・`rules/google.md`・`rules/ai-connect.md`）。
- アプリ本体は `src/`（コンテナの中では `/data`）。`docker-compose.yml` と `infra/` はプロジェクト直下。コマンドは必ずコンテナ経由（ホストに PHP はない）：`docker compose exec -T app php artisan ...`
- ブラウザは http://localhost:8086 、DB はホストの 127.0.0.1:3311。ログインは Google だけ（テストでは偽物にする）。
- 設定値・文言は `src/config/suiri.php`（ワトソンは `watson.php`、決まりごとのページは `legal.php`、ChatGPT・Claude とつなぐ窓口は `ai_connect.php`）に集める。コードに数字や文言を直書きしない。`env()` は `config/` の中だけ。
- 外部のフォント・CDN・アクセス解析・広告は読み込まない。書体は端末の書体。
- 本番（AWS EC2 `suiri-prod-ec2`・13.197.78.156・https://suiri.agi-japan.com）へのデプロイ（GitHub に上げたコードの反映）とサーバーの準備は、オーナーが AI に任せている。入るのは `ssh -i ~/.ssh/suiri-prod-key.pem ec2-user@13.197.78.156`（アプリは `~/suiri-techo`）だけで、してよいのは見る・決まった更新の手順・`invite:*`・初回だけの `passport:keys`（窓口の通行証に署名する鍵を作る。`--force` は付けない）だけ（`rules/deploy.md` の「本番で AI がしてよいこと」）。`.env` を読む・書く、記録を消す・読む、箱やボリュームを消す、`rm`、`git push`・`reset`、`key:generate`、AWS の設定の変更はしない（`guard-bash` が止める。要るときはオーナーに頼む。招待制に切り替える `.env` の `SUIRI_INVITE_ONLY=true` もオーナーが書く）。
- 変更したら `php artisan test` と `vendor/bin/pint` を通す。画面を変えたら `npm run build` し、headless Chrome でスクリーンショットを撮って確かめる（スマホ幅 390px とダークモードも）。
- 運営者の体験や気持ちを作って書かない。運営者の氏名・連絡先は運営者が書く。

## よく使うコマンド

```bash
docker compose up -d                                   # 起動（web:8086 / db:3311 / worker：キューと5分・15分ごとの予定）
docker compose exec -T app php artisan test --compact  # テスト（sqlite in-memory。1つだけなら --filter=名前）
docker compose exec -T app vendor/bin/pint             # 整形
docker compose exec -T app npm run build               # 画面の CSS/JS をビルド（画面を変えたら毎回。並行作業中は flock /tmp/suiri-build.lock を前に付ける）
docker compose exec -T app php artisan migrate         # 表の追加・直し（本番でも migrate だけ）
docker compose exec -T app php artisan db:seed --class=DemoSeeder  # 見本のデータ（watson@example.com。ローカル専用で、本番では動かない）
docker compose exec -T app php artisan invite:add {email}          # 招待の名簿に足す（招待制にしたときだけ効く。invite:list・invite:remove も。管理画面は作らない）
docker compose restart worker                          # Job・予定のコードを変えたら（worker は起動したときのコードで動く）
bin/check rules [ファイル...]                          # 決まりのチェック（ファイルを渡さなければ全部）
bin/check docs                                         # ドキュメントのリンク・HTML 版の作り直し忘れ
node tools/docs-html/build.mjs                         # docs/guide.html を作り直す（docs を変えたら。初回は cd tools/docs-html && npm ci）
```
