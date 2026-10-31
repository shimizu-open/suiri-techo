# 11 Google の準備（ログインとカレンダー連携）

## この章でわかること

- 推理手帳が Google に何を頼むのか（ログインと、カレンダーの専用カレンダーへの書き込み・今日の予定の読み取り）
- `gcloud` コマンドでできる所（プロジェクトを作る・Calendar API を有効にする）と、Google Cloud のコンソール（ブラウザの画面）でしかできない所
- Google Auth Platform の4つの画面（Branding・Audience・Data Access・Clients）に入れる値。項目名は画面の英語のまま書きます
- 本番の公開状態（In production）にする理由と、未審査のときに利用者に出る確認画面
- もらった鍵（クライアント ID とシークレット）の扱い

> **使う Google アカウントは、このアプリのために決めた1つ（このプロジェクトでは学習用のアカウント）にそろえます。** 仕事用など、ほかのアカウントでは作りません。以下の `<学習用の Google アカウント>` は、そのアドレスに読みかえてください。

---

## 0. 全体の流れ

**役目：** Google に「推理手帳というアプリが、Google のログインとカレンダーを使います」と届け出て、アプリ用の名札と合言葉（クライアント ID とシークレット）をもらいます。

**たとえると：** 大きな施設（Google）の受付に、お店の名前・規約・連絡先を登録し、「お客さんの身分証を見せてもらってよいか」「お客さんの手帳の、うちの店のページにだけ書き込んでよいか」を届け出て、出入りの許可証をもらう作業です。

| 順 | やること | どこで | いつ |
|---|---|---|---|
| 1 | プロジェクト `suiri-prod` を作り、Calendar API を有効にする | `gcloud`（手元のパソコン） | いつでも |
| 2 | Google Auth Platform を始める（Get started） | コンソール | 1 のあと |
| 3 | Branding（同意画面に出る名前・ページ・ドメイン） | コンソール | 2 のあと |
| 4 | Audience（だれが使えるか）を確かめる | コンソール | 2 のあと |
| 5 | Data Access（求める許可＝スコープ） | コンソール | 1・2 のあと |
| 6 | Clients（OAuth クライアント `suiri-prod-web`）を作り、ID とシークレットをもらう | コンソール | 3 のあと |
| 7 | シークレットを `.env` に書き、控える | EC2・手元 | [10 章](10-deploy-aws.md) のステップ10 |
| 8 | 本番の公開状態（In production）にする | コンソール | `https://suiri.agi-japan.com` が開け、プライバシーポリシーの運営者の情報（【】）を埋めたあと |

**gcloud でできない理由：** Web アプリ用の OAuth クライアントを作るコマンドは、Google が用意していません（以前あった IAP 用のコマンドは 2025 年に非推奨になり、`gcloud iam oauth-clients` は別の用途のものです。[調査 R4 の 9章](../research/R4-google-integration.md)）。同意画面（Branding・Audience・Data Access）もコンソールで設定します。

**料金：** Google Cloud のプロジェクト・OAuth クライアント・Calendar API の通常の利用に、料金はかかりません。

---

## 1. gcloud でやる：プロジェクトを作り、Calendar API を有効にする

**役目：** 推理手帳の設定をまとめる箱（プロジェクト）を作り、その箱で Google カレンダーの API を使えるようにします。

**たとえると：** 新しい店のための書類棚を1つ用意し、「カレンダーの窓口を使います」という札を棚に貼ることです。ほかのアプリ（Pixel Office など）の棚とは分けます。

**なぜ専用のプロジェクトにするか：** 未審査のアプリは「利用者は通算100人まで」という上限があり（8章）、この数はプロジェクトごとに数えます。ほかのアプリと棚を分けておけば、上限も設定も混ざりません。

```bash
# ── 手元のパソコン（WSL）で ──
ACCOUNT=<学習用の Google アカウント>   # 例：you@example.com
PROJECT_ID=suiri-prod        # 「すでに使われている」と出たら suiri-prod-123456 のように数字を足して、以下も同じ値で進める

# 推理手帳専用の gcloud の設定を作って切り替える（仕事用の既定の設定を使わないため）
gcloud config configurations create suiri-prod
gcloud auth list                          # ACCOUNT がすでに並んでいれば、次の行の login は飛ばしてよい
gcloud auth login "$ACCOUNT"              # ブラウザが開くので、このアカウントでログインして許可する
gcloud config set account "$ACCOUNT"
gcloud config list                        # account = <学習用の Google アカウント> になっているか確かめる

# プロジェクトを作り、Calendar API を有効にする
gcloud projects create "$PROJECT_ID" --name="suiri-prod"
gcloud config set project "$PROJECT_ID"
gcloud services enable calendar-json.googleapis.com
gcloud services list --enabled --filter="config.name=calendar-json.googleapis.com"
```

