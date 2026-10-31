![推理手帳](docs/images/cover.png)

# 推理手帳（Deduction Notebook）

シャーロック・ホームズのものの見方を、毎日の生活で練習して記録する手帳アプリです。
気になる出来事を **事件** にし、**手がかり** から **見立て** を2つ以上立て、「**もしそうなら** 〜が見えるはず」という予想に確かめる日を付けて Google カレンダーに入れ、**答え合わせ** で見立てを直します。
**観察 → 仮説 → 予想 → 確かめる → 振り返る** の手順を、タスク管理に組み込んだ形です。

- 公開の住所：**https://suiri.agi-japan.com**（一般公開）
- 使う人：子どもと一緒に論理的に考える練習をしている保護者とその子ども（相棒）、自分の見方を振り返りたい施術者など。Google アカウントがあれば、だれでも登録できます。設定（`.env` の `SUIRI_INVITE_ONLY=true`）を変えると、運営者がコマンドで名簿に入れたメールアドレスの人だけが新しく登録できる**招待制**に戻せます。

![今日の画面（スマホ）。記録のきっかけ、答え合わせ待ち、今日の予定とタスク、今日の練習、捜査中の事件](docs/images/screen-today.png)

## 主な機能

- **今日**：ログインした人の最初の画面。記録のきっかけ → 答え合わせ待ちの予想 → 今日の予定（Google カレンダー）とタスクを時刻順に → 今日の練習 → 捜査中の事件、の順に並びます。予定やタスクの行の「気になる」から、そのまま事件を作れます。
- **事件**：なぞ（「〜はなぜ？」）と、手がかり（見た／聞いた／なかった）・見立て・○×（手がかりが見立てに合うか）・予想を1画面で書きます。なぞと手がかり1つと見立て1つだけでも保存できます。上には「いまの一手」、決まった規則で出す問いかけ「ワトソンのひとこと」（AI ではありません）が出ます。
- **もしそうなら（予想）と答え合わせ**：予想には確かさ（50〜95％）と確かめる日を付け、確かめるタスクが自動でできます。**予想の文と確かさは保存後に書き換えられません**（後知恵を防ぐため）。答え合わせは 当たり・外れ・どちらとも言えない・前提が崩れた の4つで、外れたら「何を見落とした？」を選びます。
- **相棒モード**：同じ端末で、相棒と自分が互いに隠して見立てを書き、「見せ合う」で並べます。どの手がかりに ○ を付けたかの違いも並べて見せます。
- **タスク**：今日／これから／いつか／済み。予想時間とかかった時間を入れておくと、題名の似た過去の実績を見せ、ふりかえりで見積もりのずれを出します。
- **ふりかえり**：週ごとの数字（記録した事件・見立てを2つ以上立てた割合・答え合わせ・考えを変えた回数）、確かさの答え合わせ（確かさの区間ごとの当たった割合）、週の問い。外れは **ノーベリ帳**、わかった一般則（つまり？）と具体例（たとえば？）は **屋根裏の棚** にためます。続けた日数・順位・点数は作りません。休んでも何も減りません。
- **手引き**：ホームズの思考の10の技能、名推理カード23枚（手がかりを見て自分で推理を書いてから裏返す）、練習メニュー11種、演繹・帰納・アブダクションの説明。大人向けと子ども向けの言葉を切り替えられます。
- **Google カレンダー連携**（任意）：アプリ専用のカレンダー「推理手帳」を作り、期日のあるタスクを書き込みます。送るのはタスクの題名と日時と、アプリに戻るリンクと、どのタスクの予定かを見分ける番号（アプリの中の通し番号で、記録の中身ではありません）だけで、事件の本文は送りません。専用カレンダーで動かした日時は15分ごとにタスクへ取り込みます。
- **子ども向けの言葉・仕事の場面の言葉**：ふだんの場面を「親子」にすると、見立て →「かも」、ノーベリ帳 → まちがいノート のように言葉が変わります。仕事（施術）の場面では「見た・触れた・測った」「ご本人の言葉」の言い方になります。
- **自分のデータ**：すべて JSON でダウンロードでき、退会するとすべて消えます。自分の記録は自分にしか見えません（運営者が記録を読む画面もありません）。

## 言えること・言わないこと

- **言えること：** 観察 → 仮説 → 予想 → 確かめる → 振り返る、の手順を、自分の生活で練習して記録する手帳です。
- **言わないこと：** 使うと考える力がつく、といった練習の効果は約束しません（練習の効果がほかの場面にまで広がるかは、研究でも確かではないため）。画面でもコードでも使わない言葉は [言葉のルール](docs/design/words.md) の3にまとめています。
- **医療機器ではありません。** 病気や体の不調の判断には使えません。体の不調は医師に相談してください。施術者が使うときも、お客さまの記録には使わず、自分の見方・考え方の振り返りにだけ使います。
- ホームズの名前はアプリ名の主役にせず、作品の紹介としてだけ使っています。

