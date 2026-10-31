---
paths:
  - "docker-compose.yml"
  - "infra/**"
  - "**/.env.example"
  - "src/bootstrap/**"
  - "src/app/Providers/AppServiceProvider.php"
  - "src/app/Http/Middleware/**"
  - "docs/guide/*deploy*"
---

# Docker・本番（AWS EC2）・デプロイ

本番は EC2 `suiri-prod-ec2`（13.197.78.156）で動かす。手順書は `docs/guide/10-deploy-aws.md`（AWS）と `docs/guide/11-google-setup.md`（Google の本番設定）。**手順を変えたら、手順書とこのファイルの両方を直す。**

## 手元（Docker）

- 箱は4つ：`app`（PHP-FPM。Node.js も入っていて画面のビルドもここ）・`worker`（`schedule:work` と `queue:work`。カレンダーの書き出し・取り込みを片づける）・`web`（nginx）・`db`（MySQL 8.4）。アプリ本体は `src/`、コンテナの中では `/data`。
- コマンドは必ずコンテナ経由：`docker compose exec -T app php artisan ...`（ホストに PHP・Composer はない。`bin/hooks/guard-bash` が止める）。
- ブラウザは http://localhost:8086 、DB はホストの 127.0.0.1:3311。値はプロジェクト直下の `.env`（`WEB_LISTEN`・`DB_PORT`）。アプリの設定は `src/.env`。どちらも git に入れない（`bin/check` が止める）。
- Docker のネットワークは `10.232.0.0/24` を明示している（既定のアドレス帯がこのマシンでは埋まっているため。BSE は `10.231.0.0/24`）。
- データベースの入れ物を消すコマンド（`docker compose down -v`・`docker volume rm`）と、記録が消える `migrate:fresh` 系は使わない（`guard-bash` が止める）。
- PHP の設定 `infra/php/`（php.ini・`zz-security.conf`）、nginx の設定 `infra/nginx/default.conf` を変えたら箱を作り直す（`docker compose build app`・`docker compose restart web`）。

## 本番（決まったこと）

- 形は講座どおり：Route 53 → CloudFront（https）→ http(80) → EC2（Elastic IP）→ docker compose（web・app・worker・db）。東京リージョン。公開の住所は `https://suiri.agi-japan.com`。
- サーバー：EC2 は `suiri-prod-ec2`、住所（Elastic IP）は `13.197.78.156`、鍵は手元の `~/.ssh/suiri-prod-key.pem`、ユーザーは `ec2-user`、アプリの場所は `~/suiri-techo`。
- AWS の名前はすべて **`suiri-prod-<種類>`**（例 `suiri-prod-vpc`・`suiri-prod-sg`・`suiri-prod-ec2`・`suiri-prod-key`・`suiri-prod-cf`）。ウィザードが付けた長い名前は直す。
- CloudFront の後ろで動かすための設定（`bootstrap/app.php` の `trustProxies(at: '*')`、`AppServiceProvider` の本番だけ `URL::forceScheme('https')`、本番の HSTS）は消さない。80 番は CloudFront のプレフィックスリストに絞る（`trustProxies` の前提）。
- 本番の `src/.env`：`APP_ENV=production`・`APP_DEBUG=false`・`APP_URL=https://suiri.agi-japan.com`・`LOG_LEVEL=error`・`SESSION_SECURE_COOKIE=true`・`GOOGLE_REDIRECT_URI` は https の絶対 URL。直下の `.env` は `WEB_LISTEN=80`。
- 更新の手順（手順書の「更新するとき」と同じ）：`~/bin/suiri-backup.sh`（先にバックアップ）→ `git pull --ff-only` → `docker compose exec app composer install --no-dev --optimize-autoloader` → `php artisan migrate --force` → `npm ci` → `npm run build` → `php artisan optimize`（作り置きを作り直す）→ `docker compose up -d` → **`docker compose restart worker`**（worker は起動したときのコードで動き続けるので、`optimize` のあとに起動し直して新しいコードと5分・15分ごとの予定を読ませる）。本番で `migrate:fresh` や seed のやり直しをしない（`DemoSeeder` はローカル専用で、本番では動かない）。
- **初回だけの手順（ChatGPT・Claude とつなぐ窓口を初めて載せるとき）**：更新の手順の `migrate --force`（Passport の表ができる）のあとに1回だけ `docker compose exec -T app php artisan passport:keys`（窓口の通行証に署名する鍵の組を `src/storage/oauth-private.key`・`oauth-public.key` に作る。git に入らない）→ `php artisan optimize` → `docker compose restart worker`。鍵がないと、同意の画面や AI が通行証を受け取る所（`/oauth/authorize`・`/oauth/token`）がエラーになる。**`--force` は付けない**（鍵を作り直すと、出した通行証がすべて使えなくなり、つないでいる人が全員つなぎ直しになる）。鍵はバックアップの外なので、サーバーを作り直したら作り直してよい（つなぎ直しになるだけで、記録は消えない）。確かめ方は `docs/guide/12-ai-connect.md` の「本番で確かめる」。
- 変わった物で足すこと：`infra/php/` → 最初に `docker compose up -d --build`、`infra/nginx/default.conf` → `docker compose restart web`、`src/.env` → `optimize` → `restart worker`。
- worker の箱（`schedule:work` と `queue:work`）が片づける予定：カレンダーの取り込み（15分ごと `PullCalendarChanges`）と、期限の過ぎたキャッシュ（今日の予定）を消す `cache:prune-expired`（5分ごと）と、AI の窓口の通行証の掃除（毎日 `passport:purge`・`ai:prune-apps`）。worker が止まると、プライバシーポリシーの「今日の予定は5分で消す」も守れないので、更新のあとは `docker compose ps` で worker が Up か確かめる。
- 記録の大きさ：`docker-compose.yml` の `x-logging`（10MB × 5）。数字を変えたら `src/config/legal.php` の `access_log` も同じにする（ポリシーの本文が引く）。アプリのエラーの記録は `LOG_STACK=daily`・`LOG_DAILY_DAYS=90`（`legal.error_log_days`）。