| コマンド | 何をしているか |
|---|---|
| `gcloud config configurations create suiri-prod` | 「どのアカウント・どのプロジェクトを使うか」の組を、推理手帳専用に1つ作って切り替える。既定の組（仕事用）には手を付けない |
| `gcloud auth login` | このアカウントで gcloud を使う許可を出す |
| `gcloud projects create` | プロジェクトを作る。ID は世界で1つだけの名前で、あとから変えられない |
| `gcloud services enable calendar-json.googleapis.com` | Calendar API（Google カレンダーをプログラムから読み書きする窓口）を、このプロジェクトで使えるようにする |

**確かめ方：** 最後の行の表に `calendar-json.googleapis.com`（Google Calendar API）が出る。

- 作業が終わったら `gcloud config configurations activate default` で元の設定に戻します。次に推理手帳の作業をするときは `gcloud config configurations activate suiri-prod`。
- 有効にするときに「請求先アカウント（billing）が要る」と出るかどうかは未確認です（Calendar API の通常の利用は無料）。出たら、その画面の説明を読み、請求先を結びつけるかどうかはオーナーが決めます。

---

## 2. コンソール：Google Auth Platform を始める

**役目：** 同意画面（利用者がログインするときに出る「推理手帳があなたの情報を使います」の画面）の最初の設定をします。

**たとえると：** 受付に店の名前と連絡先を届け出る用紙を、最初に1枚書くことです。

ブラウザで次を開きます。右上のアカウントが `<学習用の Google アカウント>`、上のプロジェクトの欄が `suiri-prod` になっていることを確かめます。

`https://console.cloud.google.com/auth/overview?project=suiri-prod&authuser=<学習用の Google アカウント>`

（開けないときは、左上のメニュー → **Google Auth Platform**。プロジェクト ID に数字を足したときは、URL の `suiri-prod` も書き換えます。）

**Get started** を押して、次を入れます。

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| App Information → App name | アプリの名前（同意画面に出る） | `推理手帳` |
| App Information → User support email | 利用者向けの問い合わせ先（同意画面に出る） | `<学習用の Google アカウント>` |
| Audience | だれが使うか | **External**（Google アカウントを持つ人なら使える。Internal は会社の Google Workspace の中だけ） |
| Contact Information → Email addresses | Google からの連絡を受け取る先 | `<学習用の Google アカウント>` |
| Finish | 決まりへの同意 | **I agree to the Google API Services: User Data Policy** にチェック |

**Continue** → **Create**。

**なぜその値か：** 推理手帳の利用者（保護者・相棒・施術者など）は、それぞれ自分の Google アカウントでログインするので External です。推理手帳は一般公開（Google アカウントがあれば、だれでも登録できる）なので、ここで人を絞る必要もありません。登録できる人を絞りたくなったら、Google の側ではなくアプリの側で招待制に切り替えます（[10 章](10-deploy-aws.md) の 10-4「招待制にしたいとき」）。User support email は利用者に見えるので、見えてよいアドレスを選びます。

**確かめ方：** 左のメニューに **Overview**・**Branding**・**Audience**・**Clients**・**Data Access** が並ぶ。

---

## 3. Branding（同意画面に出る情報）

**役目：** 同意画面に出る名前と、ホームページ・プライバシーポリシー・利用規約の場所、使うドメインを登録します。

**たとえると：** 受付に置いてもらう、お店の案内カード（店名・規約の置き場所・住所）です。