## 技術

| 分類 | 使っているもの |
|---|---|
| アプリ | Laravel 13・PHP 8.4・MySQL 8.4 |
| 画面 | Blade（部品は Blade コンポーネント）・Tailwind CSS v4・Vite。書体は端末のもの（外部の書体・CDN・アクセス解析・広告は読み込まない）。ダークモード対応 |
| ログイン | Google だけ（Laravel Socialite）。一般公開が既定で、設定 `SUIRI_INVITE_ONLY=true` で招待制に切り替えられる |
| カレンダー | Google Calendar API を Laravel の HTTP クライアントで直接呼ぶ。書き出し・取り込みは後回しの仕事（キュー）で、`worker` の箱が片づける |
| 動かす場所 | 手元は Docker（`app`・`worker`・`web`・`db` の4つの箱）。本番は AWS（Route 53 → CloudFront → EC2 → Docker）。東京リージョン |
| 作り方の決まり | 太らない MVC（検証は FormRequest・権限は Policy・用事は Action・外とのやりとりは Service・一覧は Query）。1ファイルの行数の上限・使わない言葉・コメントの抜けを `bin/check` が機械で止める |
| テスト | PHPUnit（sqlite のメモリの中）。Google は偽物に替える |

アプリの中では AI を使っていません（ワトソンのひとことも入力の提案も決まった規則です）。つなぎたい人だけが、自分の ChatGPT・Claude から記録を読ませる窓口（読むだけ。仕事の場面の事件は見せない）を使えます（[12. ChatGPT・Claude とつなぐ](docs/guide/12-ai-connect.md)）。

## 手元で動かす（最短）

Docker Desktop を起動してから、プロジェクト直下で打ちます。このパソコンに PHP はないので、PHP・Composer・npm はすべて箱の中（`docker compose exec app …`）で動かします。1行ずつの意味は [8. 動かし方と運用](docs/guide/08-operations.md) の 8-1 にあります。

```bash
cp .env.example .env                 # DB_PASSWORD・DB_ROOT_PASSWORD を決めて書き、WEB_LISTEN を 8086 にする
docker compose up -d --build         # 4つの箱を作って起動する
docker compose exec app composer install
cp src/.env.example src/.env         # DB_PASSWORD を上と同じ値にする
docker compose exec app php artisan key:generate
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app php artisan migrate
git config core.hooksPath .githooks  # コミット前のチェックを有効にする（クローンしたら1回だけ）
```

http://localhost:8086 を開くとトップが出ます。ログインするには、`src/.env` に Google の鍵（`GOOGLE_CLIENT_ID`・`GOOGLE_CLIENT_SECRET`。作り方は [11. Google の準備](docs/guide/11-google-setup.md)）を書けば、自分の Google アカウントで登録できます（既定は一般公開）。招待制を試すときだけ、`src/.env` に `SUIRI_INVITE_ONLY=true` を書いて、自分を名簿に入れます（`invite:add`）。

```bash
docker compose exec app php artisan invite:add 自分のメールアドレス  # 招待制にしたときだけ要る
docker compose exec app php artisan db:seed --class=DemoSeeder   # 見本のデータ（任意。手元だけで動く）
docker compose exec -T app php artisan test --compact            # テスト
bin/check rules                                                  # 決まりのチェック
```

## ドキュメント

- **人間向け**：[docs/README.md](docs/README.md) — 図とスクリーンショットつきの解説書（解体新書）の目次。1つの HTML にまとめた [docs/guide.html](docs/guide.html) もあります（ダブルクリックで開くだけで読めます）。
- **作る人・AI 向けの設計書**：[要件定義書](docs/design/requirements.md)・[基本設計](docs/design/basic-design.md)・[データ設計](docs/design/data-model.md)・[言葉のルール](docs/design/words.md)・[部品説明書](docs/design/components.md)
- **AI 向け**：[CLAUDE.md](CLAUDE.md)（どの作業でも守ることと道案内の表。`AGENTS.md` は同じ中身）と `.claude/rules/*.md`（分野ごとの決まり。その分野のファイルを扱うときだけ読み込まれる）
- **作る前の調査**：[docs/research/README.md](docs/research/README.md)
- **本番に載せる**：[10. 本番（AWS）に載せる](docs/guide/10-deploy-aws.md)・[11. Google の準備](docs/guide/11-google-setup.md)

運営者の氏名・連絡先は、運営者が `src/config/legal.php` に書き込みます。
