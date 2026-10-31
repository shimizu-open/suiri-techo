# 10 本番（AWS）に載せる

## この章でわかること

- 推理手帳が本番でどんな形で動くのか（Route 53 → CloudFront → EC2 → Docker の4つの箱）
- 講座（week6「EC2デプロイ①〜④」）のステップ0〜13の順に、何もない AWS に推理手帳を載せる手順
- 各ステップで、画面に入れる値（AWS の画面は英語なので、項目名は画面の英語のまま）と、同じことを `aws` コマンドでやる方法
- 本番の `.env`（2枚）に書く値と、初めて起動するときのコマンドの順番
- 料金の目安と、使いすぎに気づくための予算（Budgets）
- 更新のしかた・毎日のバックアップ・困ったとき・片付け

> この手順は、オーナーが手で進める前提で書いています。AI は本番の `.env`・AWS の設定・サーバーの設定には触りません（[`.claude/rules/deploy.md`](../../.claude/rules/deploy.md)）。Google の準備は [11 Google の準備](11-google-setup.md) に分けてあります。

---

## 全体の形

**役目：** これから作るものが、どうつながるかを先に見ておきます。

**たとえると：** 住所録（Route 53）で「推理手帳はどこ？」と引くと、受付カウンター（CloudFront）の場所が返ってきます。受付は鍵のかかった通信（https）でお客さんを迎え、奥の事務所（EC2）に用件を渡します。事務所の中には4人の係（4つの箱）がいます。

```
利用者のブラウザ
  │ https://suiri.agi-japan.com
  ▼
Route 53（agi-japan.com の住所録）  suiri → CloudFront（A エイリアス）
  ▼
CloudFront（suiri-prod-cf・証明書 suiri-prod-cert）  ← https を受ける
  │ http（80番）。行き先は suiri-origin.agi-japan.com → Elastic IP
  ▼
EC2（suiri-prod-ec2・t4g.small・Elastic IP suiri-prod-eip）
  │ 門番 suiri-prod-sg：80番は CloudFront からだけ、22番は自宅からだけ
  └ docker compose
      web（nginx・80番で受ける）→ app（Laravel）→ db（MySQL 8.4）
      worker（15分ごとのカレンダーの取り込み・後回しにした書き込み）→ Google カレンダー
  ※ すべて VPC suiri-prod-vpc（10.0.0.0/20）の suiri-prod-subnet-public-1a（10.0.0.0/24）の中
```

**中身：**

- **CloudFront**：インターネットからの入口です。https の証明書をここで持ち、EC2 には http で渡します。証明書の更新は AWS がしてくれます。
- **EC2**：AWS 上に借りる1台のサーバーです。手元と同じ `docker-compose.yml` を動かします。手元との違いは `.env` の2枚だけです。
- **4つの箱**：`web`（nginx）が受け、`app`（PHP）が画面を作り、`db`（MySQL）が記録をしまいます。`worker` は画面の外の仕事（15分ごとの取り込み・Google への書き込み）を片づけます。worker が止まっていても画面は動きますが、カレンダーが更新されなくなります。

### 作るものと名前

名前はすべて **`suiri-prod-<種類>`** にそろえます（要件定義書 7章）。画面のウィザードが長い名前を自動で付けたときは、作ったあとに必ずこの名前に直します。

| 作るもの | 名前 | 役目 |
|---|---|---|
| VPC | `suiri-prod-vpc` | このアプリ専用のネットワーク（区切った土地） |
| サブネット（4つ） | `suiri-prod-subnet-public-1a`・`suiri-prod-subnet-public-1c`・`suiri-prod-subnet-private-1a`・`suiri-prod-subnet-private-1c` | 土地の中の区画。講座と同じ4つ。EC2 を置くのは `public-1a` だけ |
| インターネットゲートウェイ | `suiri-prod-igw` | VPC とインターネットをつなぐ玄関 |
| ルートテーブル | `suiri-prod-rtb-public`・`suiri-prod-rtb-private` | 道案内の表。public は「外行きは玄関へ」、private は VPC の中だけ |
| セキュリティグループ | `suiri-prod-sg` | EC2 の前の門番 |
| キーペア | `suiri-prod-key` | EC2 に SSH で入る鍵（手元の `~/.ssh/suiri-prod-key.pem`） |
| EC2 | `suiri-prod-ec2` | アプリを動かすサーバー（ディスクは `suiri-prod-ebs`） |
| Elastic IP | `suiri-prod-eip` | 変わらない住所 |
| デプロイキー | `suiri-prod-deploy-key` | EC2 が GitHub からコードを読むだけの鍵 |
| 証明書（ACM） | `suiri-prod-cert` | https の身分証（バージニア北部で作る） |
| CloudFront | `suiri-prod-cf` | https の受付（名前の欄がないので Description と Name タグに書く） |
| 予算（Budgets） | `suiri-prod-budget` | 使いすぎたらメールで知らせる |

**サブネットの名前に `public-1a` のような区別を付ける理由：** 講座の配置どおりサブネットを4つ作るので、`suiri-prod-subnet` だけでは見分けがつきません。「外に出られるか（public／private）」と「どの建物か（1a／1c）」を後ろに足します。オーナーが Pixel Office で使った名前（`pixel-prod-subnet-public-1a` など）と同じ形です。

### 講座から変える所・足す所

講座の値をそのまま使い、変える所だけ理由を書きます（くわしい対応表は [調査 R6 の 3章](../research/R6-reuse-and-architecture.md)）。

| 場所 | 講座 | 推理手帳 | 理由 |
|---|---|---|---|
| ステップ5 の 80番 | 全世界（0.0.0.0/0） | **CloudFront のプレフィックスリストだけ** | アプリは「前にいる CloudFront の言うことを信じる」作り（ステップ13）。CloudFront を通らずに直接来られると困るため |
| ステップ5 の 22番 | マイ IP | 自宅の IP だけ（同じ） | — |
| ステップ6 のディスク | 20 GiB gp3 | 20 GiB gp3・**暗号化する** | 利用者の記録と Google の鍵が入るディスクなので、置いてある状態でも読めないようにする |
| ステップ8 | Docker・compose・buildx | ＋**スワップ 2GB**・cron・日本時間 | 画面のビルドでメモリが足りなくならないように。バックアップを決まった時刻に動かすため |
| ステップ10 | `.env` 2枚・起動 | ＋`LOG_*`・`SESSION_SECURE_COOKIE`・`GOOGLE_*`・画面のビルド・`optimize`・worker の確かめ・（招待制にしたいときだけ）名簿と `SUIRI_INVITE_ONLY` | 推理手帳に要る設定 |
| ステップ12 のオリジン | `origin.<ドメイン>` | `suiri-origin.agi-japan.com` | 同じドメインの下にほかのアプリ（Pixel Office の `pixel-origin`）もあるので、アプリ名を付ける |
| 講座になし | — | Budgets・バックアップ・Google の本番設定・片付けの手順 | 運用に要る |

---

## コマンドを使う前に（変数をまとめて決める）

**役目：** この章の `aws` コマンドを、そのままコピーして動くようにします。

**たとえると：** 料理の前に、使う材料を台の上に並べておくようなものです。

この章のコマンドは2か所で打ちます。**どこで打つかを毎回確かめます。**

| プロンプトの見た目 | どこ |
|---|---|
| `you@PC:~$` のような手元の名前 | 手元のパソコン（WSL）。`aws` コマンドはここで打つ |
| `[ec2-user@ip-10-0-0-xx ~]$` | EC2 の中。`docker compose` はここで打つ |

手元のパソコンの端末を開いたら、**最初に下の塊を貼ります**。途中で端末を閉じたら、もう一度貼れば、作ったものの ID が名前から引き直されます（まだ作っていないものは `None` と出ます）。

```bash
# ── 手元のパソコン（WSL）で、この章のコマンドの前に毎回貼る ──
AWS="aws --profile shimizu --region ap-northeast-1"   # 東京リージョンで操作する
AWS_US="aws --profile shimizu --region us-east-1"     # ACM（証明書）と Budgets だけはバージニア北部
DOMAIN=suiri.agi-japan.com              # 公開の住所
ORIGIN=suiri-origin.agi-japan.com       # CloudFront が EC2 を探すための裏の名前
MY_IP=$(curl -s https://checkip.amazonaws.com)   # 今つないでいる自宅の IP（SSH を許す相手）
BUDGET_USD=50                           # 月の予算（ドル）。アカウント全体の額（ステップ0-3を読んで決める）
BUDGET_EMAIL='<予算のお知らせを受け取るメールアドレス>'

# 作ったものの ID を、名前から引き直す
ZONE_ID=$($AWS route53 list-hosted-zones-by-name --dns-name agi-japan.com --query "HostedZones[?Name=='agi-japan.com.'].Id | [0]" --output text | sed 's|/hostedzone/||')
VPC_ID=$($AWS ec2 describe-vpcs --filters Name=tag:Name,Values=suiri-prod-vpc --query 'Vpcs[0].VpcId' --output text)
SUBNET_ID=$($AWS ec2 describe-subnets --filters Name=tag:Name,Values=suiri-prod-subnet-public-1a --query 'Subnets[0].SubnetId' --output text)
SG_ID=$($AWS ec2 describe-security-groups --filters Name=group-name,Values=suiri-prod-sg --query 'SecurityGroups[0].GroupId' --output text)
INSTANCE_ID=$($AWS ec2 describe-instances --filters Name=tag:Name,Values=suiri-prod-ec2 Name=instance-state-name,Values=pending,running,stopping,stopped --query 'Reservations[0].Instances[0].InstanceId' --output text)
EIP=$($AWS ec2 describe-addresses --filters Name=tag:Name,Values=suiri-prod-eip --query 'Addresses[0].PublicIp' --output text)
CERT_ARN=$($AWS_US acm list-certificates --query "CertificateSummaryList[?DomainName=='suiri.agi-japan.com'].CertificateArn | [0]" --output text)
CF_ID=$($AWS cloudfront list-distributions --query "DistributionList.Items[?Comment=='suiri-prod-cf'].Id | [0]" --output text)
echo "zone=$ZONE_ID vpc=$VPC_ID subnet=$SUBNET_ID sg=$SG_ID ec2=$INSTANCE_ID eip=$EIP cert=$CERT_ARN cf=$CF_ID"
```

- `AWS` と `AWS_US` は「いつも付ける部分」を短く書くための変数です。`$AWS ec2 ...` は `aws --profile shimizu --region ap-northeast-1 ec2 ...` と同じ意味になります。
- `BUDGET_EMAIL` の `<…>` は、自分のメールアドレスに書き換えてから貼ります。
- 出てきた ID や IP は、どこにも貼り出しません（このドキュメントにも書きません）。

---

## ステップ0：始める前の準備

### 0-1. 前提のチェック

**役目：** 途中で必ず詰まる原因を、先に取り除きます。

**たとえると：** 引っ越しの前に、鍵・住所・トラックがそろっているかを確かめる作業です。

| 確かめること | 確かめ方 |
|---|---|
| `aws` コマンドに、プロファイル `shimizu` でログインできている | `aws --profile shimizu sts get-caller-identity` でアカウントの情報が出る。「session has expired」と出たら `aws login --profile shimizu` |
| Route 53 に `agi-japan.com` のホストゾーンがある（Pixel Office と同じ） | 上の変数の塊で `zone=` に `Z` から始まる ID が出る |
| GitHub の `shimizu-open/suiri-techo` に、最新のコードが push してある | 手元で `git status` がきれいで、`git log origin/main -1` が手元と同じ。コミット前のチェック（`bin/check`）は手元で済ませてある |
| AWS のコンソールに IAM ユーザーで入れる | ルートユーザー（アカウントを作ったメールアドレス）は使わない |
| コンソールの右上のリージョンが **Asia Pacific (Tokyo) ap-northeast-1** | ACM（ステップ11）のときだけ US East (N. Virginia) に切り替える |
| Google の準備（[11 章](11-google-setup.md) の 1〜6） | ステップ10 の前に済ませておくと、`.env` を一度で書ける（あとからでもよい） |

「さっき作ったはずのものが一覧にない」ときは、まず右上のリージョンを疑います。

### 0-2. 料金の目安（2026-10-31 に確かめた値）