左の **Branding** を開いて、次を入れます。

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| App name | アプリの名前 | `推理手帳` |
| User support email | 利用者向けの問い合わせ先 | `<学習用の Google アカウント>` |
| App logo | ロゴ | **上げない** |
| App domain → Application home page | ホームページ | `https://suiri.agi-japan.com` |
| App domain → Application privacy policy link | プライバシーポリシー | `https://suiri.agi-japan.com/privacy` |
| App domain → Application terms of service link | 利用規約 | `https://suiri.agi-japan.com/terms` |
| Authorized domains | 使ってよいドメイン | **Add domain** → `agi-japan.com` |
| Developer contact information → Email addresses | 開発者の連絡先 | `<学習用の Google アカウント>` |

**Save** を押します。

**なぜその値か：**

- **3つのページ：** 外向けに公開するアプリでは、ホームページ・プライバシーポリシー・利用規約のリンクが必要です。推理手帳には、ログインしなくても見られる `/privacy`・`/terms` があり、トップにもリンクがあります（Google はトップからプライバシーポリシーに行けるかを見ます）。
- **Authorized domains は `agi-japan.com`：** Google が求めるのは、登録できる形のドメイン（`suiri.agi-japan.com` ではなく `agi-japan.com`）です。戻り先（6章）より先に登録しておきます。
- **ロゴを上げない：** 外向けの本番アプリでは、ロゴを上げると審査の対象になり、審査が通るまで同意画面に出ません（[調査 R4 の 9章](../research/R4-google-integration.md)）。

**ページはまだ開けなくても保存できます。** ただし 8章で公開する前に、`https://suiri.agi-japan.com` が開け、`src/config/legal.php` の【】（運営者の氏名・連絡先）をオーナーが埋めておきます。

**確かめ方：** **Branding** を開き直して、値が残っている。

---

## 4. Audience（だれが使えるか）

**役目：** アプリの公開状態（テスト中か本番か）と、利用者の種類を確かめます。

**たとえると：** お店の入口の札が「準備中（関係者だけ）」か「営業中」かです。

左の **Audience** を開きます。

| 項目（画面の英語） | 日本語の意味 | この時点の値 |
|---|---|---|
| Publishing status | 公開状態 | **Testing**（作った直後はテスト中） |
| User type | 利用者の種類 | **External** |
| Test users | テスト中に使える人 | 足さなくてよい（8章で本番にするため） |

ここではまだ **Publish app** を押しません。本番にするのは 8章（サイトが開け、プライバシーポリシーの【】を埋めたあと）です。

**テスト中に手元で試したいとき：** **Test users** → **Add users** で自分のアドレスを足すと、テスト中でもその人だけログインできます（最大100人）。ただしテスト中のカレンダー連携の鍵は7日で切れます（8章）。

---

## 5. Data Access（求める許可＝スコープ）

**役目：** 推理手帳が Google に求める許可（スコープ）を、前もって登録します。登録していない許可を求めると、警告の画面になります。

**たとえると：** 「お客さんの身分証の名前とメールだけ見ます」「お客さんの手帳の、うちの店専用のページにだけ書きます」「今日の予定を読むだけです」と、頼みごとの一覧を受付に出しておくことです。

左の **Data Access** → **Add or remove scopes** を押し、次の5つを選びます。一覧に出ないものは、下の **Manually add scopes** の欄に URL を貼って **Add to table** を押します。最後に **Update** → ページの **Save**。

| スコープ（画面の表示） | 日本語の意味 | いつ求めるか |
|---|---|---|
| `openid` | Google のアカウントの番号（利用者を見分ける） | ログイン（全員） |
| `.../auth/userinfo.email` | メールアドレス | ログイン（全員） |
| `.../auth/userinfo.profile` | 名前とアイコン | ログイン（全員） |
| `https://www.googleapis.com/auth/calendar.app.created` | アプリが作った専用カレンダーだけを作る・読む・書く | 「カレンダーとつなぐ」を押した人だけ |
| `https://www.googleapis.com/auth/calendar.events.owned.readonly` | 自分のカレンダーの予定を読むだけ | 同上 |

**なぜこの5つか：** 許可は最小にします（[`.claude/rules/google.md`](../../.claude/rules/google.md)）。カレンダー全部を読み書きできる許可（`calendar`・`calendar.events`）は求めません。推理手帳は、つないだ人のために専用カレンダー「推理手帳」を作り、そこにタスクの題名と日時とリンクだけを書きます。メインのカレンダーには書きません。今日の予定は、きょうの画面に出すために読むだけで、保存しません。ログインとカレンダー連携は別々に許可をもらう（段階的な許可）ので、カレンダーをつながない人には、カレンダーの許可を求めません。