## 本番で AI がしてよいこと（2026-10-31 に決まった範囲）

- オーナーは**このアプリのデプロイ（GitHub に上げたコードの反映）とサーバーの準備を AI に任せている**（2026-10-31）。
- 入り方は1つだけ：`ssh -i ~/.ssh/suiri-prod-key.pem ec2-user@13.197.78.156 "cd ~/suiri-techo && …"`。`bin/hooks/guard-bash` の `PROD_SSH` が通すのはこの鍵・ユーザー・住所の ssh だけで、ほかの住所・鍵・ユーザーと scp・rsync・SSM は止まる。
- してよいこと：
  - 見る：`docker compose ps`・`docker compose logs`・`curl`（`/up` や公開の住所）など
  - コードの反映：上の「更新の手順」（`~/bin/suiri-backup.sh` → `git pull --ff-only` → `composer install --no-dev --optimize-autoloader` → `php artisan migrate --force` → `npm ci && npm run build` → `php artisan optimize` → `docker compose up -d` → `docker compose restart worker`）と、`php artisan optimize:clear`・`docker compose up -d --build`・`docker compose restart`。artisan・composer・npm は `docker compose exec -T app …` で動かす
  - 招待：`php artisan invite:add`・`invite:list`・`invite:remove`
  - `php artisan passport:keys`（**初回だけ**。窓口の通行証に署名する鍵を作る。`--force` は付けない。上の「初回だけの手順」）
- 許した ssh の中でも `PROD_FORBIDDEN` が止めること：`.env` を読む・書く（`cat .env` など。中身が見える `tinker`・`config:show`・`env`・`printenv`・`docker compose config`・`docker inspect` も）、利用者の記録を読む `mysql`・`mysqldump`、記録が消える `migrate:fresh` 系・`db:wipe`・`db:seed`（`--seed`）、`docker compose down`（`-v` も）・`stop`・`rm`・`kill`、ボリューム・イメージの削除、`rm`、ファイルを手で直す（エディタ・`sed -i`・`tee`・`>`・`<` で手元のファイルを流す）、`git push`・`reset`・`checkout`・`clean` など履歴や作業中のファイルを書き換える git、`--ff-only` でない `git pull`、`key:generate`（初回は済んでいる）、`passport:keys --force`（鍵の作り直し。つないでいる人が全員つなぎ直しになる）、`chmod`・`chown`・`dnf`・`yum`・`reboot`・`shutdown`。
- 止められた操作が要るとき（`.env` を直す・パッケージを入れるなど）は、オーナーに頼む。フックを弱めて通さない。AWS の設定（セキュリティグループ・CloudFront など）も変えない。
- AWS の費用がかかる操作の前には、費用と `.env` の中身（秘密の値を除く項目）を先に確かめる。memo-app など、ほかのサーバーには触らない。

## 手順書に入れること（講座から足す・変える所）

`docs/guide/10-deploy-aws.md` に入れ済み：Budgets（月の上限とメール）・名前の表・80 番のプレフィックスリスト・スワップ 2GB・worker の箱が動いているかの確かめ・バックアップ（毎日・7日分。プライバシーポリシーの数字と同じ）・更新の手順・困ったとき（419・`redirect_uri_mismatch`）・片付け。Google の本番設定（OAuth クライアント・リダイレクト URI・Calendar API の有効化・同意画面の公開状態）は `docs/guide/11-google-setup.md`。