**役目：** 月にいくらかかるかを、作る前に知っておきます。

**たとえると：** 部屋を借りる前に、家賃・電気代・駐車場代の内訳を見ておくことです。

東京リージョンの公式の価格（AWS が公開している価格データ。2026-10-29 公開分を 2026-10-31 に確認）で、1か月＝730時間として計算しました。

| もの | 単価（東京） | 月の目安 |
|---|---|---|
| EC2（t4g.small） | 0.0216 USD／時間 | 15.77 USD |
| EBS（gp3・20 GiB） | 0.096 USD／GB・月 | 1.92 USD |
| パブリック IPv4（Elastic IP） | 0.005 USD／時間（付けていても、外して持っているだけでも同じ） | 3.65 USD |
| CloudFront | 毎月 1TB の転送と 1,000万回のリクエストまで無料（アカウント全体で） | 0 USD |
| Route 53 | ホストゾーン 0.50 USD／月。`agi-japan.com` はすでにあり Pixel Office と共用なので増えない。CloudFront へのエイリアスの問い合わせは無料、ふつうの問い合わせは 100万回で 0.40 USD | ほぼ 0 USD |
| ACM（証明書） | 書き出さない公開の証明書は無料 | 0 USD |
| Budgets | 知らせるだけの予算は無料 | 0 USD |
| EC2 からインターネットへの転送（Google への書き込みなど） | 少量（無料枠の範囲かは未確認） | ほぼ 0 USD |
| **合計** | | **約 21.34 USD／月**（1ドル＝150円なら約 3,200円。為替で変わる） |