**記録しておくこと：** 保存したあと、画面でカレンダーの2つが **Your non-sensitive scopes**（機密でない）と **Your sensitive scopes**（機密）のどちらに入ったかを控えます（[調査 R4](../research/R4-google-integration.md) の未確認事項の1つめ）。機密に入ったものを求めると、未審査のあいだは利用者に確認画面が出ます（8章）。

**確かめ方：** **Data Access** の画面に5つが並んでいる。カレンダーの2つが一覧に出てこないときは、1章の Calendar API の有効化が済んでいるかを確かめます。

> スコープを足すときは、プライバシーポリシーと、連携の前にアプリが見せる説明も直します（[`.claude/rules/google.md`](../../.claude/rules/google.md)）。

---

## 6. Clients（OAuth クライアントを作る）

**役目：** 推理手帳のサーバーが Google に名乗るための名札（クライアント ID）と合言葉（クライアント シークレット）を作り、「ログインが終わったら、この住所にだけ戻してよい」と登録します。

**たとえると：** 出入りの許可証と、その暗証番号をもらい、「荷物の届け先はこの住所だけ」と届け出ることです。届け先の一覧にない住所には送らないので、他人のサイトにログインの結果を横取りされません。

左の **Clients** → **Create client** を押して、次を入れます。

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Application type | アプリの種類 | **Web application** |
| Name | 管理用の名前（利用者には見えない） | `suiri-prod-web` |
| Authorized JavaScript origins | ブラウザのプログラムから使う元 | 空のまま |
| Authorized redirect URIs | ログインのあとに戻してよい住所 | **Add URI** で下の4つ |

**Authorized redirect URIs に入れる4つ：**

| 住所 | 何の戻り先か |
|---|---|
| `https://suiri.agi-japan.com/auth/google/callback` | 本番のログイン |
| `https://suiri.agi-japan.com/calendar/callback` | 本番のカレンダー連携 |
| `http://localhost:8086/auth/google/callback` | 手元のパソコンで試すときのログイン |
| `http://localhost:8086/calendar/callback` | 手元のパソコンで試すときのカレンダー連携 |

**Create** を押すと、**Client ID** と **Client secret** が出ます。**シークレットの全体が見られるのは、このときだけです**（あとからは末尾の数文字しか出ません）。**Download JSON** で保存し、すぐに7章のとおり控えます。

**なぜその値か：**

- **Web application：** 推理手帳は、サーバーの側（Laravel）で Google とやりとりする形なので、この種類です。ブラウザのプログラムから直接使わないので、JavaScript origins は要りません。
- **戻り先は1字も違わないように：** `http` と `https`、末尾の `/` の有無、ポート番号が1つでも違うと、Google は `redirect_uri_mismatch` で止めます。本番の `src/.env` の `GOOGLE_REDIRECT_URI` は1つめと同じ値にします（[10 章](10-deploy-aws.md) のステップ10）。
- **localhost は http でよい：** 手元の住所（localhost）だけは、Google が http を認めています。手元の Docker は `http://localhost:8086` で動くので、同じ番号にします。本番と同じクライアントを手元でも使います（分けたくなったら、`suiri-local-web` のような別のクライアントを作り、手元の `src/.env` だけをそちらにします）。
- **カレンダー連携の戻り先 `/calendar/callback`：** 設計書（`docs/design/basic-design.md` の 2章）で決めた住所です。こちらは `.env` に書く項目がなく、アプリが今開いている住所から作ります（`ConnectCalendarController` の `route('calendar.callback')`）。本番では CloudFront が利用者の開いた名前をそのまま渡す設定（AllViewer。[10 章](10-deploy-aws.md) のステップ12）なので、`https://suiri.agi-japan.com/calendar/callback` になります。

**確かめ方：** **Clients** の一覧に `suiri-prod-web` が出て、開くと戻り先が4つ並んでいる。

---

## 7. シークレットの扱い

**役目：** もらったクライアント ID とシークレットを、アプリに渡し、なくさず、漏らさないようにします。

**たとえると：** お店の金庫の暗証番号です。金庫（`.env`）と、自分だけの控え帳（パスワードマネージャ）にだけ書き、壁には貼りません。

| どこに | 書くもの | 決まり |
|---|---|---|
| 本番の `src/.env`（EC2） | `GOOGLE_CLIENT_ID`・`GOOGLE_CLIENT_SECRET`・`GOOGLE_REDIRECT_URI` | `chmod 600`。書いたら `docker compose exec app php artisan optimize` |
| 手元の `src/.env` | `GOOGLE_CLIENT_ID`・`GOOGLE_CLIENT_SECRET`（`GOOGLE_REDIRECT_URI` は相対の `/auth/google/callback` のまま） | git に入れない（`.gitignore` 済み） |
| パスワードマネージャ | ID とシークレット（ダウンロードした JSON の中身） | 控えたら、ダウンロードフォルダの JSON は消す |

```
GOOGLE_CLIENT_ID=<Google からもらう>
GOOGLE_CLIENT_SECRET=<Google からもらう>
GOOGLE_REDIRECT_URI=https://suiri.agi-japan.com/auth/google/callback
```

- **git・チャット・スクリーンショットに入れない。** コミットの前のチェック（`bin/check`）は、Google のシークレットの形（`GOCSPX-` で始まる文字列）と `.env` を見つけたら止めます。
- **漏れたかもしれないとき：** **Clients** → `suiri-prod-web` → **Add secret** で新しいシークレットを作る → 本番の `src/.env` を書き換えて `optimize` → ログインできるのを確かめてから、古いシークレットを無効にして消します（シークレットは同時に2つまで持てます）。
- **6か月使われないクライアントは、Google が自動で消します**（30 日前にメールが届き、消えてから 30 日以内なら戻せます）。アプリを長く止めるときは気をつけます。

---

## 8. 公開する（In production にする）

**役目：** 公開状態を「テスト中」から「本番」に切り替え、利用者がふつうにログイン・カレンダー連携できるようにします。

**たとえると：** 入口の札を「準備中」から「営業中」に掛け替えることです。ただし、まだ Google の審査は受けていないので、カレンダーの許可を求めるときだけ「この店はまだ審査を受けていません」という注意書きが一度出ます。

**公開する前に：** `https://suiri.agi-japan.com` が開け（[10 章](10-deploy-aws.md) のステップ12）、`/privacy`・`/terms` に運営者の情報が入っていること（`src/config/legal.php` の【】をオーナーが埋めて反映済み）。

**手順：** 左の **Audience** → **Publishing status** の **Publish app** → 確認の画面で **Confirm**。**Publishing status** が **In production** になります。審査（Verification Center）の申し込みはしません。

### なぜテストのままにしないのか