**t4g.small の無料トライアルについて：** 2026年12月31日までは、t4g.small が月 750 時間まで無料になります（[EC2 の FAQ](https://aws.amazon.com/ec2/faqs/)）。ただし **750 時間はアカウントの中のすべての t4g.small の合計**です。同じアカウントで Pixel Office の `pixel-prod-ec2`（t4g.small）が1か月動き続けていると、それだけで 720〜744 時間を使うので、**推理手帳の EC2 にはほとんど無料が残りません**。上の表は、その前提（全額かかる）で書いています。

**出典（2026-10-31 に確認）：** [EC2 のオンデマンド料金](https://aws.amazon.com/ec2/pricing/on-demand/)・[EBS の料金](https://aws.amazon.com/ebs/pricing/)・[VPC の料金（パブリック IPv4）](https://aws.amazon.com/vpc/pricing/)・[CloudFront（Pay as you go）](https://aws.amazon.com/cloudfront/pricing/pay-as-you-go/)・[Route 53 の料金](https://aws.amazon.com/route53/pricing/)・[ACM の料金](https://aws.amazon.com/certificate-manager/pricing/)・[Budgets の料金](https://aws.amazon.com/aws-cost-management/aws-budgets/pricing/)。

**止め方：** いちばん高いのは EC2 です。しばらく使わない日は EC2 を Stop すると EC2 の分は止まります（ディスクと Elastic IP の分は残ります）。やめるときは「片付け」の順で消します。

### 0-3. 予算（Budgets）を作る

**役目：** 「月に○ドルを超えそうになったらメールで知らせて」という見張りを、お金がかかる物を作る前に置きます。

**たとえると：** 電気代が一定を超えたら鳴る警報です。AWS は使った分だけの後払いなので、消し忘れに請求書で初めて気づく、ということが起きます。それを防ぎます。

**先に決めること：** 予算は**アカウント全体の請求**で数えます（アプリごとに分けるには、タグの設定と1日の待ち時間が要るので、ここではしません）。このアカウントには Pixel Office なども載っているので、金額は「今の月の請求額＋推理手帳の約 22 USD＋余裕」にします。講座で作った予算（`memo-app-budget` など）がすでにあるなら、新しく作らずに、その金額を推理手帳の分だけ上げれば十分です。

**画面で入れる値**（右上のアカウント名 → **Billing and Cost Management** → 左の **Budgets** → **Create budget**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Budget setup | 作り方 | **Use a template (simplified)** |
| Templates | ひな形 | **Monthly cost budget** |
| Budget name | 予算の名前 | `suiri-prod-budget` |
| Enter your budgeted amount ($) | 月の予算額（ドル） | 上の「先に決めること」で決めた額 |
| Email recipients | 知らせる先 | 自分のメールアドレス |

**Create budget** を押します。ひな形では、実際の額が予算の 85%・100% を超えたときと、月末までの見込みが 100% を超えたときにメールが届きます。

**コマンドでやる場合**（手元のパソコン）

```bash
ACCOUNT_ID=$(aws --profile shimizu sts get-caller-identity --query Account --output text)
# すでにある予算を見る（あれば、新しく作らずに金額を上げるだけでよい）
$AWS_US budgets describe-budgets --account-id "$ACCOUNT_ID" --query 'Budgets[].[BudgetName,BudgetLimit.Amount]' --output table

cat > /tmp/suiri-budget.json <<EOF
{"BudgetName":"suiri-prod-budget","BudgetLimit":{"Amount":"$BUDGET_USD","Unit":"USD"},"TimeUnit":"MONTHLY","BudgetType":"COST"}
EOF
cat > /tmp/suiri-budget-notify.json <<EOF
[{"Notification":{"NotificationType":"ACTUAL","ComparisonOperator":"GREATER_THAN","Threshold":80,"ThresholdType":"PERCENTAGE"},
  "Subscribers":[{"SubscriptionType":"EMAIL","Address":"$BUDGET_EMAIL"}]},
 {"Notification":{"NotificationType":"FORECASTED","ComparisonOperator":"GREATER_THAN","Threshold":100,"ThresholdType":"PERCENTAGE"},
  "Subscribers":[{"SubscriptionType":"EMAIL","Address":"$BUDGET_EMAIL"}]}]
EOF
$AWS_US budgets create-budget --account-id "$ACCOUNT_ID" \
  --budget file:///tmp/suiri-budget.json \
  --notifications-with-subscribers file:///tmp/suiri-budget-notify.json
```

**なぜその値か：** 80%（講座と同じ）で知らせると、上限に届く前に気づけます。見込み（FORECASTED）の 100% は「このままだと月末に超える」という早めの知らせです。

**確かめ方：** Budgets の一覧に `suiri-prod-budget`（または金額を上げた既存の予算）が出ていること。

---

## ステップ1：VPC を作る（自分専用のネットワーク）

**役目：** AWS の中に、推理手帳だけの区切られたネットワークを作ります。

**たとえると：** 大きな土地の中に、壁で囲った自分の敷地を借りることです。ほかの人の敷地とは完全に分かれています。

**画面で入れる値**（検索窓に VPC → 左の **Your VPCs** → **Create VPC**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Resources to create | 何を作るか | **VPC only**（VPC だけ。中身は自分の手で作る） |
| Name tag - optional | 名前 | `suiri-prod-vpc` |
| IPv4 CIDR block | 住所の範囲の決め方 | **IPv4 CIDR manual input** |
| IPv4 CIDR | 住所の範囲 | `10.0.0.0/20` |
| IPv6 CIDR block | IPv6 の範囲 | **No IPv6 CIDR block** |
| Tenancy | 専用の機械を使うか | **Default** |

**コマンドでやる場合**

```bash
VPC_ID=$($AWS ec2 create-vpc --cidr-block 10.0.0.0/20 \
  --tag-specifications 'ResourceType=vpc,Tags=[{Key=Name,Value=suiri-prod-vpc}]' \
  --query 'Vpc.VpcId' --output text)
echo "$VPC_ID"
```

**なぜその値か：** `10.0.0.0/20` は「インターネットでは使わない内部用の番号」の中から、4,096 個分を使うという意味です（講座と同じ）。Pixel Office の VPC と同じ番号ですが、VPC 同士は完全に分かれているので、ぶつかりません。手元の Docker の `10.232.0.0/24` とも関係ありません。

**確かめ方：** **Your VPCs** の一覧に `suiri-prod-vpc` が出ていて、IPv4 CIDR が `10.0.0.0/20`。これより先は、選ぶ欄で**必ず `suiri-prod-vpc` を選びます**（Default VPC やほかのアプリの VPC も一緒に出てきます）。

---

## ステップ2：サブネットを切る（土地の区画）

**役目：** VPC の中を、外から触れる区画（public）と触れない区画（private）に分けます。

**たとえると：** 敷地の中を「表通りに面した店先」と「奥の倉庫」に区切ることです。建物（AZ＝データセンター）も2つに分けておきます。

**画面で入れる値**（左の **Subnets** → **Create subnet** → **VPC ID** で `suiri-prod-vpc` を選び、**Add new subnet** で4つ並べる）

| Subnet name（名前） | Availability Zone（建物） | IPv4 subnet CIDR block（住所の範囲） | 種類 |
|---|---|---|---|
| `suiri-prod-subnet-public-1a` | Asia Pacific (Tokyo) / ap-northeast-1a | `10.0.0.0/24` | public |
| `suiri-prod-subnet-public-1c` | Asia Pacific (Tokyo) / ap-northeast-1c | `10.0.1.0/24` | public |
| `suiri-prod-subnet-private-1a` | Asia Pacific (Tokyo) / ap-northeast-1a | `10.0.8.0/24` | private |
| `suiri-prod-subnet-private-1c` | Asia Pacific (Tokyo) / ap-northeast-1c | `10.0.9.0/24` | private |

作ったあと、**public の2つだけ**、一覧でチェック → **Actions** → **Edit subnet settings** → **Enable auto-assign public IPv4 address** にチェック → **Save**。

**コマンドでやる場合**

```bash
SUBNET_ID=$($AWS ec2 create-subnet --vpc-id "$VPC_ID" --cidr-block 10.0.0.0/24 --availability-zone ap-northeast-1a \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=suiri-prod-subnet-public-1a}]' --query 'Subnet.SubnetId' --output text)
SUBNET_1C=$($AWS ec2 create-subnet --vpc-id "$VPC_ID" --cidr-block 10.0.1.0/24 --availability-zone ap-northeast-1c \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=suiri-prod-subnet-public-1c}]' --query 'Subnet.SubnetId' --output text)
$AWS ec2 create-subnet --vpc-id "$VPC_ID" --cidr-block 10.0.8.0/24 --availability-zone ap-northeast-1a \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=suiri-prod-subnet-private-1a}]' --query 'Subnet.SubnetId' --output text
$AWS ec2 create-subnet --vpc-id "$VPC_ID" --cidr-block 10.0.9.0/24 --availability-zone ap-northeast-1c \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=suiri-prod-subnet-private-1c}]' --query 'Subnet.SubnetId' --output text
# public の2つだけ、置いたサーバーに外から届く住所を自動で付ける
$AWS ec2 modify-subnet-attribute --subnet-id "$SUBNET_ID" --map-public-ip-on-launch
$AWS ec2 modify-subnet-attribute --subnet-id "$SUBNET_1C" --map-public-ip-on-launch
```

**なぜその値か：**

- 講座と同じ配置です。public は 0 番台、private は 8 番台にしておくと、番号を見ただけでどちらか分かります。
- いま使うのは `public-1a` だけです。DB も EC2 の中の箱なので、private の2つは空のままです（この先、DB を分けるときの置き場所）。区画を作るだけなら料金はかかりません。
- 自動で付く住所は、止めて動かすたびに変わります。ステップ7 で変わらない住所（Elastic IP）に置き換えます。

**確かめ方：** **Subnets** の一覧で、`suiri-prod-subnet-` の4つが **Available**、VPC の列がすべて `suiri-prod-vpc`。

---

## ステップ3：インターネットゲートウェイとルートテーブル（玄関と道案内）

**役目：** 閉じた箱の VPC に、インターネットへの玄関を付け、「外行きは玄関へ」という道案内を書きます。

**たとえると：** 敷地に門を付けただけでは誰も通れません。「表通りへはこちら」という看板を立てて、初めて通れるようになります。看板を立てた区画が、結果として public と呼ばれます。

**画面で入れる値**

1. 左の **Internet gateways** → **Create internet gateway** → **Name tag** に `suiri-prod-igw` → 作成。続けて **Actions** → **Attach to VPC** → **Available VPCs** で `suiri-prod-vpc` → **Attach internet gateway**。
2. 左の **Route tables** → **Create route table**：

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Name - optional | 名前 | `suiri-prod-rtb-public` |
| VPC | どの VPC の表か | `suiri-prod-vpc` |

3. できた表の **Routes** タブ → **Edit routes** → **Add route**：**Destination**（行き先）`0.0.0.0/0`、**Target**（どこへ流すか）**Internet Gateway** → `suiri-prod-igw` → **Save changes**。
4. **Subnet associations** タブ → **Edit subnet associations** → `suiri-prod-subnet-public-1a` と `suiri-prod-subnet-public-1c` の**2つだけ**にチェック → **Save associations**。
5. 一覧に名前のない表がもう1つあります（VPC を作ると自動でできる「メインのルートテーブル」。Main の列が Yes）。Name の欄の鉛筆を押して `suiri-prod-rtb-private` と付けます。private の2つはこの表を使います。

**コマンドでやる場合**

```bash
IGW_ID=$($AWS ec2 create-internet-gateway \
  --tag-specifications 'ResourceType=internet-gateway,Tags=[{Key=Name,Value=suiri-prod-igw}]' \
  --query 'InternetGateway.InternetGatewayId' --output text)
$AWS ec2 attach-internet-gateway --internet-gateway-id "$IGW_ID" --vpc-id "$VPC_ID"

RTB_ID=$($AWS ec2 create-route-table --vpc-id "$VPC_ID" \
  --tag-specifications 'ResourceType=route-table,Tags=[{Key=Name,Value=suiri-prod-rtb-public}]' \
  --query 'RouteTable.RouteTableId' --output text)
$AWS ec2 create-route --route-table-id "$RTB_ID" --destination-cidr-block 0.0.0.0/0 --gateway-id "$IGW_ID"
SUBNET_1C=$($AWS ec2 describe-subnets --filters Name=tag:Name,Values=suiri-prod-subnet-public-1c --query 'Subnets[0].SubnetId' --output text)
$AWS ec2 associate-route-table --route-table-id "$RTB_ID" --subnet-id "$SUBNET_ID"
$AWS ec2 associate-route-table --route-table-id "$RTB_ID" --subnet-id "$SUBNET_1C"

# メインのルートテーブル（自動でできたもの）に名前を付ける
MAIN_RTB=$($AWS ec2 describe-route-tables --filters Name=vpc-id,Values="$VPC_ID" Name=association.main,Values=true \
  --query 'RouteTables[0].RouteTableId' --output text)
$AWS ec2 create-tags --resources "$MAIN_RTB" --tags Key=Name,Value=suiri-prod-rtb-private
```

**なぜその値か：** `0.0.0.0/0` は「ほかのどの行にも当てはまらない全部」＝インターネット行きです。最初から入っている `10.0.0.0/20 → local`（VPC の中同士）の行は消しません。private の区画をこの表に入れると「外に出られる区画」になってしまい、分けた意味がなくなるので、public の2つだけを関連付けます。

**確かめ方：**

- `suiri-prod-igw` の State が **Attached**。
- **Subnets** で `suiri-prod-subnet-public-1a` を選び、下の **Route table** タブに `0.0.0.0/0 → igw-…` の行がある。
- `suiri-prod-subnet-private-1a` を選ぶと、`suiri-prod-rtb-private` が付いていて `0.0.0.0/0` の行がない。

---

## ステップ4：キーペアを作る（サーバーに入る鍵）

**役目：** EC2 にパスワードではなく鍵ファイルで入るための、鍵の組を作ります。

**たとえると：** 鍵穴（公開鍵）は AWS が EC2 に取り付け、鍵そのもの（秘密鍵）は自分だけが持ちます。合う鍵を持っている人だけが入れます。

**画面で入れる値**（検索窓に EC2 → 左の **Network & Security** → **Key Pairs** → **Create key pair**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Name | 名前 | `suiri-prod-key` |
| Key pair type | 鍵の方式 | **ED25519** |
| Private key file format | 秘密鍵のファイルの形 | **.pem** |

**Create key pair** を押すと `suiri-prod-key.pem` がダウンロードされます。**この1回しかダウンロードできません。** すぐに手元の `~/.ssh/` に移します。

```bash
mkdir -p ~/.ssh
mv /mnt/c/Users/<Windows のユーザー名>/Downloads/suiri-prod-key.pem ~/.ssh/
chmod 400 ~/.ssh/suiri-prod-key.pem
```

**コマンドでやる場合**（ダウンロードの代わりに、直接 `~/.ssh/` に書き出す）

```bash
mkdir -p ~/.ssh
$AWS ec2 create-key-pair --key-name suiri-prod-key --key-type ed25519 --key-format pem \
  --tag-specifications 'ResourceType=key-pair,Tags=[{Key=Name,Value=suiri-prod-key}]' \
  --query 'KeyMaterial' --output text > ~/.ssh/suiri-prod-key.pem
chmod 400 ~/.ssh/suiri-prod-key.pem
```

**なぜその値か：** ED25519 は新しく、短くて安全な方式です（講座と同じ）。`chmod 400` は「自分だけが読める」にする設定です。ほかの人も読める状態だと、ssh が「この鍵は危ないので使いません」と断ります。

**確かめ方：** `ls -l ~/.ssh/suiri-prod-key.pem` が `-r--------` で始まる。コンソールの **Key Pairs** に `suiri-prod-key` がある。鍵ファイルは git にもチャットにも入れません（`bin/check` が `.pem` を止めます）。

---

## ステップ5：セキュリティグループを作る（サーバーの門番）

**役目：** EC2 の前に立つ門番に、「誰を、どの扉から通すか」を書きます。書いていない通信は全部断られます。

**たとえると：** 受付（CloudFront）から来た人だけを表口（80番）から通し、裏口（22番＝SSH）は自分の家の鍵を持つ人だけを通す、と警備員に頼むことです。

**画面で入れる値**（左の **Network & Security** → **Security Groups** → **Create security group**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Security group name | 名前 | `suiri-prod-sg` |
| Description | 説明（英数字だけ） | `suiri web from CloudFront only, SSH from owner home` |
| VPC | どの VPC か | `suiri-prod-vpc` |

**Inbound rules**（入ってくる通信）で **Add rule** を2回押します。

| Type（種類） | Port range（扉の番号） | Source（どこから） | 理由 |
|---|---|---|---|
| HTTP | 80 | **Custom** → 検索欄で **`com.amazonaws.global.cloudfront.origin-facing`**（プレフィックスリスト）を選ぶ | CloudFront からだけ届くようにする |
| SSH | 22 | **My IP**（今の自宅の IP が `<自宅のIP>/32` の形で入る） | サーバーの操作は自宅からだけ |

**Outbound rules**（出ていく通信）は初めのまま（すべて許可）。EC2 が GitHub・部品のダウンロード・Google の API に出るのに使います。**Create security group** を押します。

**コマンドでやる場合**

```bash
SG_ID=$($AWS ec2 create-security-group --group-name suiri-prod-sg \
  --description "suiri web from CloudFront only, SSH from owner home" --vpc-id "$VPC_ID" \
  --tag-specifications 'ResourceType=security-group,Tags=[{Key=Name,Value=suiri-prod-sg}]' \
  --query 'GroupId' --output text)
# CloudFront がオリジン（EC2）に来るときに使う住所の一覧（AWS が管理しているプレフィックスリスト）
CF_PL=$($AWS ec2 describe-managed-prefix-lists \
  --filters Name=prefix-list-name,Values=com.amazonaws.global.cloudfront.origin-facing \
  --query 'PrefixLists[0].PrefixListId' --output text)
$AWS ec2 authorize-security-group-ingress --group-id "$SG_ID" \
  --ip-permissions "IpProtocol=tcp,FromPort=80,ToPort=80,PrefixListIds=[{PrefixListId=$CF_PL,Description=CloudFront}]"
$AWS ec2 authorize-security-group-ingress --group-id "$SG_ID" \
  --ip-permissions "IpProtocol=tcp,FromPort=22,ToPort=22,IpRanges=[{CidrIp=$MY_IP/32,Description=owner-home}]"
```

**なぜその値か：**

- **80番を CloudFront だけにする（講座からの変更）：** アプリは、前にいる CloudFront が付ける「本当のアクセス元」の情報を信じる作りです（ステップ13 の `trustProxies`）。CloudFront を通らずに EC2 へ直接来られると、その情報を偽れてしまいます。プレフィックスリストは「CloudFront の住所の一覧」で、AWS が中身を最新に保ってくれます。
- **22番は自宅だけ：** 22番は世界中から常に試されています。全世界に開けると、すぐに自動の侵入の試みが始まります。
- **443番は開けない：** CloudFront から EC2 までは http（80番）なので要りません。
- このプレフィックスリストは、1行でも「ルール 55 行分」と数えられます（上限は 60）。このあとルールを増やして上限のエラーが出たら、それが理由です。

**確かめ方：** `suiri-prod-sg` の **Inbound rules** に、80（`pl-…`）と 22（`<自宅のIP>/32`）の2行だけがある。**自宅の回線が変わると SSH がつながらなくなる**ので、そのときは「困ったとき」の手順で 22番を直します。

---

## ステップ6：EC2 を起動する（サーバーを1台借りる）

**役目：** アプリを動かすサーバーを1台借ります。借りた瞬間から料金がかかります。

**たとえると：** 敷地の店先の区画に、建物を1つ建てることです。

**画面で入れる値**（左の **Instances** → **Launch instances**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Name and tags → Name | 名前 | `suiri-prod-ec2` |
| Application and OS Images (Amazon Machine Image) | OS のひな形 | **Quick Start** → **Amazon Linux** → **Amazon Linux 2023 AMI** |
| Architecture | CPU の種類 | **64-bit (Arm)** |
| Instance type | 大きさ | **t4g.small**（2 vCPU・メモリ 2GiB） |
| Key pair (login) → Key pair name | 入る鍵 | `suiri-prod-key` |
| Network settings → **Edit** → VPC | 置く VPC | `suiri-prod-vpc` |
| Subnet | 置く区画 | `suiri-prod-subnet-public-1a` |
| Auto-assign public IP | 外から届く住所を付ける | **Enable** |
| Firewall (security groups) | 門番 | **Select existing security group** → `suiri-prod-sg` |
| Configure storage | ディスク | **20** GiB、**gp3** |
| Configure storage → **Advanced** → Encrypted | 暗号化 | **Encrypted** |
| KMS key | 暗号の鍵 | **(default) aws/ebs** |
| Advanced details → Metadata version | サーバーの情報の取り方 | **V2 only (token required)**（初めのまま） |

右の **Summary** で **Number of instances** が 1 なのを確かめて **Launch instance**。

**コマンドでやる場合**

```bash
INSTANCE_ID=$($AWS ec2 run-instances \
  --image-id resolve:ssm:/aws/service/ami-amazon-linux-latest/al2023-ami-kernel-default-arm64 \
  --instance-type t4g.small --key-name suiri-prod-key \
  --subnet-id "$SUBNET_ID" --security-group-ids "$SG_ID" \
  --block-device-mappings 'DeviceName=/dev/xvda,Ebs={VolumeSize=20,VolumeType=gp3,Encrypted=true,DeleteOnTermination=true}' \
  --metadata-options HttpTokens=required \
  --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=suiri-prod-ec2}]' 'ResourceType=volume,Tags=[{Key=Name,Value=suiri-prod-ebs}]' \
  --query 'Instances[0].InstanceId' --output text)
echo "$INSTANCE_ID"
$AWS ec2 wait instance-status-ok --instance-ids "$INSTANCE_ID"   # 起動して検査に合格するまで待つ（数分）
```

`resolve:ssm:…` は「AWS が公開している、いちばん新しい Amazon Linux 2023（Arm 版）の番号」を引く書き方です。

**なぜその値か：**

- **Amazon Linux 2023・Arm・t4g.small（講座と同じ）：** AWS が保守している Linux で、`dnf` で部品を入れられます。メモリ 1GB（t4g.micro）だと、部品の取り込みや画面のビルドが途中で止まることがあるので 2GB にします。Arm の CPU なので、OS も Arm 版を選びます（x86 のままだと t4g が一覧に出ません）。推理手帳の箱（PHP・MySQL・nginx）と画面のビルドの道具は、Arm 用も用意されています。
- **20 GiB gp3（講座と同じ）：** 初めの 8 GiB だと、Docker のイメージとビルドの一時ファイルですぐ埋まります。gp3 は gp2 より安くて速い標準の SSD です。
- **暗号化（講座からの追加）：** ディスクには利用者の記録と、カレンダー連携の鍵（暗号化して保存していますが、その元になる `APP_KEY` も同じディスクの `.env` にあります）が入ります。ディスクそのものも暗号化しておくと、ディスクの写しが外に出ても読めません。AWS が用意する鍵（aws/ebs）なら追加の料金はかかりません。

**確かめ方：** 一覧の `suiri-prod-ec2` で、**Instance state** が **Running**、**Status check** が「2/2 checks passed」または「3/3 checks passed」（すべて合格）。合格する前は SSH できません。詳細の **Storage** タブで、ボリュームの **Encrypted** が Yes。

---

## ステップ7：Elastic IP を付ける（住所を固定する）

**役目：** 止めて動かしても変わらない住所を借りて、EC2 に付けます。

**たとえると：** 引っ越しても変わらない私書箱の番号です。CloudFront は、この番号を指す名前（ステップ12 の `suiri-origin`）で EC2 を探します。

**画面で入れる値**（左の **Network & Security** → **Elastic IPs** → **Allocate Elastic IP address**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Network border group | どの地域の住所か | `ap-northeast-1`（初めのまま） |
| Public IPv4 address pool | どこから借りるか | **Amazon's pool of IPv4 addresses**（初めのまま） |
| Tags → Name | 名前 | `suiri-prod-eip` |

**Allocate** のあと、その住所を選んで **Actions** → **Associate Elastic IP address**：**Resource type** は **Instance**、**Instance** は `suiri-prod-ec2`、**Private IP address** は出てくる1つ → **Associate**。

**コマンドでやる場合**

```bash
ALLOC_ID=$($AWS ec2 allocate-address --domain vpc \
  --tag-specifications 'ResourceType=elastic-ip,Tags=[{Key=Name,Value=suiri-prod-eip}]' \
  --query 'AllocationId' --output text)
$AWS ec2 associate-address --allocation-id "$ALLOC_ID" --instance-id "$INSTANCE_ID"
EIP=$($AWS ec2 describe-addresses --allocation-ids "$ALLOC_ID" --query 'Addresses[0].PublicIp' --output text)
echo "$EIP"
```

**なぜその値か：** 自動で付いた住所は、止めて動かすたびに変わります。変わると、CloudFront が EC2 を見失い、SSH の行き先も毎回調べ直しになります。Elastic IP は、付けていても外して持っているだけでも 1時間 0.005 USD かかるので、やめるときは必ず「解放」します（片付け）。

**確かめ方：** EC2 の詳細の **Public IPv4 address** が、いま付けた Elastic IP と同じ。この住所は手元の控えにだけ書き、ドキュメントやチャットには貼りません。

> AI に更新を任せるときは、この住所と鍵の名前が決まったあとで、AI に `bin/hooks/guard-bash` と `.claude/rules/deploy.md` の書き換えを頼みます（[`.claude/rules/deploy.md`](../../.claude/rules/deploy.md)「本番で AI がしてよいこと」）。

---

## ステップ8：SSH で入り、サーバーの準備をする

**役目：** EC2 の中に入り、Docker・compose・git などの道具をそろえます。

**たとえると：** 建てた建物に入って、電気・水道・作業台を用意することです。

**画面で入れる値：** なし（ここからはコマンドだけ）。

### 8-1. 入る

```bash
# 手元のパソコンで
ssh -i ~/.ssh/suiri-prod-key.pem ec2-user@$EIP
```

初めてのときは「この相手を信じますか（Are you sure you want to continue connecting）」と聞かれるので `yes`。プロンプトが `[ec2-user@ip-10-0-0-xx ~]$` に変われば、EC2 の中にいます。`ip-10-0-0-xx` の数字は、ステップ2 で決めた `10.0.0.0/24` の中の住所です。

### 8-2. 道具を入れる（EC2 の中で、1行ずつ）

```bash
sudo dnf update -y
sudo dnf install -y docker git cronie
sudo systemctl enable --now docker crond
sudo usermod -aG docker ec2-user
sudo timedatectl set-timezone Asia/Tokyo
exit
```

| コマンド | 何をしているか |
|---|---|
| `dnf update -y` | 入っている部品を最新にする（1〜2分） |
| `dnf install -y docker git cronie` | Docker・git・cron（決まった時刻にコマンドを動かす係。Amazon Linux 2023 には最初から入っていない）を入れる |
| `systemctl enable --now docker crond` | 今すぐ動かし、サーバーを再起動したあとも自動で動くようにする |
| `usermod -aG docker ec2-user` | `ec2-user` が `sudo` なしで docker を使えるようにする。**入り直したときに反映される**ので、最後に `exit` |
| `timedatectl set-timezone Asia/Tokyo` | サーバーの時計を日本時間にする（バックアップの時刻とログを日本時間で読めるように） |

### 8-3. 入り直して、compose と buildx を入れる

```bash
# 手元のパソコンで、もう一度入る
ssh -i ~/.ssh/suiri-prod-key.pem ec2-user@$EIP
```

```bash
# EC2 の中で
sudo mkdir -p /usr/local/lib/docker/cli-plugins
sudo curl -fSL "https://github.com/docker/compose/releases/latest/download/docker-compose-linux-$(uname -m)" \
  -o /usr/local/lib/docker/cli-plugins/docker-compose
ARCH=$(uname -m | sed 's/aarch64/arm64/;s/x86_64/amd64/')
VER=$(curl -fsSL https://api.github.com/repos/docker/buildx/releases/latest | grep '"tag_name"' | cut -d'"' -f4)
sudo curl -fSL "https://github.com/docker/buildx/releases/download/${VER}/buildx-${VER}.linux-${ARCH}" \
  -o /usr/local/lib/docker/cli-plugins/docker-buildx
sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose /usr/local/lib/docker/cli-plugins/docker-buildx
docker --version && docker compose version && docker buildx version
```

- `docker compose` は、4つの箱をまとめて動かす道具です。Amazon Linux 2023 の Docker には付いていないので、公式の配布物を置きます。
- `buildx` は、箱の設計図からイメージを組み立てる部品です。入っていない・古いと、ステップ10 で `compose build requires buildx 0.17.0 or later` と出て止まります（講座と同じ）。
- `$(uname -m)` は CPU の種類（t4g なら `aarch64`）に置き換わるので、Arm でも同じコマンドで済みます。

### 8-4. スワップを足す（メモリの足し）

```bash
sudo dd if=/dev/zero of=/swapfile bs=1M count=2048
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
free -h
```

**役目：** メモリが足りなくなったとき、ディスクの 2GB をメモリの代わりに使えるようにします。MySQL と PHP を同じ 2GB の1台で動かし、画面のビルドもここでするので、足りなくなって止まるのを防ぎます。最後の `echo` の行は「再起動したあとも使う」という設定です。

**確かめ方：** `docker compose version` と `docker buildx version` に版の番号が出る（buildx は 0.17 以上）。`free -h` の **Swap** の行が `2.0Gi`。`docker ps` が `permission denied` にならない（なったら `exit` して入り直す）。

---

## ステップ9：GitHub からコードを持ってくる（デプロイキー）

**役目：** EC2 に「このリポジトリだけを読める鍵」を作って GitHub に登録し、コードを取ってきます。

**たとえると：** 倉庫（GitHub）の合鍵を作るときに、「この棚だけ・見るだけ」の鍵にしておくことです。サーバーが乗っ取られても、ほかのリポジトリや書き込みには使えません。

### 9-1. 鍵を作る（EC2 の中で）

```bash
ssh-keygen -t ed25519 -C "suiri-prod-deploy-key" -f ~/.ssh/suiri_deploy -N ""
printf 'Host github.com\n  IdentityFile ~/.ssh/suiri_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
chmod 600 ~/.ssh/config
cat ~/.ssh/suiri_deploy.pub
```

- `-N ""` はパスフレーズなし（コマンドで自動で pull するため）。
- `~/.ssh/config` の3行は「github.com に行くときは、この鍵を使う」という指定です。

### 9-2. GitHub に登録する

**画面で入れる値**（GitHub の `shimizu-open/suiri-techo` → **Settings** → **Deploy keys** → **Add deploy key**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Title | 名前 | `suiri-prod-deploy-key` |
| Key | 公開鍵 | 上の `cat` で出た1行（`ssh-ed25519` から最後まで。改行を入れない） |
| Allow write access | 書き込みを許すか | **チェックしない** |

**コマンドでやる場合**（手元のパソコンで。`gh` が `shimizu-open` のリポジトリを扱えるアカウントでログインしている前提）

```bash
ssh -i ~/.ssh/suiri-prod-key.pem ec2-user@$EIP 'cat ~/.ssh/suiri_deploy.pub' > ~/suiri_deploy.pub
GH_TOKEN=$(gh auth token -u shimizu-open) gh repo deploy-key add ~/suiri_deploy.pub \
  --repo shimizu-open/suiri-techo --title suiri-prod-deploy-key
rm ~/suiri_deploy.pub
```

`gh repo deploy-key add` は、`--allow-write` を付けなければ読むだけの鍵になります。

### 9-3. clone する（EC2 の中で）

```bash
cd ~
git clone git@github.com:shimizu-open/suiri-techo.git
cd suiri-techo
ls
git log -1 --oneline
```

初めては `yes` と答えます。URL は `https://` ではなく `git@github.com:` で始まる形にします（デプロイキーは SSH でしか使えません）。

**確かめ方：** `ls` で `docker-compose.yml`・`infra`・`src` が見え、`git log` の一番上が手元の最新のコミットと同じ。`Permission denied (publickey)` なら、公開鍵の貼り付けの抜けがほとんどです。

---

## ステップ10：本番の .env を作り、起動する

**役目：** 本番だけの設定（パスワード・住所・Google の鍵）を書き、4つの箱を起動して、使える状態にします。

**たとえると：** 建物に家具（コード）を運び込んだあと、金庫の暗証番号と看板の住所を決め、係を配置する作業です。

`.env` は秘密の値が入るので git に入っていません。だから本番のサーバーの上で作ります。読む人が違うので2枚あります。

| ファイル | 読む人 | 決めること |
|---|---|---|
| `~/suiri-techo/.env` | docker compose | 箱の設定（DB の名前とパスワード・どの番号で受けるか） |
| `~/suiri-techo/src/.env` | Laravel | アプリの設定（本番の印・住所・ログ・DB のつなぎ先・Google の鍵） |

手元と本番の違いは、この2枚だけです。コードも `docker-compose.yml` も同じものが動きます。

### 10-1. Docker 用の `.env`

| 項目 | 値 | だれが決める | なぜ |
|---|---|---|---|
| `DB_DATABASE` | `suiri` | このまま | DB の名前 |
| `DB_USERNAME` | `suiri` | このまま | アプリが DB に入るときの名前 |
| `DB_PASSWORD` | 長いでたらめな文字列 | **自分で作る**（下のコマンド） | アプリが DB に入る合言葉。手元の値を使い回さない |
| `DB_ROOT_PASSWORD` | 別の長いでたらめな文字列 | **自分で作る** | DB の管理者の合言葉。バックアップでも使う |
| `WEB_LISTEN` | `80` | このまま（ひな形も 80） | CloudFront が 80番に来る |
| `DB_PORT` | `3306` | 変える（ひな形は 3311） | 手元ではほかの MySQL とぶつからないよう 3311 にずらしていた。EC2 にはほかにないので標準の番号に戻す。どちらでも `127.0.0.1` だけで待つので、外からは見えない |

```bash
# EC2 の中で
cd ~/suiri-techo
cp .env.example .env
cp src/.env.example src/.env
chmod 600 .env src/.env
DB_PW=$(openssl rand -hex 24)
ROOT_PW=$(openssl rand -hex 24)
sed -i -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PW|" -e "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$ROOT_PW|" \
       -e "s|^WEB_LISTEN=.*|WEB_LISTEN=80|" -e "s|^DB_PORT=.*|DB_PORT=3306|" .env
```

- `openssl rand -hex 24` は、48 文字のでたらめな文字列を作ります。記号を含まないので、`.env` や URL の中で崩れません。
- `chmod 600` は「自分だけが読み書きできる」にする設定です。

### 10-2. Laravel 用の `src/.env`

| 項目 | 値 | だれが決める | なぜ |
|---|---|---|---|
| `APP_NAME` | `推理手帳` | このまま | 画面とタブに出る名前 |
| `APP_ENV` | `production` | 変える | 本番の印。https の URL を作る設定と HSTS（ステップ13）が働く |
| `APP_KEY` | （コマンドで作る） | 10-3 の `key:generate` | ログイン状態と Google の鍵を暗号化する鍵 |
| `APP_DEBUG` | `false` | 変える | エラーの中身（設定値やコード）を利用者に見せない。本番の鉄則 |
| `APP_URL` | `https://suiri.agi-japan.com` | 変える | このアプリの住所。末尾に `/` を付けない |
| `LOG_LEVEL` | `error` | 変える | エラーだけを残し、ディスクを使いすぎない |
| `LOG_STACK` | `daily` | 変える | ログを1日1ファイルに分ける |
| `LOG_DAILY_DAYS` | `90` | 足す | 90 日より古いアプリのエラーの記録を消す。プライバシーポリシーの「アプリのエラーの記録は 90 日で消す」（`src/config/legal.php` の `error_log_days`）とそろえる。アクセスの記録（nginx）は日数ではなく、`docker-compose.yml` の記録の大きさの上限（10MB × 5）で古いものから消える |
| `DB_HOST` | `db` | このまま | つなぐ先は docker compose の箱の名前 |
| `DB_PORT` | `3306` | このまま | 箱の中の MySQL の番号。直下の `.env` の `DB_PORT` と名前は同じでも意味が違う |
| `DB_DATABASE`・`DB_USERNAME` | `suiri` | このまま | 直下の `.env` と同じ |
| `DB_PASSWORD` | 直下の `.env` と**同じ値** | 自分で作った値を写す | 違うと `Access denied` で止まる |
| `SESSION_SECURE_COOKIE` | `true` | 足す | ログイン状態のクッキーを https のときだけ送る |
| `SESSION_DRIVER`・`QUEUE_CONNECTION`・`CACHE_STORE` | `database` | このまま | ログイン状態・後回しの仕事・取り置きを DB の表にしまう（worker は表から仕事を取り出す） |
| `MAIL_MAILER` | `log` | このまま | 推理手帳にはメールを送る機能がない |
| `GOOGLE_CLIENT_ID` | Google の値 | **Google からもらう**（[11 章](11-google-setup.md)） | Google に「推理手帳からのログインです」と名乗る |
| `GOOGLE_CLIENT_SECRET` | Google の値 | **Google からもらう** | 名乗りが本物だと示す合言葉。人に見せない |
| `GOOGLE_REDIRECT_URI` | `https://suiri.agi-japan.com/auth/google/callback` | 変える | Google のログインから戻ってくる先。https で始まる完全な URL にする（相対のままでもアプリが作れるが、完全な URL がいちばん確実） |
| `SUIRI_INVITE_ONLY` | `false` | このまま | 一般公開（Google アカウントがあれば、だれでも登録できる）。`true` にすると招待制（10-4） |

```bash
# EC2 の中で（10-1 の続き。DB_PW が残っている端末で打つ）
sed -i \
  -e "s|^APP_ENV=.*|APP_ENV=production|" \
  -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
  -e "s|^APP_URL=.*|APP_URL=https://suiri.agi-japan.com|" \
  -e "s|^LOG_LEVEL=.*|LOG_LEVEL=error|" \
  -e "s|^LOG_STACK=.*|LOG_STACK=daily|" \
  -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PW|" \
  -e "s|^GOOGLE_REDIRECT_URI=.*|GOOGLE_REDIRECT_URI=https://suiri.agi-japan.com/auth/google/callback|" \
  src/.env
grep -q '^LOG_DAILY_DAYS=' src/.env || echo 'LOG_DAILY_DAYS=90' >> src/.env
grep -q '^SESSION_SECURE_COOKIE=' src/.env || echo 'SESSION_SECURE_COOKIE=true' >> src/.env
```

**ログの日数の注意：** `LOG_DAILY_DAYS=90` で消えるのは Laravel のログ（アプリのエラーの記録）です。nginx のアクセスの記録（`docker compose logs web` で見えるもの）は、`docker-compose.yml` の `x-logging` で「1ファイル 10MB × 5個まで」と大きさで消える決まりなので、利用者が少ないと長く残ることがあります。プライバシーポリシーもこの実態どおり「大きさの上限を超えた古いものから自動で消える」と書いてあります（`src/config/legal.php` の `access_log`）。`x-logging` の数字を変えたら、`access_log` も同じにします。

Google の2つの値は、手で書き込みます（`vim src/.env` → `i` で書き始め → `Esc` → `:wq` で保存）。

```
GOOGLE_CLIENT_ID=<Google からもらう>
GOOGLE_CLIENT_SECRET=<Google からもらう>
```

まだ Google の準備が済んでいなければ、空のままで先に進めます（ログインのボタンが「準備中」になるだけです）。あとで書いたら `docker compose exec app php artisan optimize` を打ちます。

**確かめ方：**

```bash
grep -E '^(APP_ENV|APP_DEBUG|APP_URL|LOG_LEVEL|LOG_STACK|LOG_DAILY_DAYS|SESSION_SECURE_COOKIE|GOOGLE_REDIRECT_URI|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME)=' src/.env
[ "$(grep '^DB_PASSWORD=' .env)" = "$(grep '^DB_PASSWORD=' src/.env)" ] && echo "DB_PASSWORD は2枚で同じ"
```

### 10-3. 初めての起動（順番と理由）

EC2 の中の `~/suiri-techo` で、上から1行ずつ打ちます。

```bash
docker compose up -d --build
docker compose ps
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan passport:keys
docker compose exec app php artisan optimize
docker compose restart worker
```

| 順 | コマンド | 何をしているか | なぜこの順か |
|---|---|---|---|
| 1 | `up -d --build` | `infra/` の設計図から PHP と MySQL の箱を作り、4つの箱を起動する。初回は数分 | すべての土台。このあとのコマンドは app の箱の中で動く。この時点の worker は、部品も表もないので失敗と再起動をくり返すが、7 のあとで落ち着く |
| 2 | `composer install --no-dev --optimize-autoloader` | Laravel 本体と Google ログインの部品（Socialite）を `src/vendor/` に入れる。`--no-dev` はテスト用の部品を入れない、`--optimize-autoloader` はクラスの置き場所の索引を先に作る | `vendor/` は git に入っていない。これがないと `artisan` が動かない |
| 3 | `npm ci` → `npm run build` | 画面の CSS（Tailwind）と JavaScript を `src/public/build/` に書き出す | `public/build/` も git に入っていない。ないと 500 エラー（`Vite manifest not found`） |
| 4 | `key:generate` | `APP_KEY` を作って `src/.env` に書き込む | 暗号化の鍵がないとアプリが動かない。5・6 より前に作る。**サーバーごとに1回だけ**（作り直すと全員がログアウトになり、暗号化して保存した Google の鍵も読めなくなる） |
| 5 | `migrate --force` | DB に表を作る（ログイン状態・後回しの仕事・取り置きの表も） | `--force` は本番で確認の質問を省くため。表を消して作り直す系のコマンドは使わない |
| 6 | `passport:keys` | ChatGPT・Claude とつなぐ窓口の「通行証」（AI に渡す許可の証拠）に署名する鍵の組を `src/storage/oauth-private.key`・`oauth-public.key` に作る（[12章](12-ai-connect.md)） | 5 で Passport の表ができてから。git に入らないので、サーバーごとに1回。**`--force` は付けない**（作り直すと、つないでいる人が全員つなぎ直しになる） |
| 7 | `optimize` | 設定・住所の表・画面のひな形を作り置きして速くする（`config:cache` など） | `.env` を読み終えてから作る。**このあと `.env` を変えたら、もう一度 `optimize`** |
| 8 | `restart worker` | worker の箱を起動し直す | 1 の時点では表も鍵もなかったので、そろった状態で起動し直し、15分ごとの取り込みと後回しの仕事を確実に動かす |

**書き込みの権限について：** 講座では `chmod -R guo+w storage bootstrap/cache` が要りましたが、推理手帳の app の箱は `ec2-user` と同じ番号（1000）の利用者で動くので、clone したファイルにそのまま書き込めます。500 エラーでログに `Permission denied` が出たときだけ、講座のコマンドを打ちます。

**`.env` を控える：** ここまで終わったら `cat .env src/.env` で全文を出し、パスワードマネージャに控えます（スクリーンショットやチャットには貼らない）。git に入っていないので、サーバーが壊れると一緒に消えます。特に `APP_KEY` をなくすと、暗号化して保存した Google の鍵は二度と読めません。

**確かめ方：**

```bash
docker compose ps                  # web・app・worker・db の4つが Up（db は healthy）
curl -i http://localhost/up        # HTTP/1.1 200 が返る（Laravel の「元気ですか」の窓口）
docker compose logs --tail 20 worker   # エラーをくり返していない
```

ブラウザではまだ開けません（80番は CloudFront からだけ。CloudFront はステップ12 で作ります）。

### 10-4. 招待制にしたいとき（いまは不要）

**役目：** 推理手帳は、既定では **一般公開**（Google アカウントがあれば、だれでも登録できる）です。登録できる人を絞りたくなったときだけ、**招待制**（運営者が名簿に入れたメールアドレスの人だけが新しく登録できる形）に切り替えます。一般公開のまま使うなら、この節は飛ばしてかまいません。

**たとえると：** ふだん開けている入口に、名簿を持った受付を立てることです。名簿にないメールアドレスの人には「招待制です」と出ます。もう登録した人は、名簿に関係なくそのまま入れます。

**手順（オーナーが EC2 の中の `~/suiri-techo` で）：**

1. 先に、自分と、使い続けてほしい人のメールアドレスを名簿に足しておきます（切り替えたあと、名簿にない新しい人は登録できなくなるため）。

```bash
# メールアドレスとメモを書き換えて打つ
docker compose exec app php artisan invite:add you@example.com --note="保護者"
docker compose exec app php artisan invite:list
```

2. `src/.env` の `SUIRI_INVITE_ONLY` を `true` にします（行がなければ足す）。`.env` は AI が触らない決まりなので、オーナーが書きます。

```bash
grep -q '^SUIRI_INVITE_ONLY=' src/.env \
  && sed -i 's|^SUIRI_INVITE_ONLY=.*|SUIRI_INVITE_ONLY=true|' src/.env \
  || echo 'SUIRI_INVITE_ONLY=true' >> src/.env
docker compose exec app php artisan optimize   # 作り置きの設定を読み直す（しないと切り替わらない）
```

- `--note` は運営者のメモ（例「保護者」「相棒」）で、画面には出ません。
- 外すときは `docker compose exec app php artisan invite:remove you@example.com`（これから新しく登録できなくなるだけで、登録済みの人の記録は消えません）。
- 一般公開に戻すときは、`SUIRI_INVITE_ONLY=false` にして、もう一度 `optimize` を打ちます。名簿はそのまま残り、また招待制にしたときに使えます。
- 切り替えると、トップのボタンの下の一文と、利用規約の「登録できる人」の文も自動で変わります（招待制のときだけ「招待制です」と書く）。

**確かめ方：** `invite:list` の表に足したメールアドレスが出る。トップ（`https://suiri.agi-japan.com`）のボタンの下に「招待制です。」が出る。名簿にないアカウントで試すと「推理手帳はいま招待制です」と出る。

---

## ステップ11：ACM で証明書を作る（https の身分証）

**役目：** 「`suiri.agi-japan.com` の持ち主は確かにこの人です」という証明書を、無料で発行してもらいます。

**たとえると：** 身分証の発行所（ACM）に申し込み、住所録（Route 53）に指定の合言葉を書いて「この住所を管理しているのは自分です」と示すことです。

**画面で入れる値**（右上のリージョンを **US East (N. Virginia) us-east-1** に切り替える → 検索窓に Certificate Manager → **Request a certificate** → **Request a public certificate** → **Next**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Fully qualified domain name | 証明する住所 | `suiri.agi-japan.com` |
| Allow export | 証明書を外に書き出せるようにするか | **Disable export**（書き出せる証明書は有料） |
| Validation method | 持ち主の確かめ方 | **DNS validation - recommended** |
| Key algorithm | 鍵の方式 | **RSA 2048**（初めのまま） |
| Tags | 名前 | Key `Name`、Value `suiri-prod-cert` |

**Request** のあと、一覧で証明書を開き、**Create records in Route 53** → `suiri.agi-japan.com` にチェック → **Create records**。数分で **Status** が **Pending validation** から **Issued** に変わります。終わったら、リージョンを東京に戻します。

**コマンドでやる場合**

```bash
CERT_ARN=$($AWS_US acm request-certificate --domain-name "$DOMAIN" --validation-method DNS \
  --key-algorithm RSA_2048 --tags Key=Name,Value=suiri-prod-cert --query CertificateArn --output text)
sleep 15   # 確かめ用のレコードの中身が用意されるまで少し待つ
read VNAME VVALUE < <($AWS_US acm describe-certificate --certificate-arn "$CERT_ARN" \
  --query 'Certificate.DomainValidationOptions[0].ResourceRecord.[Name,Value]' --output text)
cat > /tmp/suiri-acm.json <<EOF
{"Changes":[{"Action":"UPSERT","ResourceRecordSet":{"Name":"$VNAME","Type":"CNAME","TTL":300,"ResourceRecords":[{"Value":"$VVALUE"}]}}]}
EOF
$AWS route53 change-resource-record-sets --hosted-zone-id "$ZONE_ID" --change-batch file:///tmp/suiri-acm.json
$AWS_US acm wait certificate-validated --certificate-arn "$CERT_ARN"   # Issued になるまで待つ
```

**なぜその値か：**

- **バージニア北部で作る：** CloudFront が使えるのは us-east-1 の証明書だけです。東京で作ると、CloudFront の選ぶ欄に出てきません（講座と同じ）。
- **DNS 検証：** Route 53 に指定の CNAME（合言葉）を書けた＝住所を管理している、という確かめ方です。レコードを残しておけば、ACM が自動で更新してくれます。
- Pixel Office の証明書（`pixel.agi-japan.com`）とは別に、推理手帳だけの証明書を作ります。片方を片付けても、もう片方に響かないようにするためです。

**確かめ方：** ACM（us-east-1）の一覧で `suiri.agi-japan.com` が **Issued**。

---

## ステップ12：CloudFront と Route 53（https の受付と住所録）

**役目：** CloudFront を EC2 の前に置き、`https://suiri.agi-japan.com` で開けるようにします。

**たとえると：** 表通りに受付カウンターを置き、住所録に「推理手帳はこの受付」と書くことです。受付は身分証（証明書）を持って https でお客さんを迎え、奥の事務所には裏口の名前（`suiri-origin`）で用件を渡します。

### 12-1. CloudFront が EC2 を探すための名前を作る

CloudFront の行き先（オリジン）には IP を直接書けず、名前しか書けません。そこで Elastic IP を指す名前を1つ作ります。

**画面で入れる値**（検索窓に Route 53 → **Hosted zones** → `agi-japan.com` → **Create record**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Record name | 名前の前の部分 | `suiri-origin`（結果は `suiri-origin.agi-japan.com`） |
| Record type | 種類 | **A – Routes traffic to an IPv4 address and some AWS resources** |
| Alias | エイリアスにするか | **オフ** |
| Value | 中身 | Elastic IP（`<EIP>`） |
| TTL (seconds) | 覚えておく秒数 | `300` |
| Routing policy | 答え方 | **Simple routing** |

**コマンドでやる場合**

```bash
cat > /tmp/suiri-origin.json <<EOF
{"Changes":[{"Action":"UPSERT","ResourceRecordSet":{"Name":"$ORIGIN","Type":"A","TTL":300,"ResourceRecords":[{"Value":"$EIP"}]}}]}
EOF
$AWS route53 change-resource-record-sets --hosted-zone-id "$ZONE_ID" --change-batch file:///tmp/suiri-origin.json
```

**なぜこの名前か：** 講座の `origin.<ドメイン>` と同じ考え方です。サーバーを引っ越して IP が変わっても、このレコードを1つ書き換えるだけで済みます。EC2 のパブリック DNS 名（`ec2-…amazonaws.com`）を使う方法もありますが、それには VPC の「DNS ホスト名」をオンにする必要があり、講座の「VPC only」で作ると初めはオフです。

### 12-2. ディストリビューションを作る

**画面で入れる値**（検索窓に CloudFront → **Distributions** → **Create distribution**）

画面の並びは時期によって変わります。項目名で探してください。料金のプランを選ぶ画面が出たら **Pay as you go** を選びます（講座と同じ「使った分だけ」の払い方。この量なら毎月の無料枠に収まります。定額プランは選べる設定に制限があり、この手順の値と合わせにくいため使いません）。作る画面で見つからない項目は、作ったあとにディストリビューションの **Origins**・**Behaviors**・**General** の各タブの **Edit** で同じ値にできます。

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Origin domain | 行き先 | `suiri-origin.agi-japan.com`（手で入力） |
| Protocol | 行き先との通信の方式 | **HTTP only** |
| HTTP port | 行き先の番号 | `80` |
| Viewer protocol policy | 利用者との通信の方式 | **Redirect HTTP to HTTPS** |
| Allowed HTTP methods | 通す送り方 | **GET, HEAD, OPTIONS, PUT, POST, PATCH, DELETE** |
| Cache key and origin requests | 取り置きと受け渡しの決め方 | **Cache policy and origin request policy (recommended)** |
| Cache policy | 取り置きの決まり | **CachingDisabled** |
| Origin request policy | EC2 に渡す情報の決まり | **AllViewer** |
| Response headers policy | 応答に足す札 | なし（付けない） |
| Web Application Firewall (WAF) | 防火壁 | **Do not enable security protections** |
| Price class | どの地域の拠点を使うか | **Use all edge locations (best performance)**（初めのまま） |
| Alternate domain name (CNAME) | 受ける名前 | **Add item** → `suiri.agi-japan.com` |
| Custom SSL certificate | 証明書 | ステップ11 の `suiri.agi-japan.com` |
| Security policy | 暗号の強さ | **TLSv1.2_2021 (recommended)** |
| Supported HTTP versions | 通信の版 | HTTP/2（と HTTP/3） |
| Default root object | トップで返すファイル | 空のまま |
| Standard logging | 受付の記録 | **Off** |
| IPv6 | IPv6 で受けるか | **On**（初めのまま） |
| Description | 説明 | `suiri-prod-cf` |

作ったあと、**Tags** タブで Key `Name`・Value `suiri-prod-cf` を足します（CloudFront には名前の欄がないので、説明とタグで名前をそろえます）。**Last modified** が **Deploying** から日時に変わるまで待ちます（数分〜十数分）。

**コマンドでやる場合**

```bash
CACHE_ID=$($AWS cloudfront list-cache-policies --type managed \
  --query "CachePolicyList.Items[?CachePolicy.CachePolicyConfig.Name=='Managed-CachingDisabled'].CachePolicy.Id | [0]" --output text)
ORP_ID=$($AWS cloudfront list-origin-request-policies --type managed \
  --query "OriginRequestPolicyList.Items[?OriginRequestPolicy.OriginRequestPolicyConfig.Name=='Managed-AllViewer'].OriginRequestPolicy.Id | [0]" --output text)
cat > /tmp/suiri-cf.json <<EOF
{
  "DistributionConfig": {
    "CallerReference": "suiri-prod-cf-$(date +%s)",
    "Comment": "suiri-prod-cf",
    "Enabled": true,
    "Aliases": {"Quantity": 1, "Items": ["$DOMAIN"]},
    "DefaultRootObject": "",
    "Origins": {"Quantity": 1, "Items": [{
      "Id": "suiri-origin",
      "DomainName": "$ORIGIN",
      "CustomOriginConfig": {"HTTPPort": 80, "HTTPSPort": 443, "OriginProtocolPolicy": "http-only",
        "OriginReadTimeout": 30, "OriginKeepaliveTimeout": 5,
        "OriginSslProtocols": {"Quantity": 1, "Items": ["TLSv1.2"]}}
    }]},
    "DefaultCacheBehavior": {
      "TargetOriginId": "suiri-origin",
      "ViewerProtocolPolicy": "redirect-to-https",
      "AllowedMethods": {"Quantity": 7, "Items": ["GET","HEAD","OPTIONS","PUT","POST","PATCH","DELETE"],
        "CachedMethods": {"Quantity": 2, "Items": ["GET","HEAD"]}},
      "CachePolicyId": "$CACHE_ID",
      "OriginRequestPolicyId": "$ORP_ID",
      "Compress": true
    },
    "ViewerCertificate": {"ACMCertificateArn": "$CERT_ARN", "SSLSupportMethod": "sni-only", "MinimumProtocolVersion": "TLSv1.2_2021"},
    "HttpVersion": "http2and3",
    "IsIPV6Enabled": true,
    "PriceClass": "PriceClass_All"
  },
  "Tags": {"Items": [{"Key": "Name", "Value": "suiri-prod-cf"}]}
}
EOF
CF_ID=$($AWS cloudfront create-distribution-with-tags --distribution-config-with-tags file:///tmp/suiri-cf.json \
  --query 'Distribution.Id' --output text)
$AWS cloudfront wait distribution-deployed --id "$CF_ID"   # 配り終わるまで待つ（数分〜十数分）
CF_DOMAIN=$($AWS cloudfront get-distribution --id "$CF_ID" --query 'Distribution.DomainName' --output text)
echo "$CF_DOMAIN"
```

**なぜその値か：**

- **HTTP only・80：** EC2 は 80番しか開けていません。CloudFront から EC2 までを https にすると、つながりません。
- **Redirect HTTP to HTTPS：** `http://` で来た人を、自動で `https://` に案内します。
- **7つの送り方を全部通す：** 推理手帳は、記録の保存（POST）・直す（PUT・PATCH）・消す（DELETE）を使います。GET と HEAD だけだと、保存ボタンが失敗します。
- **CachingDisabled（いちばん大事）：** 推理手帳の画面は、ログインした人ごとに中身が違います。CloudFront に取り置かせると、Aさんの手帳が Bさんに見える、という重大な事故が起きえます。画面の CSS・JS は、ブラウザの側で取り置かれるので遅くなりません。
- **AllViewer：** クッキー（ログイン状態・CSRF の合言葉）・見出し（ヘッダー）・URL の `?` 以降（Google から戻るときの `code` と `state`）・Host（利用者が開いた名前）を、そのまま EC2 に渡します。クッキーが届かないと、送信が必ず 419 になります。Host を渡すので、アプリは `suiri.agi-japan.com` の住所で URL を作れます（AllViewerExceptHostHeader だと `suiri-origin` の名前になってしまう）。カレンダー連携の戻り先（`/calendar/callback`）も、この名前から作ります。
- **Response headers policy なし：** 守りの札（セキュリティのヘッダー）はアプリが付けています（`src/app/Http/Middleware/SecurityHeaders.php`）。CloudFront で上書きしません。
- **WAF なし：** 有料のため。まずはアプリ側の回数制限で守ります。

### 12-3. `suiri.agi-japan.com` を CloudFront に向ける

**画面で入れる値**（Route 53 → **Hosted zones** → `agi-japan.com` → **Create record**）

| 項目（画面の英語） | 日本語の意味 | 入れる値 |
|---|---|---|
| Record name | 名前の前の部分 | `suiri` |
| Record type | 種類 | **A** |
| Alias | エイリアスにするか | **オン** |
| Route traffic to | 向け先 | **Alias to CloudFront distribution** → `suiri-prod-cf`（`d…cloudfront.net`） |
| Routing policy | 答え方 | **Simple routing** |

**コマンドでやる場合**

```bash
cat > /tmp/suiri-alias.json <<EOF
{"Changes":[{"Action":"UPSERT","ResourceRecordSet":{"Name":"$DOMAIN","Type":"A",
  "AliasTarget":{"HostedZoneId":"Z2FDTNDATAQYW2","DNSName":"$CF_DOMAIN","EvaluateTargetHealth":false}}}]}
EOF
$AWS route53 change-resource-record-sets --hosted-zone-id "$ZONE_ID" --change-batch file:///tmp/suiri-alias.json
```

`Z2FDTNDATAQYW2` は「CloudFront へのエイリアス」を表す、AWS 共通の決まった番号です（アカウントの番号ではありません）。

**なぜエイリアスか：** CloudFront の IP は AWS の都合で変わるので、IP を直接書けません。エイリアスにすると、Route 53 が今の正しい場所を答えてくれます。追加の料金もかかりません。IPv6 用の AAAA レコードも同じ手順で足せますが、A だけでも動きます（講座と同じ）。

これで名前の道が2本できました。

```
suiri.agi-japan.com        → CloudFront（https を受ける）→ suiri-origin.agi-japan.com → EC2 の 80番
suiri-origin.agi-japan.com → EC2 の Elastic IP（CloudFront だけが使う裏の名前）
```

**確かめ方**（手元のパソコンで。DNS が広まるまで数分〜十数分かかることがあります）

```bash
curl -sI https://suiri.agi-japan.com/up | head -1        # HTTP/2 200
curl -sI http://suiri.agi-japan.com/ | grep -i -E '^(HTTP|location)'   # 301 と location: https://…
curl -m 5 -sI http://$EIP/ ; echo "終了コード $?"          # 時間切れ（28）になる＝80番が CloudFront だけに絞れている
```

ブラウザで `https://suiri.agi-japan.com` を開き、鍵マークが付いてトップ（ログインのボタン）が出れば、道はつながっています。

---

## ステップ13：Laravel に「CloudFront の後ろにいる」と教える（コードは済んでいる）

**役目：** CloudFront から EC2 までは http なので、何もしないと Laravel は「自分は http で呼ばれている」と思い込みます。それを正しく扱う設定です。

**たとえると：** 受付が「表では鍵のかかった通信で受けました」と伝えてくれないので、事務所の中で「本番では、いつも鍵のかかった住所で返事を書く」と決めておくことです。

**推理手帳では、もうコードに入っています。** 講座のように手で書き足す必要はありません。消さないでください（[`.claude/rules/deploy.md`](../../.claude/rules/deploy.md)）。

| 備え | 場所 | 何をしているか | ないとどうなるか |
|---|---|---|---|
| CloudFront を信じる | [`src/bootstrap/app.php`](../../src/bootstrap/app.php) の `trustProxies(at: '*')` | CloudFront が付ける `X-Forwarded-For` から、本当のアクセス元の IP を取る | 回数制限（ログインの試しすぎを止める）が、全員を CloudFront の IP として数えてしまう |
| 本番では https の URL を作る | [`src/app/Providers/AppServiceProvider.php`](../../src/app/Providers/AppServiceProvider.php) の `URL::forceScheme('https')`（`APP_ENV=production` のときだけ） | フォームの送信先・CSS の場所・Google の戻り先を必ず `https://` で作る | 保存ボタンで「送信しようとしている情報は保護されません」と止められる |
| 次からも https で来て（HSTS） | [`src/app/Http/Middleware/SecurityHeaders.php`](../../src/app/Http/Middleware/SecurityHeaders.php)（本番なら付ける） | ブラウザに「このサイトは1年間 https だけで開いて」と覚えさせる | 最初の1回を http で開かれたときに、途中で書き換えられる隙が残る |
| Google の戻り先 | [`src/config/services.php`](../../src/config/services.php) の `redirect` と、本番の `GOOGLE_REDIRECT_URI` | 本番は https の完全な URL を `.env` に書く（ステップ10） | Google が `redirect_uri_mismatch` で止める |

`trustProxies(at: '*')` は「どこから来た情報でも信じる」という設定です。安全に使えるのは、ステップ5 で 80番を CloudFront だけに絞っているからです。**80番を全世界に開けないでください。**

**確かめ方**（手元のパソコンで）

```bash
curl -sI https://suiri.agi-japan.com/ | grep -i strict-transport-security   # max-age=31536000 が出る
curl -s https://suiri.agi-japan.com/ | grep -o 'http://suiri[^"]*' | head    # 何も出ない（リンクがすべて https）
```

---

## 公開の前に確かめること

**役目：** 利用者を招く前に、決まりごとのページと Google の設定と動きを、ひととおり確かめます。

**たとえると：** お店を開ける前の最後の見回りです。

1. **決まりごとのページの運営者の情報：** `src/config/legal.php` の【】（運営者の氏名・連絡先のメールアドレス・制定日・施行日）を、**オーナーが自分で**書き換えて push し、EC2 で更新の手順（下の「更新するとき」）を流します。【】のままだと、利用者に運営者と連絡先が伝わりません。Google の同意画面もプライバシーポリシーへのリンクを求めます。
2. **Google を本番の公開状態にする：** [11 章](11-google-setup.md) の「8. 公開する」。テストのままだと、カレンダーの鍵が7日で切れます。
3. 次を順に確かめます。

| 確かめること | 見方 |
|---|---|
| 鍵マークが付く | `https://suiri.agi-japan.com` をブラウザで開く |
| http → https に飛ぶ | `http://suiri.agi-japan.com` を開くと `https://` になる |
| ログインできる | 自分の Google アカウントで「Google ではじめる」→ 同意の画面 → 最初の画面 |
| （招待制にしたときだけ）名簿にない人は入れない | 名簿に入れていないアカウントで試すと「招待制です」（10-4） |
| 記録を保存できる（419 にならない） | 事件を1つ作って保存する |
| カレンダー連携（作ったら） | 「カレンダーとつなぐ」→ Google の確認 → 専用カレンダー「推理手帳」ができ、確かめる日のタスクが入る |
| worker が動いている | EC2 で `docker compose ps` の worker が Up、`docker compose logs --tail 50 worker` にエラーがくり返していない |
| EC2 に直接は届かない | 手元で `curl -m 5 -sI http://$EIP/` が時間切れ |
| 守りの札が付く | ステップ13 の確かめ方 |

---

## 更新するとき

**役目：** 動いている本番を、GitHub に上げた新しいコードに入れ替えます。

**たとえると：** お店を開けたまま、棚と道具を新しいものに入れ替える作業です。商品（利用者の記録）はそのまま残します。

**決まり：** 本番のファイルを直接書き換えません。直すのは必ず手元で、`git push` → EC2 で `git pull` の一本道だけにします（直接書き換えると、次の `git pull` で消えたり、ぶつかって pull できなくなったりします）。

```bash
# 手元のパソコンで
ssh -i ~/.ssh/suiri-prod-key.pem ec2-user@$EIP
```

```bash
# EC2 の中で
cd ~/suiri-techo
~/bin/suiri-backup.sh                       # 先にバックアップ（表を変える更新のときは必ず）
git pull --ff-only
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app php artisan optimize
docker compose up -d
docker compose restart worker
```

| コマンド | 何をしているか | なぜ |
|---|---|---|
| `~/bin/suiri-backup.sh` | DB を丸ごとファイルに書き出す（「バックアップ」で作るスクリプト） | 表を変える移行は戻しにくい。何かあったときに更新の前に戻せるように |
| `git pull --ff-only` | GitHub の新しいコードを取ってくる | `--ff-only` は、サーバーで何か書き換えてあって食い違うときに、混ぜずに止める |
| `composer install …` | PHP の部品を入れ直す | `composer.lock` が変わったときに要る（変わっていなければすぐ終わる） |
| `migrate --force` | 足りない移行（表の追加・直し）を動かす | 新しい列・表がないと 500 エラー。表を消して作り直すコマンドは本番では使わない（記録が全部消える） |
| `npm ci` → `npm run build` | 画面の CSS・JS を作り直す | `public/build/` は git に入っていない |
| `optimize` | 作り置き（設定・住所の表・画面のひな形）を作り直す | 古い作り置きが残ると、新しい画面や設定が反映されない |
| `up -d` | `docker-compose.yml` が変わっていれば、その箱を作り直す | 変わっていなければ何もしない |
| `restart worker` | worker を起動し直す | worker は起動したときのコードで動き続けるので、新しいコードを読ませる（`php artisan queue:restart` と同じ役目に加え、15分ごとのカレンダーの取り込みと、5分ごとに今日の予定の覚えを消す `cache:prune-expired` の予定の係も読み直す） |

**変わった物によって足すこと：**

| 変わった物 | 足すこと |
|---|---|
| `infra/php/` の Dockerfile・`php.ini`・`zz-security.conf` | 最初に `docker compose up -d --build`（PHP の箱を作り直す） |
| `infra/nginx/default.conf` | `docker compose restart web`（nginx は起動したときにしか設定を読まない） |
| `src/.env`（手で直した） | `docker compose exec app php artisan optimize` → `docker compose restart worker` |

**初回だけ（ChatGPT・Claude とつなぐ窓口を、すでに動いている本番に初めて載せるとき）：** 上の更新の手順の `migrate --force`（Passport の表ができる）のあとに、次の1行を1回だけ打ちます。そのあとは上の手順どおり `optimize` → `up -d` → `restart worker` です。

```bash
docker compose exec app php artisan passport:keys
```

| コマンド | 何をしているか | なぜ |
|---|---|---|
| `passport:keys` | 窓口の通行証に署名する鍵の組（秘密の鍵・公開の鍵）を `src/storage/` に作る | 鍵がないと、同意の画面や AI が通行証を受け取る所（`/oauth/authorize`・`/oauth/token`）がエラーになり、ChatGPT・Claude からつなげない。鍵は git に入らない（`src/.gitignore` の `/storage/*.key`）ので、サーバーで1回だけ作る。**`--force` は付けない**：作り直すと、それまでに出した通行証がすべて使えなくなり、つないでいる人が全員つなぎ直しになる（記録は消えない） |

確かめ方は [12. ChatGPT・Claude とつなぐ](12-ai-connect.md) の「本番で確かめる」にあります。

コードは `./src` をそのまま箱に見せているので、PHP と Blade の変更は `git pull` だけで画面に出ます。ただし `optimize` の作り置きと worker は上の手順で入れ替えます。

**更新の前に止めたいとき（任意）：** `docker compose exec app php artisan down` で「ただいま準備中」の画面にし、終わったら `docker compose exec app php artisan up`。

**確かめ方：** `docker compose ps` で4つが Up、`curl -i http://localhost/up` が 200、ブラウザで変えた所が出ている。

---

## バックアップ（毎日・7日分）

**役目：** DB を毎日ファイルに書き出し、7日分だけ残します。

**たとえると：** 毎晩、帳簿の写しを取って金庫にしまい、1週間より古い写しはシュレッダーにかけることです。

**なぜ7日か：** プライバシーポリシーに「バックアップは7日で消える」と書いてあるからです（`src/config/legal.php` の `backup_days`）。退会した人の記録は、すぐに消したうえで、写しの中からも7日以内に消えます。**この数字を変えるときは、ポリシーの数字も一緒に変えます。**

### バックアップのスクリプトと cron（EC2 の中で）

```bash
mkdir -p ~/backup ~/bin
cat > ~/bin/suiri-backup.sh <<'EOF'
#!/bin/bash
# 推理手帳の DB を1日1回ファイルに書き出し、7日より古いものを消す
set -euo pipefail
cd /home/ec2-user/suiri-techo
out=/home/ec2-user/backup/suiri-$(date +%F).sql.gz
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump --single-transaction --routines --no-tablespaces -uroot "$MYSQL_DATABASE"' | gzip > "$out.tmp"
mv "$out.tmp" "$out"
find /home/ec2-user/backup -name 'suiri-*.sql.gz' -mtime +6 -delete
EOF
chmod 700 ~/bin/suiri-backup.sh
chmod 700 ~/backup
~/bin/suiri-backup.sh && ls -l ~/backup
(crontab -l 2>/dev/null; echo '0 3 * * * /home/ec2-user/bin/suiri-backup.sh >> /home/ec2-user/backup/backup.log 2>&1') | crontab -
crontab -l
```

| 部分 | 意味 |
|---|---|
| `set -euo pipefail` | どこかで失敗したら、そこで止める（壊れたファイルを「成功」として残さない） |
| `docker compose exec -T db …` | db の箱の中で `mysqldump`（DB を SQL の文に書き出す道具）を動かす。パスワードは箱の中の設定値を使うので、スクリプトに書かなくてよい。`-T` は書き出しに画面用の文字が混ざらないようにするため |
| `--single-transaction` | 動かしたまま、ある瞬間の矛盾のない写しを取る |
| `gzip` | 小さくする |
| `.tmp` に書いてから `mv` | 書いている途中のファイルを、できあがった写しと取り違えない |
| `find … -mtime +6 -delete` | 7日より古い写しを消す（今日の分と前の6日分＝7つが残る） |
| `0 3 * * *` | 毎日 3:00（ステップ8 で日本時間にしてある） |

**確かめ方：** 次の日に `ls -l ~/backup` で新しい日付のファイルができている。`tail ~/backup/backup.log` にエラーがない。8日目からファイルの数が7つのままになる。

### 戻すとき

```bash
cd ~/suiri-techo
~/bin/suiri-backup.sh     # まず今の状態も写しておく（戻したあとで「やっぱり今のほうがよかった」に備える）
gunzip -c ~/backup/suiri-YYYY-MM-DD.sql.gz | docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot "$MYSQL_DATABASE"'
docker compose exec app php artisan optimize
docker compose restart worker
```

`YYYY-MM-DD` は戻したい日付に書き換えます。写しの中の表で上書きするので、**その日より後の記録は消えます**。戻すかどうかは慎重に決めます。

### もう一段の備え（任意）

上の写しは EC2 と同じディスクにあるので、EC2 ごと壊れたり消したりすると一緒になくなります。気になるときは次のどちらかを足します。

- **ディスクのスナップショットを毎日取る：** EC2 → **Elastic Block Store** → **Lifecycle Manager** → **Create lifecycle policy**。**Target resource tags** に `Name` = `suiri-prod-ec2`、**Frequency** は Daily、**Retain** は **7**、Description は `suiri-prod-dlm`。スナップショットは東京で 0.05 USD／GB・月で、変わった所だけが増えていきます（月額はディスクの使い方しだいで、未確認）。
- **ときどき手元に写す：** 手元のパソコンで `scp -i ~/.ssh/suiri-prod-key.pem "ec2-user@$EIP:backup/suiri-*.sql.gz" ~/suiri-backup/`。ただし手元の写しも7日より古いものは消します（ポリシーの数字と合わせるため）。

---

## 困ったとき

**役目：** よくある症状から、見る所と直し方を引けるようにします。まずログを読みます（推測で設定を変える前に）。

```bash
docker compose logs --tail 100 app      # PHP のエラー
docker compose logs --tail 100 web      # nginx
docker compose logs --tail 100 worker   # 取り込み・書き込みの係
docker compose logs --tail 100 db       # MySQL
tail -n 50 src/storage/logs/laravel-$(date +%F).log   # Laravel のログ（LOG_STACK=daily のとき）
```

| 症状 | 原因として多いもの | 見る所・直し方 |
|---|---|---|
| `https://suiri.agi-japan.com` が「サイトにアクセスできません」 | CloudFront の配り待ち・DNS の広まり待ち | 5〜15分待つ。`dig +short suiri.agi-japan.com` で CloudFront の住所が返るか |
| 「この接続ではプライバシーが保護されません」 | 代替ドメイン名と証明書の名前が違う | CloudFront の **Alternate domain name** に `suiri.agi-japan.com`、証明書が us-east-1 の `suiri.agi-japan.com` か |
| 502 Bad Gateway | EC2 か箱が止まっている、`suiri-origin` の IP が違う | `docker compose ps`、`dig +short suiri-origin.agi-japan.com` が Elastic IP か、SG の 80番がプレフィックスリストか |
| 504 Gateway Timeout | EC2 に届いていない | SG の 80番、EC2 が Running か |
| 送信すると 419 Page Expired | クッキーが EC2 に届いていない | CloudFront の **Origin request policy** が **AllViewer** か。`SESSION_SECURE_COOKIE=true` なのに http で開いていないか |
| 保存で「送信しようとしている情報は保護されません」・リンクが `http://` | 本番の印がない | `src/.env` の `APP_ENV=production` → `php artisan optimize` |
| Google で「エラー 400: redirect_uri_mismatch」 | Google に登録した戻り先と、アプリが送る戻り先が1文字違う | `GOOGLE_REDIRECT_URI` が `https://suiri.agi-japan.com/auth/google/callback` と1字も違わないか（https か、末尾の `/`）。[11 章](11-google-setup.md) の Clients の **Authorized redirect URIs**。直したら `optimize` |
| 「Google ではじめる」のボタンが「準備中」 | `GOOGLE_CLIENT_ID`・`GOOGLE_CLIENT_SECRET` が空、または `optimize` していない | `src/.env` を見て `php artisan optimize` |
| ログインすると「招待制です」 | 招待制（`SUIRI_INVITE_ONLY=true`）にしていて、そのメールアドレスが名簿にない | `php artisan invite:list` → `invite:add`（大文字・小文字の違いはアプリがそろえる）。一般公開のつもりなら `src/.env` の `SUIRI_INVITE_ONLY=false` → `php artisan optimize`（10-4） |
| カレンダーに予定が入らない・取り込まれない | worker が止まっている | `docker compose ps` の worker、`docker compose logs worker`。`docker compose restart worker` |
| 「連携が切れました」と出る | Google の公開状態がテストのまま（7日で切れる）、利用者が許可を取り消した、6か月使われなかった | [11 章](11-google-setup.md) の Audience が **In production** か。つなぎ直してもらう |
| 500 で `Vite manifest not found` | 画面をビルドしていない | `npm ci` → `npm run build` |
| 500 でログに `Permission denied` | 書き込めないフォルダがある | `docker compose exec app chmod -R guo+w storage bootstrap/cache` |
| 500 でログに `Access denied for user` | `DB_PASSWORD` が2枚で違う | 2枚を見比べる → `optimize` |
| `.env` を直したのに変わらない | 作り置きが古い | `php artisan optimize` → `docker compose restart worker` |
| `compose build requires buildx 0.17.0 or later` | buildx が古い・ない | ステップ8-3 の buildx を入れ直す |
| ビルドの途中で止まる・`Killed` | メモリ不足 | `free -h` でスワップがあるか（ステップ8-4） |
| SSH が `Operation timed out` | 自宅の IP が変わった | 下のコマンドで 22番の相手を今の IP に直す |
| SSH が `Permission denied (publickey)` | 鍵のパス・権限・ユーザー名 | `-i ~/.ssh/suiri-prod-key.pem`、`chmod 400`、`ec2-user` |
| t4g.small が選べない | OS のひな形が x86 のまま | **Architecture** を **64-bit (Arm)** に |
| さっき作った物が一覧にない | リージョンが違う | 右上が東京か（ACM だけバージニア北部） |
| ディスクがいっぱい | Docker のイメージ・ビルドの一時ファイル・写しがたまった | `df -h`、`docker image prune -f`、`docker builder prune -f` |
| EC2 を再起動したら開けない | （箱は `restart: unless-stopped` なので自動で戻るはず） | `docker compose ps`。止まっていれば `cd ~/suiri-techo && docker compose up -d` |

**自宅の IP が変わったときに 22番を直す**（手元のパソコンで。変数の塊を貼ってから）

```bash
OLD_CIDR=$($AWS ec2 describe-security-groups --group-ids "$SG_ID" \
  --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`].IpRanges[0].CidrIp' --output text)
$AWS ec2 revoke-security-group-ingress --group-id "$SG_ID" --protocol tcp --port 22 --cidr "$OLD_CIDR"
$AWS ec2 authorize-security-group-ingress --group-id "$SG_ID" \
  --ip-permissions "IpProtocol=tcp,FromPort=22,ToPort=22,IpRanges=[{CidrIp=$MY_IP/32,Description=owner-home}]"
```

画面でやるなら、`suiri-prod-sg` → **Inbound rules** → **Edit inbound rules** → SSH の行の **Source** を **My IP** に選び直す → **Save rules**。

---

## 片付け（もう使わないとき）

**役目：** 料金を止めるために、作った物を消します。**順番が大事です**（使われている物は消せません）。

**たとえると：** お店をたたむときに、看板を下ろし、住所録から消し、建物を壊し、借りた番号を返し、最後に土地を返す順番です。

**消す前に：** 利用者に前もって知らせ（プライバシーポリシーでは 30 日前。`src/config/legal.php` の `shutdown_notice_days`）、その間はデータをダウンロードできるようにしておきます。

| 順 | 消す物 | やり方（画面） | 注意 |
|---|---|---|---|
| 1 | CloudFront `suiri-prod-cf` | 選んで **Disable** → 状態が無効になったら **Delete** | 無効になるまで数分かかる |
| 2 | Route 53 のレコード | `agi-japan.com` の中の `suiri`（A エイリアス）・`suiri-origin`（A）・ACM の確かめ用の CNAME（`_` で始まる名前）を消す | ホストゾーン `agi-japan.com` は Pixel Office も使うので**消さない** |
| 3 | ACM の証明書 `suiri-prod-cert` | us-east-1 で **Delete** | CloudFront を消したあとでないと消せない。無料なので残してもよい |
| 4 | バックアップを取り出す | 必要なら写しを手元へ（7日で消す決まりは守る） | 次の EC2 の終了で、ディスクの中身は戻らなくなる |
| 5 | EC2 `suiri-prod-ec2` | **Instance state** → **Terminate instance** | ディスク（`suiri-prod-ebs`）も一緒に消える |
| 6 | Elastic IP `suiri-prod-eip` | **Actions** → **Release Elastic IP addresses** | **忘れると料金が続く代表**。EC2 を消しても残る |
| 7 | スナップショットの方針（作ったなら） | Lifecycle Manager の `suiri-prod-dlm` を消し、残ったスナップショットも消す | 残すと料金が続く |
| 8 | セキュリティグループ `suiri-prod-sg`・キーペア `suiri-prod-key` | それぞれ **Delete**。手元の `~/.ssh/suiri-prod-key.pem` も消す | — |
| 9 | VPC 一式 | サブネット4つ → ルートテーブル `suiri-prod-rtb-public` → `suiri-prod-igw`（**Detach from VPC** してから Delete）→ `suiri-prod-vpc` | VPC そのものは無料。**Delete VPC** を押すと中身もまとめて消せる |
| 10 | Budgets | `suiri-prod-budget` を消す、または金額を戻す | — |
| 11 | GitHub のデプロイキー | `shimizu-open/suiri-techo` → **Settings** → **Deploy keys** → `suiri-prod-deploy-key` を **Delete** | — |
| 12 | Google | [11 章](11-google-setup.md) の「11. 片付け」 | OAuth クライアントを消す |

**コマンドでやる場合（EC2 と Elastic IP）**

```bash
$AWS ec2 terminate-instances --instance-ids "$INSTANCE_ID"
$AWS ec2 wait instance-terminated --instance-ids "$INSTANCE_ID"
ALLOC_ID=$($AWS ec2 describe-addresses --filters Name=tag:Name,Values=suiri-prod-eip --query 'Addresses[0].AllocationId' --output text)
$AWS ec2 release-address --allocation-id "$ALLOC_ID"
```

**しばらく使わないだけなら：** EC2 を **Stop** するだけでも、いちばん高い EC2 の料金は止まります（ディスクと Elastic IP の分は残ります）。再開は **Start** → EC2 で `cd ~/suiri-techo && docker compose up -d`。

---

## 次に読む章

- Google の準備（OAuth クライアント・Calendar API・同意画面の公開）：[11 Google の準備](11-google-setup.md)
- 決めたこと（AWS の名前・公開の住所）：[要件定義書 7章](../design/requirements.md)
- 講座・Pixel Office・Baker Street English の手順の対応表：[調査 R6 の 3章](../research/R6-reuse-and-architecture.md)
- Google 連携の調べ（スコープ・審査・7日で切れる条件）：[調査 R4](../research/R4-google-integration.md)