テスト中のアプリが、基本の3つ（`openid`・メール・プロフィール）以外の許可を1つでも求めると、Google が渡す「つなぎ直さなくても使い続けられる鍵」（refresh token）は **7日で切れます**（[Google の説明](https://developers.google.com/identity/protocols/oauth2#expiration)）。推理手帳はカレンダーの許可を求めるので、テストのままだと、利用者は毎週カレンダーをつなぎ直すことになり、15分ごとの取り込みも止まります。本番にすれば、この7日の制限はなくなります（[調査 R4 の 3章](../research/R4-google-integration.md)）。

### 未審査のときに利用者に出る画面

| 場面 | 出る画面 |
|---|---|
| ログイン（`openid`・メール・プロフィールだけ） | ふつうの同意画面。審査は要らない |
| 「カレンダーとつなぐ」（カレンダーの許可） | 求める許可が機密（5章で控えた分類）なら、「Google はこのアプリを確認していません」という注意の画面が先に出る |

注意の画面では、**詳細（Advanced）** を開き、下に出る「推理手帳（または suiri.agi-japan.com）に移動（安全ではないページ）」のようなリンクで先に進みます。画面の正確な文言は未確認なので、初めてつなぐときに実際の画面で確かめ、「この画面が出たら、こう進んでください」を利用者に伝えます（手引きやお知らせに書くなど）。

**通算100人まで：** 未審査のまま、この注意の画面を通って許可した**新しい利用者は、プロジェクトの一生で通算100人まで**です。減らしたり、数え直したりはできません（[未審査のアプリ](https://support.google.com/cloud/answer/7454865)）。この注意の画面が出るのはカレンダーとつなぐときなので、数に入るのはカレンダーとつないだ人です（ログインは審査の要らない3つだけを求めます）。推理手帳は一般公開なので、カレンダーとつなぐ人がこの数に届くことがありえます。100人に近づく前に、審査を受けるか（独自のドメイン・プライバシーポリシー・説明の動画などが要ります）、招待制に切り替えて新しい人を絞るか（[10 章](10-deploy-aws.md) の 10-4）を決めます。

**確かめ方：** **Audience** の **Publishing status** が **In production**。自分のアカウントで `https://suiri.agi-japan.com` からログインでき、（カレンダー連携を作ったら）つないだ7日あとも、カレンダーに予定が入り続ける。

---

## 9. ひととおり確かめる

| 確かめること | 見方 |
|---|---|
| プロジェクトとアカウント | `gcloud config list` が `<学習用の Google アカウント>` と `suiri-prod` |
| Calendar API | 1章の最後の行の表に出る |
| Branding | 3つの URL と `agi-japan.com` が入っている |
| Data Access | 5つのスコープが並び、カレンダーの2つの分類を控えた |
| Clients | `suiri-prod-web` の戻り先が4つ |
| 鍵 | 本番の `src/.env` に ID とシークレット、パスワードマネージャにも控えがある |
| 公開状態 | **In production** |
| ログイン | 本番で「Google でログイン」→ 同意の画面 → 最初の画面に戻る |

---

## 10. 困ったとき

| 表示 | 原因と直し方 |
|---|---|
| `Error 400: redirect_uri_mismatch` | 6章の **Authorized redirect URIs** と、本番の `GOOGLE_REDIRECT_URI`（またはアプリが作る戻り先）が1字違う。`https` か、末尾の `/`、`suiri.agi-japan.com` の綴りを見比べる。`.env` を直したら `optimize` |
| `Error 403: access_denied`・「アクセスをブロック」 | 公開状態がテスト中で、その人が **Test users** にいない。8章で公開する |
| 「Google はこのアプリを確認していません」 | 未審査で機密の許可を求めたときの、想定どおりの画面。8章のとおり進む |
| 「招待制です」 | Google ではなく、アプリを招待制（`SUIRI_INVITE_ONLY=true`）にしていて名簿にない。[10 章](10-deploy-aws.md) の 10-4 の `invite:add`（一般公開に戻すなら `SUIRI_INVITE_ONLY=false`） |
| ログインのボタンが「準備中」 | `src/.env` の `GOOGLE_CLIENT_ID`・`GOOGLE_CLIENT_SECRET` が空、または `optimize` していない |
| カレンダーの許可が一覧に出ない | Calendar API が有効になっていない（1章）。または **Manually add scopes** に URL を貼る |
| 「連携が切れました」が毎週出る | 公開状態がテストのまま（7日で切れる）。8章で公開する |
| `gcloud` で別のアカウントが使われる | `gcloud config configurations activate suiri-prod` を忘れている。`gcloud config list` で確かめる |
| `gcloud projects create` で ID が使われている | 世界で1つだけの名前なので、`suiri-prod-123456` のように数字を足す |

---

## 11. 片付け（もう使わないとき）

| 順 | 消す物 | やり方 |
|---|---|---|
| 1 | OAuth クライアント `suiri-prod-web` | **Clients** → `suiri-prod-web` → **Delete**（消すと、もうだれもログインできない） |
| 2 | プロジェクト `suiri-prod`（まるごと消すなら） | `gcloud projects delete suiri-prod`（30 日のあいだは戻せる） |
| 3 | gcloud の設定 | `gcloud config configurations activate default` → `gcloud config configurations delete suiri-prod` |
| 4 | 控え | パスワードマネージャの ID とシークレットを消す |

利用者に前もって知らせるのは、AWS の片付けと同じです（[10 章](10-deploy-aws.md) の「片付け」）。

---

## 次に読む章

- AWS に載せる手順（`.env` に鍵を書く所はステップ10）：[10 本番（AWS）に載せる](10-deploy-aws.md)
- スコープ・審査・7日で切れる条件のくわしい調べ：[調査 R4](../research/R4-google-integration.md)
- Google 連携の決まり（スコープ・トークン・送る項目）：[`.claude/rules/google.md`](../../.claude/rules/google.md)
- 決めたこと（Google のスコープ・公開の住所）：[要件定義書 4-5・7章](../design/requirements.md)
