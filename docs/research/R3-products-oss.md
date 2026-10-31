# R3 似たもの調査：既存のアプリ・OSS・ゲームの仕組み

- 調査日：2026-10-31
- 目的：「タスク管理＋Googleカレンダー＋日常でホームズの視点（観察→仮説→検証→振り返り）を練習する」アプリを作る前に、似たもの・参考になるものを調べる。そのうえで「何をまねるか／何をまねないか／どこで差をつけるか」を決める材料にする。
- 調べ方：WebSearch・WebFetch で公式サイト、ヘルプ、App Store、論文、レビューを開いて確かめた。OSS は GitHub のリポジトリを `gh` コマンドで直接開き、LICENSE、`composer.json`、ソースコードを読んだ。
- 制約：調査の途中で、このセッション全体の Web 検索の上限（200回）に達した。後半は URL が分かっているページを直接開いて確かめた。裏付けが取れなかったことは「未確認」と書いた。「検索抜粋」と書いたものは、検索結果に出た抜粋だけを見て、本文は開けていない。
- 価格は主に米国 App Store か公式サイトの表示で、時期と地域によって変わる。

---

## 0. 先に結論

1. **いちばん近い競合は Daily Holmes**（dailyholmes.com、2026年公開）。毎朝「知らない人の部屋の写真」が出て、利用者が推理を書き、AI がホームズとワトソンの口調で講評する。ただし題材は用意された写真で、**後日に現実で確かめる仕組みがない**。調べた範囲では、次の4つをすべて満たす製品は見つからなかった。
   - 自分の生活が題材
   - 事実と推測を分ける
   - 予測を先に書いて後日に確かめる
   - 確かめる日をカレンダーに入れる
2. **入力のお手本は推理ゲームにある。**
   - Golden Idol：集めた語で文の穴を埋める。
   - Obra Dinn：3件そろうまで正誤を見せず、確定したら書き換えられない。
   - 逆転裁判：証言の1文に証拠をつきつける。
   - 大逆転裁判の「共同推理」：ホームズの行き過ぎた推理を、主人公が正しい語に差し替える。
   - どれも、このアプリの「事実と推測を分ける画面」にそのまま応用できる。
3. **予想と答え合わせの土台は Fatebook**（MIT ライセンスでコード公開）。
   - 流れ：1行で予想 → 確率 → 「いつ思い出させる？」の日付 → 期日に通知 → YES／NO／どちらとも言えない、で判定 → キャリブレーション図（自信の度合いと実際の当たり率を比べる図）。
   - 仕組みが短く完成している。「自分が予想するまで他人の予想を隠す」設定もあり、親子での見せ合いに使える。
4. **カレンダー連携は Todoist 型が安全。**
   - アプリ専用のカレンダーを作り、そこにだけ書く。
   - 予定にアプリ側のIDを持たせ、Google 側で日時を動かされたらタスクに戻す。Google 側で新しく作られた予定は取り込まない。
   - Sunsama の「朝の計画・夕方の締め」の手順は、そのまま「朝の観察・夜の検証」に置き換えられる。
   - Motion のように勝手に予定を動かすのは避ける。
5. **続ける工夫は「罰を使わない」が結論。**
   - Habitica の「サボると仲間にダメージ」、消せない連続記録（Day One）、0時で区切られる日付は、どれもやめる原因として報告されている。
   - 連続記録が途切れると戻ってこない、という査読付きの研究もある（Silverman & Barasch 2023）。
   - 次の4つで代わりにする。
     - 休んでも途切れない仕組み（Brilliant の Streak Charge、Duolingo の Streak Freeze）
     - 予備日
     - 後から埋められる記録
     - 「検証まで終えた件数」を数える
6. **言ってよいことの線引き。**
   - 脳トレの「遠い転移」（鍛えた課題とかけ離れた能力まで伸びること）は、大規模なレビューやメタ分析でほぼ否定されている。Lumosity は根拠のない広告で FTC（米連邦取引委員会）と200万ドルで和解した。
   - 言えるのは「自分の生活の場面で、観察→仮説→検証の手順を練習し、記録で上達が見える」まで。
   - 医療側で根拠があるのは、手順化した「見る訓練」（美術館での観察教育 VTS）が観察の正確さを上げた小規模研究と、1時間未満の予測訓練が予測精度を6〜11%上げた無作為化実験（Good Judgment Project）。

---

## 1. 思考力・脳トレ・批判的思考のアプリ

### 1-1. 比較表

| 製品 | 題材 | 中心の操作の流れ | 続けさせる工夫 | 仮説・検証 | 価格の目安 |
|---|---|---|---|---|---|
| Lumosity | 40本以上のミニゲーム | 10分の基準テスト → 毎日のセット → 分析を見る | 同年代との比較、分析 | × | 月$14.99ほか |
| Elevate | 40本以上のミニゲーム | 1日3〜5本 → 自動で難易度調整 → 週次レポート | カレンダーで連続日数を表示、週次レポート | × | $4.99〜39.99 |
| Peak | 45本以上のミニゲーム | ワークアウト／5分未満の Coffee Break | 友人・職業別の比較 | × | $2.99〜34.99＋広告 |
| Brilliant | 数学・科学の対話型教材 | 小問を解きながら概念を組み立てる | 連続記録＋Streak Charge、XP、リーグ | △ | 年$120〜150前後（未確認） |
| Kialo | 議論 | 主張の木を作り、各主張を0〜4で評価 | 授業での利用 | ◎構造（検証はない） | 無料 |
| Daily Holmes | 他人の部屋の写真 | 推理を書く → AI の講評 | 毎朝1問 | ○推論（検証はない） | 無料 |
| Sherlock Holmes: Join the Case | 原作60編 | 物語の途中で推理問題が出る | 連続記録、ランク、実績 | クイズ型 | 月$5.99 |
| Criminal Case | 事件現場の絵 | 物を探す → 鑑識 → 尋問 → 逮捕 | エネルギー制、チーム | △ | 週$2.99 など |
| イラスト探偵 | イラスト | 違和感をタップ → 犯人 | ステージ数 | △ | ¥400〜2,000＋広告 |
| 1人でウミガメのスープ | 謎の文章 | はい／いいえで質問し、AI が答える | ライフ制 | ○問いで確かめる | ¥100＋広告 |
| シンクシンク | 図形パズル | 3分のゲーム | **1日の上限**、大会、家族のプロフィール | × | 月450〜980円 |

### 1-2. 製品ごとの要点

**Lumosity**（https://www.lumosity.com/en/ 、https://apps.apple.com/us/app/lumosity-brain-training/id577232024 ）
- 何をするものか：速さ・記憶・注意などのゲームで脳を鍛えるとうたうアプリ。
- 流れ：最初に10分の Fit Test → 毎日新しいゲームのセット → 分析を見る。
- 悪い点：2016年1月、認知症や学業成績に効くという広告に根拠がないとして FTC と和解し、200万ドルを支払った（5,000万ドルの判決は支払い能力を理由に猶予）。FTC は「消費者の不安につけ込んだ」と述べた（https://www.ftc.gov/news-events/press-releases/2016/01/lumosity-pay-2-million-settle-ftc-deceptive-advertising-charges ）。
- 借りられるもの：最初の短い基準テスト。それと、効果を大きく言わないという教訓。

**Elevate**（https://apps.apple.com/us/app/elevate-brain-training-games/id875063456 ）
- 何をするものか：1回が短い脳トレゲーム。苦手な分野に合わせてワークアウトを組む。
- 借りられるもの：
  - 1日3〜5問という小さな単位
  - カレンダーでの見える化
  - 週次レポート（このアプリなら「仮説が当たった率」「観察の具体度」の推移）
- 注意：「90%以上が向上を報告」は開発元の自己申告。

**Peak**（https://apps.apple.com/us/app/peak-brain-games-training/id806223188 ）
- 5分未満の「Coffee Break」がある。
- 更新後に飛ばせない広告が入ったという不満レビューがある。
- 借りられるもの：忙しい日向けの「観察1行と仮説1つだけ」の短いモード。

**Brilliant**（https://brilliant.org/help/using-brilliant/what-is-a-streak-charge ）
- 何をするものか：手を動かして解く対話型の教材。
- 続けさせる工夫：**Streak Charge**。単元を終えると最大2個まで貯まり、1日休むと自動で使われて連続記録が途切れない。日数は増えない。
- 無料版は1日2回分の「鍵」で進む。
- 借りられるもの：休んでも途切れない連続記録。数えるのは「検証まで終えた件数」にする。

**Kialo / Kialo Edu**（https://en.wikipedia.org/wiki/Kialo 、https://support.kialo-edu.com/en/hc/about-voting/ 、https://www.kialo-edu.com/features ）
- 何をするものか：議論の構造を木の形で表すサービス。
  - 根に主題があり、その下に賛成と反対の主張が何段も続く。
  - 1つの主張は500字未満で、出典を付けられる。
- 評価のしかた：各主張に **impact（0〜4）** を付ける。impact は「その主張の確からしさ」と「すぐ上の主張との関係の強さ」を合わせたもの。
- 偏りを防ぐ工夫：
  - 管理者は他人の票を隠して評価を集められる（blind vote）。
  - 教育版には、匿名のアバターで参加する機能と、小グループごとに議論を分ける機能がある。
- 悪い点：
  - 下に行くほど文脈が失われる。
  - 賛否の型に入らない「言い直し」や「まだ説明できないこと」を置く場所がない。
  - 真偽が人気投票になる、という批判がある（https://www.lesswrong.com/posts/g3odvaj8opqCF9egv/kialo-an-online-discussion-platform-that-attempts-to-support ）。
- 借りられるもの：
  - 「観察した事実 → 仮説A／B → それぞれを支える証拠・崩す証拠」という木。
  - 証拠ごとに「どれくらい支えるか」を0〜4で付ける。確からしさと関係の強さは分けて聞く。
  - 親子が互いの評価を隠したまま付け、後で見せ合う。
  - 賛否の外に「まだ説明できないこと」の欄を作る。

**Daily Holmes**（https://www.producthunt.com/products/daily-holmes ）
- 何をするものか：毎朝、台所・机・ホテルの部屋などの写真が出て、住人の人物像・仕事・最近の一週間を推理して書く。AI が講評する。
- 採点の仕組みは未確認。
- 借りられるもの：「毎朝1問、推理を書く」というリズム。
- 違いの出し方：題材を自分の生活にすること、予測を先に書いて後で現実で確かめること、確かめる日をカレンダーに入れること。

**Sherlock Holmes: Join the Case**（https://apps.apple.com/app/id6760101822 ）
- 何をするものか：原作60編を読む（聴く）アプリ。118か所で物語が止まり、推理問題が出る。
- 間違えると「見落としたもの」が示される。
- これは**避けたい「ホームズのクイズ」型そのもの**。借りられるのは「何を見落としたかを見せる」振り返りの演出だけ。

**Criminal Case**（https://apps.apple.com/gb/app/criminal-case/id767473889 ）
- 何をするものか：事件現場で物を探して犯人を逮捕する推理ゲーム。
- 「証言と証拠の矛盾を突く」形は参考になる。
- エネルギー制と、待たせて課金させる仕組みへの不満が多い。

**イラスト探偵**（AppSeed、https://apps.apple.com/jp/app/id1563534755 ）
- 何をするものか：事件現場のイラストから違和感のある所をタップし、犯人を当てる。
- レビューで「事件性の薄い物探し型の問題が増えた」と不満が出て、開発者も推理の面白さが薄れたことを認めている。
- **仮説のない観察は物探しになる**、という分かりやすい教訓。

**1人でウミガメのスープ**（https://apps.apple.com/jp/app/1%E4%BA%BA%E3%81%A7%E3%82%A6%E3%83%9F%E3%82%AC%E3%83%A1%E3%81%AE%E3%82%B9%E3%83%BC%E3%83%97/id6544801645 ）
- 何をするものか：AI が出題者になり、はい／いいえ／関係ありません、だけで答える。
- 不満の中心は AI の判定ミスで、関係あることを「関係ない」と答える。
- **AI に真偽を判定させると信頼を失いやすい**。日常の題材では、答え合わせは現実（後日の検証）に任せる方がよい。

**シンクシンク**（Wonderfy、https://apps.apple.com/JP/app/id1082259392 ）
- 何をするものか：4〜10歳向けの思考力アプリ。
- 有料版でも1日3プレイまでと、**あえて上限を設けて**習慣にしている。家族で複数人のプロフィールを持てる。
- 効果の主張は開発元の記載で、論文は未確認。

**日本のその他**
- LOGIQ LABO（ソニー・グローバルエデュケーション）：無料の「思考力チャレンジ」に親子・きょうだいで参加できる（https://prtimes.jp/main/html/rd/p/000000039.000032729.html ）。
- 細谷功『「具体⇄抽象」トレーニング』（PHP、29問）は大人向けで、子ども向けや親子向けの版は見つからなかった（https://zenn.dev/asada/articles/99b5a85605c8bf ）。
- 算数オリンピック系、学研・Z会の思考系アプリは、検索の上限のため**未確認**。

### 1-3. 科学的な背景：言ってよいこと・言ってはいけないこと

| 文献 | 種類 | 要点 | 証拠の強さ |
|---|---|---|---|
| Stanford Center on Longevity と Max Planck 研究所、2014年の声明（https://longevity.stanford.edu/a-consensus-on-the-brain-training-industry-from-the-scientific-community-2 ） | 専門家の声明 | 脳トレの宣伝は誇張されがちで、学んだ技能を大きく超える変化があると結論するのは適切でない | 意見の表明。同じ年に127人の研究者が反論の書簡を出した |
| Simons ほか（2016）*Psychological Science in the Public Interest* 17(3)（https://www.psychologicalscience.org/publications/brain-training.html ） | 130本以上の研究の批判的レビュー | 鍛えた課題そのものは伸びるが、かけ離れた課題や日常が伸びる証拠はごくわずか。「伸ばしたい課題を練習せよ」 | 大規模なレビュー（メタ分析ではない） |
| Melby-Lervåg, Redick, Hulme（2016）*Perspectives on Psychological Science* 11(4)（https://pmc.ncbi.nlm.nih.gov/articles/PMC4968033 ） | メタ分析（87報） | 作業記憶トレーニングの遠い転移には確かな証拠がない | 強い |
| Sala ほか（2019）*Collabra: Psychology* 5(1)（https://livrepository.liverpool.ac.uk/3043493/ ） | 複数のメタ分析をまとめた分析 | 偽薬効果と出版の偏りを補正すると、遠い転移はほぼゼロ | 強い（異論はある） |
| Dolev ほか（2001）*JAMA* 286（https://www.eurekalert.org/news-releases/460214 ） | 対照群のある研究（Yale） | 美術館で絵を観察する訓練を受けた医学生（81人、対照65人）で、観察力が約10%向上 | 小〜中規模、1施設 |
| Naghshineh ほか（2008）*J Gen Intern Med* 23(7)（https://pmc.ncbi.nlm.nih.gov/articles/PMC2517949/ ） | 一部を無作為に分けた前後比較（Harvard） | VTS（絵を見て対話する方法）に基づく8回の講座で、正確な観察の数が増えた（訓練群 +5.41、対照 +0.36） | 小規模（24人と34人）、効果の持続は測っていない |

→ この表から、このアプリが言えるのは「自分の生活の場面で手順を練習する」まで。これは Simons らの「伸ばしたい課題を、その場面で練習せよ」と一致する。「頭が良くなる」「病気の予防になる」とは書かない。

---

## 2. 予想と答え合わせの道具

### 2-1. 比較表

| 製品 | 入力の画面 | 判定 | スコア | 知らせ | 続けさせる工夫 | コード公開・ライセンス |
|---|---|---|---|---|---|---|
| Fatebook | 1行＋日付の自動読み取り＋% | YES／NO／AMBIGUOUS | ブライアー（0〜2）、他の人との比較、キャリブレーション図（10%刻み） | 期日に通知とメール | お題の例文、週の目標と連続記録、ヒートマップ、チーム、大会 | 公開・MIT |
| PredictionBook | 文章＋日付＋確信度 | right／wrong／unknown（履歴が残る） | ブライアー（0〜1）、50%で折り返すキャリブレーション | 締切の通知 | 他の人も確信度を付けられる | README では BSD、**アーカイブ済み、サイトは終了** |
| Metaculus | 背景・判定基準・細則＋確率 | Yes／No／Ambiguous／Annulled | 対数系（ベースライン、ピア）、時間平均 | 未確認 | 実績ページ、大会 | 公開・BSD-2-Clause |
| Manifold | 市場を作って賭ける | 作った人が判定する | 遊び用のお金の損益 | 未確認 | 毎日の連続記録、クエスト、月替わりのリーグ | 公開・MIT |
| Farnam Street の意思決定日誌 | 8項目を書く | 人が振り返る | なし | なし | ― | ― |
| Quantified Intuitions | 雑学問題＋確信度 | 自動 | Greenberg（2018）の式 | ― | 毎月のクイズ | 公開・MIT |

### 2-2. Fatebook（いちばんの手本）

https://fatebook.io/ 、https://github.com/Sage-Future/fatebook （MIT、Next.js・Prisma・tRPC）

**中心の操作の流れ**
1. 入力欄に予想を1行で書く。例：「金曜までに肩の張りが減る」。
2. 本文の中に日付がちょうど1つだけ見つかり、しかも利用者がまだ日付欄を触っていないときだけ、その日付を判定日に自動で入れる。入れた日付は0.8秒ハイライトする（`Predict.tsx`、chrono-node というライブラリ）。
3. 判定日の欄には「**When should I remind you to resolve this question?**（いつ答え合わせを思い出させましょうか？）」と書いてある。判定日を「通知してほしい日」として見せている（`ResolveBy.tsx`）。
4. 毎日1回の処理で期日の来た問いを探し、「Ready to resolve（答え合わせの時です）」と通知・メールする。
5. YES／NO／AMBIGUOUS（どちらとも言えない）で判定する。
6. 実績ページでブライアースコア、キャリブレーション図、予想した回数のヒートマップを見る。

**続けさせる工夫**
- 「目標・健康・人間関係」などの分類ごとに例文がある。例文の `<x>` の部分が選択された状態で出るので、すぐ書き換えられる。
- 古い予想を見直すよう知らせる。「もう知らせない」ボタンも付いている。
- 自分が予想するまで他人の予想を隠せる（`hideForecastsUntilPrediction`）。
- Google カレンダーとの連携は**ない**（コードを検索して確認）。→ このアプリが差をつけられる所。

**悪い点**
- ブライアーが0〜2で「小さいほど良い」ため、説明文が必要になっている。

### 2-3. その他

**PredictionBook**（https://github.com/bellroy/predictionbook 、https://predictionbook.com/ ）
- 経緯：2024年1月10日から読み取り専用になり、その後終了した。現在のサイトは「Fatebook を勧める」と表示するだけ。利用者が少なく、維持費に見合わなかった（issue #262）。
- 入力の問いかけ文がうまい。「**What are you predicting?**（何を予想する？）／**When will you know you're right?**（いつ当たりが分かる？）／**How sure are you?**（どのくらい確か？）」。
- 判定はやり直せて、履歴が残る。
- キャリブレーション図は、50%未満の予想を反対側に折り返して集計する（30%なら「70%で外れる」と読み替える）。**件数の少ない個人に向いた集計のしかた**。

**Metaculus**（https://github.com/Metaculus/metaculus 、FAQ のソースで確認）
- 良い問いの条件：「読んだ人が、前にも後にも同じ判定に合意できること」。
- 判定には Yes／No のほかに、**Ambiguous（実情が分からず判定できない）と Annulled（前提が崩れた）**があり、どちらも採点しない。施術なら「患者が来なかった」は Annulled にあたる。
- ベースラインスコア＝100 ×（ln p − ln 0.5）/ ln 2。p は実際に起きた側に付けていた確率。完全に当てると +100、50%なら0、80%で当たると +68。**子どもにも分かる点数**になる。

**Manifold**（https://docs.manifold.markets/faq 、https://github.com/manifoldmarkets/manifold ）
- 続けさせる工夫：毎日の連続記録（報酬が少しずつ増える）、クエスト、6段階の月替わりリーグ。
- 悪い点：作った人の裁量で判定するため、「現実ではなく判定する人の解釈に賭けることになる」という批判がある（https://www.lesswrong.com/posts/DpDnXHcPejd9tn8R5/ambiguity-in-prediction-market-resolution-is-harmful ）。
- 順位を競う仕組みは、点数かせぎの雑な予想を招きやすい（これは推測）。

**意思決定日誌**
- Farnam Street のテンプレート（https://fs.blog/decision-journal/ ）は8項目：状況、問題の捉え方、結果を左右する要素、複雑さ、検討した他の選択肢と選ばなかった理由、起こりうる結果の幅、予想する結果とその確率、決めた時刻と心身の状態。狙いは後知恵の偏り（後知恵バイアス）を防ぐこと。
- Kahneman は「安いノートに、何が起きると思うか、なぜかを書け」と勧めた（https://www.fool.com/how-to-invest/2012/09/19/a-foolish-interview-with-michael-mauboussin.aspx ）。
- Annie Duke『Thinking in Bets』は「良い判断が良い結果になるとは限らない」と説く。学術的な裏付けは結果バイアスの研究。Baron & Hershey（1988）*JPSP* 54(4) では、同じ判断でも結果が悪いと判断の質まで低く評価された。
- 意思決定日誌のアプリ（Decidr など）は**未確認**。

**Good Judgment Project の訓練**
- Chang, Chen, Mellers, Tetlock（2016）*Judgment and Decision Making* 11(5)（https://jbaron.org/journal/16/16511/jdm16511.pdf ）。
  - 1時間未満の訓練（CHAMPS KNOW）を受けた群は、4年間一貫して対照群よりブライアーが6〜11%良かった。
  - CHAMPS KNOW の中身は、いつもはどうかと比べる、情報を探す、予想を直す、事後に検証する、別の視点を持つ、想定外を考える、など。
  - 4年目には、予想の説明に「どの原則を使ったか」をチェックボックスで付けさせた。
- 証拠の強さ：無作為に分けた長期の実験で強い。ただし同じ研究グループの報告で、題材は地政学、参加者は意欲の高い大人の志願者。子どもや施術の場面に当てはまるかは未検証。
- Mellers ほか（2014）*Psychological Science* 25(5) も、訓練・チーム化・成績上位者の選抜が予想精度を上げたと報告している（https://pubmed.ncbi.nlm.nih.gov/24659192/ ）。

**キャリブレーション訓練と「自分で確かめる」道具**
- Quantified Intuitions（https://www.quantifiedintuitions.org/ 、MIT）：雑学問題で自信の度合いを練習する。
- Clearer Thinking の Calibrate Your Judgment：画面の仕様は未確認。
- パーソナルサイエンス：Wolf & De Groot（2020）*Frontiers in Computer Science* 2 は「問う→設計→観察→推論→発見」の5つの活動と定義している。
- Exist（月$6.99）：行動の実験で「仮説を支持または棄却」する。
- Guessometer（2025年）：放射線科の研修医が「肺炎の確率70%」のような診断の予想を記録するために作った（https://ea.greaterwrong.com/posts/vxnWDjro9QPu8RFLK/my-tool-for-tracking-predictions ）。**施術者向けの使い方にいちばん近い例**。

### 2-4. 実装できる計算式

| 名前 | 式 | 意味 |
|---|---|---|
| ブライアースコア | (1/N) Σ (f − o)²　f＝予想の確率、o＝結果（当たり1、外れ0） | 0〜1。小さいほど良い（Brier 1950, *Monthly Weather Review* 78(1)） |
| ベースラインスコア（二択） | 100 × (ln p − ln 0.5) / ln 2 | +100〜0〜マイナス。大きいほど良い（Metaculus） |
| キャリブレーション図 | 予想の確率を区間に分け、区間ごとに「実際に当たった割合」を出す | 斜め45度の線に近いほど、自信の付け方が正確 |

---

## 3. 振り返り・日記・習慣のアプリ

### 3-1. 比較表

| アプリ | 1回の入力の手間 | 型 | 振り返り・過去の表示 | 続けさせる工夫 | 失敗したときの扱い | 価格の目安 |
|---|---|---|---|---|---|---|
| Day One | 中〜大（自由に書く） | テンプレート、毎日のお題 | On This Day（過去の同じ日） | 連続記録、知らせ | 後から書けば修復できる。**連続記録は消せない** | 無料／年$49.99／年$74.99 |
| Reflectly | 中 | 気分 → 要因 → 1文 | 気分のグラフ | ウィジェット、毎日の課題 | 未確認 | $4.99〜59.99 |
| Stoic | 中（機能が多い） | **朝の準備と夜の振り返り** | 推移のグラフ | 連続記録、バッジ | 未確認 | $6.99〜、生涯$299 |
| Daylio | **極小（2タップ）** | 気分＋活動のアイコン | 相関の統計、1年を1日1マスで並べた図 | 目標、実績、連続記録 | 未確認 | $4.99〜59.99 |
| Five Minute Journal | 小 | 朝3問・夜2問で固定 | On This Day（有料） | 連続記録、名言 | 未確認 | 月$4.99〜 |
| Habitica | 中（設定が重い） | 習慣・毎日の課題・ToDo | グラフがない | 経験値、ゴールド、パーティー | **体力が減る。0で持ち物を失う。仲間にもダメージ** | 無料／月$4.99 |
| Streaks | 極小（自動で完了も） | 最大24タスク | 統計 | 連続日数、ヘルスケアで自動完了 | 0に戻る（**予定した休みは除く**） | 買い切り$5.99 |
| Finch | 小 | 目標＋振り返り | 気分の推移 | ペットを育てる | 罰なし（連続記録はリセット） | $5.99〜69.99 |

### 3-2. 製品ごとの要点（借りられるもの中心）

- **Day One**（https://dayoneapp.com/guides/tips-and-tutorials/journal-streaks/ ）
  - 借りられるもの：書き忘れた日を後から埋めると連続記録が元に戻る仕組み。
  - 反面教師その1：連続記録の数字や知らせを**完全には消せない**。非表示にしたいという要望にも、スタッフは「提供していない」と答えた（2024-12）。
  - 反面教師その2：日付の境目が0時で固定。夜中をまたぐと連続記録が切れ、「境目を朝4時に」という要望が出ている（https://forums.dayoneapp.com/forums/topic/setting-to-push-day-boundary-to-4am-to-keep-streaks-on-late-night-entries/ ）。
  - 823日続けた人が「連続記録を守るためだけに書いていた」と、自分からやめた例もある（https://talk.macpowerusers.com/t/stopped-the-journaling-streak/32740 ）。
- **Reflectly**：「気分 → 要因を選ぶ → 1文」という段階的な入力は借りられる。一方で、あるレビューは「6日目には同じ3つの質問の言い換えになった」と書いている（https://macaron.im/blog/reflectly-ai-journaling-app-review ）。強い課金の誘導も批判されている。**前回書いた内容を受けて問いを変えないと飽きられる。**
- **Stoic**（https://www.getstoic.com/ ）：**朝に決めた焦点を夜の問いが受けて振り返る**。この組み立てを「朝の観察・仮説 → 夜の検証」に置き換えられる。ただし「選択肢が多すぎて素早く書けない」というレビューがある。
- **Daylio**（https://daylio.net/ ）：「2タップで1日の記録」。入力の最小単位をタップにしている。このアプリでも「事実／推測」をタップで分ける、よく見る対象をアイコンにする、などに使える。どの状況で立てた仮説がよく当たるか、という相関の統計にも応用できる。
- **Five Minute Journal**：毎日同じ少数の質問なので、何を書くか迷わない。夜の質問「どうすればもっと良い日にできたか」は、振り返り欄にそのまま流用できる。
- **Habitica**（https://lwn.net/Articles/747919/ 、https://habitbox.app/blog/habitica-review ）
  - やらなかった毎日の課題の分だけ体力が減り、パーティー全体にもダメージが及ぶ。体力が0になるとレベル・ゴールド・装備を失う。
  - 病気や燃え尽きのときに罰が重なる、という批判がある。ただしこれは競合アプリによるレビュー。
  - 公式も「燃え尽き」をゲームの中のボスにして、対処を発信している。
  - **子どもに「休んだら親にダメージ」は危険。** 協力させるなら、2人の記録が足し算される形にする。
- **Streaks**（https://streaks.app/ ）：曜日を指定したスケジュールなら、予定どおりの休みでは連続記録が切れない。ヘルスケアのデータで自動的に完了する。→「予定した休み」の考え方と、外の出来事をきっかけに入力を開く仕組みが借りられる。

### 3-3. やめたくなる原因（根拠つき）

| 原因 | 根拠 | 強さ |
|---|---|---|
| そもそも大半の人が残らない | Baumel ほか（2019）*JMIR* 21(9)：心の健康系の Android アプリ93本で、15日後に残る人の割合の中央値3.9%、30日後3.3%。記録型と仲間で支え合う型は比較的残った（https://pmc.ncbi.nlm.nih.gov/articles/PMC6785720 ） | 観察研究、分野は限られる |
| 連続記録が途切れると戻ってこない | Silverman & Barasch（2023）*Journal of Consumer Research* 49(6)、7つの研究：記録の上で連続がつながって見えるかどうかで、その後の行動が変わる。自分のせいで途切れたと感じると差が大きくなり、**修復できると差が小さくなる**（https://www.colorado.edu/business/faculty-research/2023/04/19/or-track-how-broken-streaks-affect-consumer-decisions ） | 査読つき、複数の研究。他のチームの追試は未確認 |
| 「どうにでもなれ」効果 | Polivy, Herman & Deo（2010）*Appetite*：「もう失敗した」と思うと自制が崩れる（検索抜粋） | 食事の研究。アプリに当てはまるかは未確認 |
| 罪悪感、または「十分分かった」 | Epstein ほか（2016, CHI）：記録をやめた193人の理由は6種類。Fitbit をやめた人の約半数が罪悪感を持っていた。満足してやめた人には、再開の勧めが説教くさく感じられた（https://www.washington.edu/news/?p=49474 ） | 質問紙と聞き取り |
| 知らせだけでは習慣にならない | Stawarz, Cox & Blandford（2015, CHI）：知らせはくり返しを助けるが、習慣として自動化するのを妨げた。**出来事をきっかけにする**と自動化が進んだ（https://orca.cardiff.ac.uk/130626 ） | 4週間の研究と115本のアプリの調査 |
| 1日休んでも本当は問題ない | Lally ほか（2010）*European Journal of Social Psychology* 40(6)：1回休んでも習慣の形成に大きな影響はない。定着までの日数は18〜254日と個人差が大きい（https://core.ac.uk/download/70653523.pdf ） | 小規模（分析は82人） |
| 予備を持たせると続く | Sharif & Shu（2017）*Journal of Marketing Research* 54(3)：高めの目標に「予備日」を付けると、粘り強さと失敗からの立ち直りが増えた（https://anderson-review.ucla.edu/emergency-reserves/ ）。Duolingo はこれを根拠に Streak Freeze を導入した（https://blog.duolingo.com/how-duolingo-streak-builds-habit ） | 複数の実験 |
| 同じ質問で飽きる・AI が浅い | Reflectly のレビュー | 体験談 |
| 機能が多すぎてすぐ書けない | Stoic のレビュー | 体験談 |
| 有料化への反発 | Five Minute Journal、Reflectly のレビュー | 体験談 |

参考：Quirk（CBT の練習アプリ）の作者は、有料購読の事業にしたところ「良くなった人が解約し、良くならない人が続ける」ことに気づいた。**事業の仕組みが成功を失敗として扱ってしまう**として、方向を変えた（https://github.com/Flaque/quirk の README）。思考の練習も「上達したら使わなくなる」のが成功の形でありうる。続けた日数を成功の物差しにしない根拠になる。

**振り返りを書くことの効果（強さの評価）**
- 筆記開示（つらい体験を書く方法）：メタ分析の結果は小さいか割れている。Frattaroli（2006）*Psychological Bulletin* は r=.075。Mogk ほか（2006）は有意な効果なし。しかも「観察→仮説」の振り返りとは別の方法。
- Di Stefano, Gino, Pisano & Staats（2014）HBS ワーキングペーパー 14-093：研修の終わりに15分振り返りを書いた群は、最終テストが22.8%良かった（https://www.library.hbs.edu/working-knowledge/reflecting-on-work-improves-job-performance ）。ただし雑誌に載ったかは未確認で、現場の実験は1社だけ、追試も未確認。**効果の大きさを約束する根拠には使わない。**

---

## 4. タスク管理＋カレンダー

### 4-1. Sunsama：朝の計画と夕方の締め（いちばん大事な手本）

https://help.sunsama.com/docs/usage-guides/daily-planning/legacy 、https://help.sunsama.com/docs/usage-guides/daily-highlights

**朝の計画（5つの手順を1画面ずつ進む）**
1. **昨日の振り返り**：前日に夕方の締めをしていない場合だけ出る。昨日のタスクを完了にするか直す。
2. **今日のタスクを集める**：カレンダーの予定、連携しているツール、あとでやる置き場、週の目標から引き込むか、新しく作る。
3. **詰め込みすぎの確認**：見積時間の合計を上限と比べ、超えたら警告する。「この予定だと何時に終わるか」と「何時に終わりたいか」を並べて見せる。9時〜17時なら作業は約5.5時間に抑えるよう、目安も示す。
4. **計画を確定する**：順番を並べ、必要ならカレンダーに時間枠を取る。
5. **計画を共有する**：起こりそうな障害を書き、必要なら Slack などに投稿する。

**夕方の締め（Daily Shutdown）**
- 朝に決めた終業時刻にアプリの中で知らせが出る。
- 流れ：今日やったことと時間の内訳を見る → 成果のまとめ（自動で選んだものを、利用者が削ったり並べ替えたりする）→ **自分で振り返りを書く**（うまくいったこと、気がかり、障害）→ 保存する。
- 終わらなかったタスクは翌日に持ち越す。
- 夕方の締めを飛ばすと、**翌朝の計画が「昨日の振り返り」から始まる**。罰ではなく、次の入り口に回すだけ。

**週の儀式**：先週の目標を見直す → 振り返りを書く → 今週の目標を立てる（https://help.sunsama.com/docs/weekly-planning ）。

**その他**
- ゲームのような要素や連続記録のない、落ち着いた作りだというレビューがある（https://www.asianefficiency.com/schedule-management/sunsama-review/ ）。
- 年払いで月$17、月払いで$22。無料プランはない（https://www.sunsama.com/pricing ）。

**このアプリへの置き換え**

| Sunsama | このアプリ |
|---|---|
| 昨日の振り返り | 期日の来た検証を片づける |
| タスクを集める | 今日の予定と、観察したい場面を選ぶ |
| 詰め込みすぎの確認 | 未検証の仮説が多すぎたら警告する |
| 夕方の締め | 今日の観察の事実と推測の確認、検証、「前はこう思った→今はこう思う」 |

### 4-2. その他の製品

**Akiflow**（https://akiflow.featurebase.app/en/help/articles/0805246-rituals ）
- 何をするものか：メールや Slack などをひとつの受信箱にまとめ、ドラッグで時間を割り当てる。
- 朝の計画：昨日のまとめから始まる。最後に「終業の時間枠」をカレンダーに置く。
- 夕方の締め：「**Rate your Day**（1日を1タップで評価）」がある。
- 締めを飛ばすと次の計画の最初の手順になる。月$34、年払いで月$19。

**Todoist**（https://todoist.com/help/articles/use-the-calendar-integration-rCqwLCt3G ）
- 同期する範囲：日付・時刻・所要時間のあるタスクだけ。終日の予定にするのは設定でオンにしたときだけ。
- 書き込み先：**「Todoist」という専用カレンダーを作り、既存のカレンダーには書かない**。
- Google 側での変更：専用カレンダーの中で名前や日時を変えると、タスクに反映される。**新しく予定を足してもタスクにはならない。**
- 他のカレンダーの予定：今日と今後の画面に読み取り専用で見せるだけ。
- 完了したタスク：予定の名前に✓を付けて残す。
- 注意：同期を切ると、専用カレンダーの予定は全部消える（手で足した予定も含む）。
- 続けさせる工夫：Karma というポイント。休みの曜日と休暇モードで連続記録を守れる（https://todoist.com/help/articles/introduction-to-karma-OgWkWy ）。

**TickTick**（https://help.ticktick.com/articles/7055781593733922816 ）
- 2つのつなぎ方がある。
  - Google の予定を「予定のまま」見せる購読
  - リストと既存のカレンダーを結んで双方向に同期する連携：時刻のあるタスクが予定になり、今日以降の予定がタスクになる
- 過去の予定は取り込まない。連携をやめた後に消したいときは、予定の出どころ（`source`）が「TickTick」のものを探して消す。
- 予定の出どころをアプリ名にしておく考え方が借りられる。

**Things 3**（https://culturedcode.com/things/support/articles/2803583/ ）
- カレンダーの予定を「今日」の画面の上にまとめて見せるだけ。タスクをカレンダーに書き出すことはできない。
- 「This Evening」という夜用の欄がある。
- 借りられるもの：**予定は上、タスクは下**という「今日」画面の作り。

**Motion**（https://www.usecarly.com/blog/motion-ai-review/ 、競合他社の記事）
- AI が空き時間に自動でタスクを詰め込み、ずらし続ける。
- 「スケジューラを見張らないといけないなら意味がない」「管理されている気分になる」という批判がある。
- → **自動で動かすのは提案までにして、確定は人がする。**

**Google Tasks**（https://developers.google.com/workspace/tasks/reference/rest/v1/tasks ）
- API の期限（`due`）は**日付だけで、時刻は捨てられる**。変更を知らせる仕組み（watch）もない。
- →「金曜15時に仮説Aを確かめる」のような**時刻のある枠は、Calendar の予定にするしかない**。

**参考**
- Reclaim.ai（習慣ごとに候補の時間帯を決め、空きに自動で置く）
- Morgen
- Notion Calendar（Google の予定を Notion のデータベースへ取り込むことはまだできない）

### 4-3. 同期のしかたの比較

| 製品 | 方向 | タスク→予定 | 予定→タスク | 書き込み先 | Google 側で予定を動かすと |
|---|---|---|---|---|---|
| Sunsama | 双方向（公式の表記） | 時間枠を取ったもの | 会議の取り込み（確認してから、または自動） | 選んだ既定のカレンダー／分類ごと／内部のカレンダー | 反映されるはず（Apple の例）。Google は未確認 |
| Akiflow | 双方向（公式の表記） | 時間枠を取ったもの | 未確認 | 未確認 | 未確認 |
| Todoist | 専用カレンダーの中だけ双方向 | 日付＋時刻＋所要時間があるもの | **しない** | **専用の「Todoist」カレンダー** | 日時・名前がタスクに戻る |
| TickTick（連携） | 双方向 | 時刻のあるタスク | 今日以降の予定がタスクになる | 選んだ既存のカレンダー | タスクに戻る |
| Things 3 | 読むだけ | なし | 表示だけ | ― | 表示が変わるだけ |
| Motion | 自動で配置 | 自動 | 未確認 | 未確認 | 自動で組み直す |
| Google Tasks | ― | 日付だけ | ― | Calendar の画面に表示 | ― |

### 4-4. Google の API で設計に関わる事実

- **権限の範囲（スコープ）**（https://developers.google.com/workspace/calendar/api/auth ）
  - `calendar.app.created`：アプリが作ったサブカレンダーを作り、その中の予定だけを読み書きできる。いちばん狭い。
  - `calendar.events`：すべてのカレンダーの予定を読み書きできる。
  - 読み取り専用のもの：`calendar.events.readonly`、空き時間だけを見る `calendar.freebusy`。
  - Google は「できるだけ狭い範囲を選べ」と求めている。
  - **`app.created` だけでは、朝の計画で「今日の会議」を表示できない**。読み取りの範囲を足すかどうかは、設計で決める（推論）。
- **カレンダーを作る**：`calendars.insert`。必須の項目は名前（summary）だけ（https://developers.google.com/workspace/calendar/api/v3/reference/calendars/insert ）。
- **予定にアプリのIDを持たせる**：`extendedProperties.private`。`privateExtendedProperty=キー=値` で検索できる。キーは44文字まで、値は1024文字までで、**超えると警告なしで捨てられるか切り詰められる**（https://developers.google.com/workspace/calendar/api/guides/extended-properties ）。
- **予定のその他の項目**（https://developers.google.com/workspace/calendar/api/v3/reference/events ）
  - 予定のIDは自分で決められる。
  - `source.url` にアプリの中のページを入れられる。
  - `transparency=transparent` にすると、予定ありとして時間を塞がない。
  - 消された予定は `status=cancelled` で届く。
- **差分の同期**：`nextSyncToken` を保存して次に渡す。410 が返ったら、全部取り直す（https://developers.google.com/workspace/calendar/api/guides/sync ）。
- **変更の通知（events.watch）**：受け取り先は HTTPS が必須。通知は「変わった」とだけ知らせ、中身は別に取りに行く。有効期間の既定は7日で、作り直しが要る。**100%確実ではない**ので、通知なしでも同期できる作りにする（https://developers.google.com/workspace/calendar/api/guides/push ）。
- **利用の上限**：1つのプロジェクトで毎分10,000件、1人で毎分600件。毎日0時に一斉に同期するのは避ける（https://developers.google.com/workspace/calendar/api/guides/quota ）。
- **OAuth の審査**
  - センシティブな範囲は、公開前に Google の審査が要る。必要なもの：公開したホームページ、同じドメインのプライバシーポリシー、範囲ごとの理由、デモ動画。最大10日ほどかかる。自分と数人の知り合いだけで使うなら審査は要らない（https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification ）。
  - 審査前のアプリは、新しい利用者が合計100人まで（https://support.google.com/cloud/answer/7454865?hl=en ）。
  - `calendar.app.created` 自体がセンシティブ扱いかは**未確認**で、Cloud Console の表示で確かめる必要がある。
- **テスト中のリフレッシュトークンは7日で切れる**（7-1 を参照）。

---

## 5. 推理ゲームの仕組み（中心の画面の参考）

### 5-1. 比較表

| 作品 | 推理の入れ方 | 判定のタイミング | 当てずっぽうの防ぎ方 | 結果の返し方 | このアプリが借りられるもの |
|---|---|---|---|---|---|
| The Case of the Golden Idol（2022） | 現場で集めた語を、文の穴に入れる | 区画ごと（全部埋めてから） | 選べる語が多い。どこが違うかは言わない | 確定／「誤り2つ以下」／不正解 | 観察語のカード＋文の型 |
| Return of the Obra Dinn（2018） | 名前・死に方・犯人を選ぶ | **3件そろうごと**（最後は2件ずつ） | まとめて確定 | 手書きの記入が活字になり、変えられなくなる | 予想を期日に「活字化」する |
| Sherlock Holmes Consulting Detective（ボードゲーム） | 訪ねる住所を選ぶ → 最後に質問に答える | 最後にまとめて | 訪問1回ごとに −5点（ホームズの手数と比べる） | 得点＋ホームズの解決を読む | 手数の記録と、手本との比較 |
| Her Story（2015） | 検索語を打つ | なし | 結果は5件まで | 最後にチャットで「分かったか」と聞かれる | 振り返りの問い |
| Sherlock Holmes: Crimes & Punishments（2014） | 手がかりを2つつなぐと結論になる（推理ボード） | 最後に。答え合わせは任意 | ほぼない | 答え合わせは任意。後日に手紙で結果が届く | 期日の知らせ、任意の答え合わせ |
| Sherlock Holmes Chapter One（2021） | 2つを組み合わせる。人物像を2択で選ぶ | 遅れて、またはほとんど示さない | 合わない組み合わせは赤く表示するだけ（罰なし） | 相棒が指摘する | 人物観察カード |
| 逆転裁判 | 証言の問題の1文に、証拠を「つきつける」 | その場で | 失敗の上限（ゲージ） | 「異議あり！」、ゲージが減る | 自分の仮説への尋問 |
| 逆転検事 | 2つの考えをつなぐ（ロジック） | その場で | 真相ゲージ | 新しい情報が出る | 結論の組み立て |
| 大逆転裁判の共同推理 | ホームズの推理の語を差し替える | その場で | 未確認 | 推理が正しい方向に戻る | **行き過ぎた推理を直す練習** |
| ダンガンロンパ | 発言の弱点を証拠で撃つ | その場で（時間制限） | 体力、時間 | 体力が増減する | 弱点の強調表示 |
| Shadows of Doubt | 報告書に書く | 提出したとき | 罰金。真犯人がまた殺す | 任意の欄が当たるとボーナス | 必須の欄と任意の欄 |
| Duck Detective | 手帳の文の穴に語を入れる | 埋めてから | 誤りの「おおよその数」 | 易しいモードでは誤りを強調表示 | Golden Idol の易しい版 |
| ウミガメのスープ | はい／いいえで答えられる質問 | 質問ごと | 答えを知っているのは出題者だけ | はい／いいえ／関係ない | 反証できる予想 |
| Disco Elysium | 「思考」を枠に入れる | 時間がたってから | 枠の数に限りがある | 利点と不利な効果 | 同時に持てる仮説の数を絞る |

### 5-2. 表に入らなかった補足と出典

- **Golden Idol**：表にない補足は3つ。
  - 誤りが3つ以上のときの表示は、転載記事の検索抜粋でしか確かめていない。
  - それでも最後の数語を総当たりした、というレビューがある。
  - 開発者は、数値よりプレイテストでの聞き取りと観察を重んじた。
  - 出典：https://en.wikipedia.org/wiki/The_Case_of_the_Golden_Idol 、https://www.rpgfan.com/review/the-case-of-the-golden-idol/ 、https://gamedeveloper.com/business/-the-case-of-the-golden-idol-i-used-frequent-testing-to-improve-its-mystery-solving
- **Obra Dinn**：3件ずつ確定させる目的は当て推量を抑えること（Wikipedia）。ただし作者自身がそう語った資料は見つけていない。
  - 出典：https://en.wikipedia.org/wiki/Return_of_the_Obra_Dinn 、https://scientificgamer.com/thoughts-return-of-the-obra-dinn
- **Consulting Detective**：「見えない基準で採点される」「ホームズが嫌味」という不満がある。→ 点数はおまけにして、**手本の推理と並べて読むこと**を主役にする。
  - 出典：https://rulespal.com/sherlock-holmes-consulting-detective/rulebook 、https://www.wargamer.com/sherlock-holmes-consulting-detective/review
- **Frogwares の作品**
  - Crimes & Punishments：1つの事件に解決が3〜5通りある。「正当な結論なのに完全に間違っている」ことが許される。
  - Chapter One：集中モードでは周りが灰色になり、人物だけが浮き上がる。人物像は、食い違う2つから1つを選ぶ。
  - 出典：https://kotaku.com/im-enjoying-a-mystery-game-that-lets-me-get-it-wrong-1823052115 、https://adventuregamehotspot.com/review/266/sherlock-holmes-chapter-one
- **逆転裁判と共同推理**
  - 「ゆさぶる」で証言の説明を求め、「つきつける」で矛盾を示す証拠を出す（https://ja.wikipedia.org/wiki/逆転裁判シリーズ ）。
  - 大逆転裁判の共同推理では、観察眼が鋭すぎて行き過ぎたホームズの推理を、成歩堂が正す（https://www.famitsu.com/news/201504/25077536.html ）。続編では「真相の向こう側まで行ってしまうホームズの超推理を、そっと入れ替えて真実へと戻していく」と説明されている（https://www.inside-games.jp/article/2017/06/03/107612.html ）。
- **その他の出典**：Her Story https://en.wikipedia.org/wiki/Her_Story_(video_game) 、Duck Detective https://en.wikipedia.org/wiki/Duck_Detective:_The_Secret_Salami （開発者は Golden Idol の仕組みを「盗んだ」と明言）

### 5-3. このアプリで使える「推理の入れ方と確かめ方」の型

1. **集めた語で文の穴を埋める**（Golden Idol）：観察した事実を短い語のカードとして集め、「[人]は[状態]だ。根拠は[事実]。正しければ[日時]までに[予想]が起きる」という文の型に入れる。**文の型そのものが、事実と推測を分けさせる。**
2. **手書きから活字へ**（Obra Dinn）：予想は登録した時点で「手書き」として見せる。期日が来たら「活字」にして書き換えられなくする。**このアプリで一番の問題は、総当たりよりも「後から予想を書き換える後知恵」**なので、これがいちばん効く。
3. **まとめて答え合わせ**（Obra Dinn）：当たり外れの集計は、3件そろうごとに見せる。1件ずつで一喜一憂させない。
4. **誤りの数だけ言う**（Golden Idol、Duck Detective）：「どこが違うか」でなく「近いかどうか」だけを返す。教材の練習問題に向く。
5. **自分の仮説への尋問**（逆転裁判）：仮説を1文ずつ出し、各文に「ゆさぶる（なぜそう言える？）」と「つきつける（反対の事実を出す）」をする。
6. **共同推理**（大逆転裁判）：利用者が集めた事実から、わざと飛躍した推理を見せる。利用者は飛躍している語を見つけ、観察した事実に差し替える。**事実と推測を見分ける練習として、題材にいちばん合う。** ホームズに詳しい作り手の持ち味が生きる部分でもある。
7. **先に決めて、答え合わせは後日**（Crimes & Punishments の手紙）：予想の期日に Google カレンダーから知らせ、結果を確かめさせる。
8. **合わない組み合わせは赤く示すだけ**（Chapter One）：考えている途中では罰を与えない。
9. **手本の推理と並べて読む**（Consulting Detective）：練習問題では、最後に「ホームズならこう見た」という手本を読ませる。
10. **同時に持てる仮説の数を絞る**（Disco Elysium）：未検証の仮説は、たとえば5件まで。

---

## 6. 医療・施術の推理を練習する道具と、施術記録（カルテ）アプリ

### 6-1. 臨床推論の練習ツール

| 製品 | 仮説の入れ方 | 採点・比較 | 結果の返し方 | 価格 |
|---|---|---|---|---|
| Human Dx | 自由に書く。順位付きのリスト。所見を1つずつ開く | 効率（何個目の所見で正解を挙げたか）、正確さ（正解が何位か）、他の人との比較 | すぐに正誤が出る | 無料（現在の状況は未確認） |
| Isabel DDx | 所見を入れると候補の病名が出る | なし（考える手助け） | 見逃してはいけない病気の表示 | 個人 年149〜199ドル |
| CASUS 臨床推論ツール | 概念図（所見・鑑別・検査を線でつなぐ）、2〜3文の要約、確信度 | 専門家の概念図との一致、要約の言葉 | 外れたときに、どの偏りが当てはまるか説明する | 研究用 |
| Digi-DDx（OSF） | 病歴を読み、検査を選ぶ | **熟練した医師の検査費用と比べる** | 間違った選択にも解説が付く | 無料 |
| Body Interact／Full Code | 仮想の患者に対応する | スコア | 終わった後の振り返り（デブリーフ） | 問い合わせ |
| Figure 1 | 症例を投稿し、コメントで議論する | なし | 仲間が答える | 未確認 |

**Human Dx**（Chatterjee ほか, *JAMA Netw Open* 2019, https://jamanetwork.com/journals/jamanetworkopen/fullarticle/2720591 ）
- 流れ：短いタイトルと最初の所見が出る → 考えられる病名を書く → 追加の所見を1つずつ開く → 病名のリストを途中でいつでも並べ替え・追加・削除できる。
- 採点：「何個目の所見で正解を初めて挙げたか」（効率）と、「最後に正解が何位にあったか」（正確さ）。経験が多い人ほど点が高かった（後ろ向きの研究、1,738人）。
- 集団の知恵：1人の正答率62.5%に対し、9人の答えをまとめると85.6%になった（Barnett ほか 2019, https://pmc.ncbi.nlm.nih.gov/articles/PMC6484633 ）。
- 限界：所見が最初から整理されて出てくるため、現実の情報集めとは違う。論文自身がこれを限界として挙げている。
- 借りられるもの：**観察を1件足すごとに仮説の順位を付け直し、その履歴を残す**。「何件目の観察で当たりの仮説を挙げたか」を振り返りの指標にする。

**Isabel**（https://uk.isabelhealthcare.com/isabel-faqs ）
- 「診断を告げたり次の手を指示したりはせず、考えられる病名の幅を広げるためのもの」と明言している。
- 借りられるもの：「答えを出さず、候補を広げる」という言い方。

**CASUS の臨床推論ツール**（Hege ほか, *JMIR Med Educ* 2017, https://pmc.ncbi.nlm.nih.gov/articles/PMC5691243/ ）
- 入力するもの：
  - 陰性の所見（「〜はなかった」）
  - 病名ごとの「見逃し禁止」「除外済み」の印
  - 確信度のスライダー
- 外れたときは、5つの偏りのうちどれに当てはまるかを説明する：早く結論を閉じる、思い出しやすいものを選ぶ、確かめたい証拠だけ探す、典型に当てはめる、元々の頻度を無視する。
- 課題：利用者が作った線や項目は少なかった。使いやすさの評価に答えたのは10人だけ。

**診断推論の考え方と、その効果の強さ**

| 考え方 | 要点 | 出典 | 強さ |
|---|---|---|---|
| 二重過程（速い直観と遅い分析） | **偏りを教えるだけでは効果がない**。知識を組み直す方法には、小さいが一貫した効果がある | Norman ほか, *Acad Med* 2017（https://pubmed.ncbi.nlm.nih.gov/27782919/ ） | 総説 |
| 自分の考え方を振り返る（メタ認知） | 問題から一歩引いて考え直す | Croskerry, *Acad Med* 2003;78:775（https://pubmed.ncbi.nlm.nih.gov/12915363/ ） | 理論の論考 |
| チェックリスト | 一般用、鑑別用、病気ごとの落とし穴用、の3種類を提案 | Ely, Graber, Croskerry, *Acad Med* 2011;86:307（https://pubmed.ncbi.nlm.nih.gov/21248608/ ） | 提案だけ（著者自身が「厳密な評価はしていない」と書いている） |
| 手順に沿って考え直す | 研修医36人の実験。直観で答えると偏りが出たが、手順に沿って考え直すと正確になった | Mamede ほか, *JAMA* 2010（https://pubmed.ncbi.nlm.nih.gov/20841533/ ） | 小規模な実験 |
| 推論の道具（チェックリスト、考え直しなど）全体 | 29研究・2,732人で、効果は **g=0.20（小さい）**。道具の種類による差はなかった。実際の診療の場での研究は2件だけ | Staal ほか, *BMJ Qual Saf* 2022（https://pmc.ncbi.nlm.nih.gov/articles/PMC9685706/ ） | 系統的レビューとメタ分析。確からしさの評価（GRADE）は中等度 |
| チェックリスト | 改善7件、改善なし6件と、結果が分かれた | Al-Khafaji ほか, *BMJ Open* 2022（https://psnet.ahrq.gov/issue/checklists-reduce-diagnostic-error-systematic-review-literature-using-human-factors-framework ） | 系統的レビュー |

→ 結論：**「偏りの一覧を見せる」より「別の仮説と、反対の事実を書かせる」「決まった手順で考え直させる」方が根拠に合う。** ただし効果は小さいので、大きく言わない。

### 6-2. 記録の形：SOAP と POMR

- **Weed（1968, *NEJM* 278:593–600, 652–657）**：問題ごとに記録する方式（POMR）を広めた。問題のリストと SOAP は、ほぼ世界共通になった。
  - Weed は後のインタビューで、所見から「支える証拠・反対する証拠つきで」可能性を並べる道具を、記録と直接つなぐべきだと語っている（Jacobs, *Perm J* 2009, https://pmc.ncbi.nlm.nih.gov/articles/PMC2911807/ ）。
- **SOAP の中身**（https://en.wikipedia.org/wiki/SOAP_note ）
  - S＝本人の言葉（主訴、経過）
  - O＝診察や測定で得た所見
  - A＝見立てと、考えられるものの順位
  - P＝計画（Aの各項目に対応させる）
- **日本語の解説**では、A に判断の理由と鑑別を書き、P には「**効かなかった場合にどうするか**」まで書くとしている（https://www.phchd.com/jp/medicom/park/tech/ehr-soap ）。
- **このアプリとの対応**

| SOAP | このアプリの段階 |
|---|---|
| S と O | 事実（誰が言ったことか、自分が見たことか、を分けて書く） |
| A | 仮説（2つ以上） |
| P | 予想と検証の予定（Google カレンダーに入れる） |
| 次の回の S と O | 答え合わせ |

  - 施術者は SOAP を学校で習っている（鍼灸の専門学校のシラバスに「SOAP を説明できる」とある。https://www.neec.ac.jp/assets/contents/documents/portal/syllabus/hachioji/2026/portal_syllabus_hachioji_2026_g26s30081.pdf ）。だから**新しい型を覚えさせなくて済む**。

### 6-3. 日本の施術記録（カルテ）アプリ

| 製品（提供元） | 記録するもの | 予約・LINE など | 価格 |
|---|---|---|---|
| らいらいリザーブ（ユーキサイエンス） | 人体図への印、部位ごとのこり・痛み、**痛みの数値（NRS）・動く範囲**、写真。**NRS の平均から改善度を計算** | 予約、会計、患者のマイページでカルテを公開 | 月2,200円〜、無料プランあり |
| 鍼灸つながるカルテ／はりのマイカルテ（セイリン） | 写真・痛みの尺度つきのカルテを患者と共有 | 予約、チャット、問診 | 院側の価格は未確認 |
| BEFORE AFTER（ならでわ） | 前後の写真を並べる・重ねる、角度の計測、姿勢のスコア（試験版） | 問診のURL、電子同意 | 0〜9,800円/月 |
| カルッテ（クロスリンク） | AI による姿勢分析（施術の前後を比べる） | 予約、問診、会計 | 非公開 |
| RIPICLE、LiMEsalon、リピッテ など | 顧客の管理とカルテ | LINE で予約・連絡 | 月1,980円〜 |
| R-body（aiwell） | SOAP をトレーニング向けにしたもの（A→P）。もう一度測る欄はない | ― | 無料 |

出典：https://www.aspicjapan.org/asu/article/78524 、https://apps.apple.com/jp/app/apple-store/id1521444747 、https://prtimes.jp/main/html/rd/p/000000010.000023494.html 、https://apps.apple.com/kh/app/r-body/id1548236733

- **今回確かめた範囲では、仮説・別の仮説・次回の検証を整理して扱うカルテアプリは見つからなかった。** 中心は記録（人体図・写真・数値）、予約、LINE、会計。
- 英語圏の Jane、ClinicSense、ChiroTouch も、テンプレートと時短（AI の書き起こし、決まり文句の入力）が売りだった。
- 「ハリカル」「カルテメイト」などの名前は、アプリとしては確認できなかった。
- 続けさせる工夫は共通して、**数値と写真で変化を見せること**と、患者にも見える画面。
- → 差をつける余地：「前回の見立て（仮説）と予想が、今回の数値で当たったか」を自動で並べる。

### 6-4. 線引き（要点のみ。法律の助言ではない）

- **医師法17条**「医師でなければ、医業をなしてはならない。」（https://laws.e-gov.go.jp/api/1/articles;lawId=323AC0000000201;article=17 ）。施術者向けの画面では、A 欄を「見立て・仮説」と呼び、**病名を判定したり提案したりしない**のが安全（解釈）。
- **病歴は要配慮個人情報**（個人情報保護法2条3項）。取得には前もって本人の同意が要る（20条2項）。扱う件数による下限はない（https://laws.e-gov.go.jp/api/1/articles;lawId=415AC0000000057;article=20 ）。
- **医療情報システムの安全管理に関するガイドライン**は、最新が第7.0版（2026年6月）（https://www.mhlw.go.jp/stf/shingi/0000516275_00006.html ）。施術所が対象に入るかは、読めた範囲では確かめられなかった。
- 文言の手本：Ubie は「医療機器ではない、診断や治療が目的ではない、医師の代わりではない」と明記している（https://ubie.app/ ）。
- **最初の版（親子用）では患者情報を扱わない。** 施術者向けは別の段階として、同意と保存方法を設計してから広げる。

---
## 7. OSS（そのまま使える部品・読んで学べるコード）

調べ方：GitHub の公開リポジトリを `gh` コマンドで直接開き、ライセンス（LICENSE ファイル／GitHub の判定）、最終更新日、`composer.json` の中身（Laravel を使っているか）を確かめた（2026-10-31 時点）。

### 7-1. Googleカレンダー連携の部品

| 部品 | ライセンス | 状態 | 利用者ごとの OAuth | このアプリでの扱い |
|---|---|---|---|---|
| [laravel/socialite](https://github.com/laravel/socialite) | MIT | 活発 | ○（ログインと同時にトークン取得） | **使う**（Googleログイン） |
| [googleapis/google-api-php-client](https://github.com/googleapis/google-api-php-client)（`google/apiclient`） | Apache-2.0 | 活発 | ○（トークンを渡せば誰のカレンダーでも） | **使う**（Calendar API 呼び出し） |
| [spatie/laravel-google-calendar](https://github.com/spatie/laravel-google-calendar) | MIT | 活発（v3.8.5, 2026-02） | ×（1つのトークンファイル固定） | 使わない |
| [spatie/icalendar-generator](https://github.com/spatie/icalendar-generator) | MIT | 活発 | 不要（iCal 配信） | 予備案（購読用URL） |
| [lorisleiva/blog-google-calendar](https://github.com/lorisleiva/blog-google-calendar) | **ライセンス表記なし** | 2020年で停止 | ○（GoogleAccount モデル） | 設計を読むだけ（コードは写さない） |
| その他の小さなパッケージ（`dimafe6/…`, `innoboxrr/google-calendar` など） | MIT／表記なし | 星0〜数個、説明文が未完成のもの多数 | 一部○ | 使わない（保守が続く保証がない） |

**spatie/laravel-google-calendar が利用者ごとの OAuth に向かない理由（コードで確認）**
- 設定の既定値が `'default_auth_profile' => env('GOOGLE_CALENDAR_AUTH_PROFILE', 'service_account')`。README の手順も「サービスアカウントを作り、そのメールアドレスにカレンダーを共有する」流れ（[README](https://github.com/spatie/laravel-google-calendar)）。
- OAuth のプロファイルもあるが、`src/GoogleCalendarFactory.php` は `$client->setAccessToken(file_get_contents($authProfile['token_json']))` と**設定ファイルに書かれた1つのトークンファイルを読むだけ**。利用者ごとのトークンを DB から渡す口がない（[ソース](https://github.com/spatie/laravel-google-calendar/blob/main/src/GoogleCalendarFactory.php)）。
- スコープは `Google_Service_Calendar::CALENDAR`（カレンダー全体の読み書き）で固定。このアプリが欲しい「自分が作った予定だけ」などの狭い権限に絞れない。
- 「DB に保存したユーザーごとのトークンを使えるようにする」PR #232（2021年）は、作者（freekmurze）が「パッケージを小さく保つため」「自分では必要がなく保守したくない」としてマージしなかった。関連の要望（#179, #228, #297, #298, #302）がコメントで挙げられている（[PR #232](https://github.com/spatie/laravel-google-calendar/pull/232)）。
- 結論：**1人の管理者が1つのカレンダーに書く用途（店の予約表など）向け**。Googleログインした各利用者のカレンダーに書く今回の用途には、Socialite でトークンを受け取り、`google/apiclient` を直接呼ぶのが筋。

**Socialite で押さえる点（公式文書で確認）**
- `->scopes([...])` でスコープを足せる。`->with([...])` で追加パラメータを渡せる（[Laravel 12.x Socialite](https://laravel.com/docs/12.x/socialite)。2026-10時点の最新は 13.x）。
- コールバックで `$user->token` `$user->refreshToken` `$user->expiresIn` が取れる。これを users（または別テーブル google_accounts）に**暗号化して**保存する。
- Google でリフレッシュトークンをもらうには `access_type=offline` が必要で、リフレッシュトークンは最初の認可のときだけ返る。`prompt=consent` で再同意させると再発行される（[Google OAuth 2.0 for Web Server Applications](https://developers.google.com/identity/protocols/oauth2/web-server)）。
- **落とし穴**：OAuth 同意画面が「外部・テスト中」のままだと、名前・メール・プロフィール以外のスコープ（＝カレンダー）を求めた場合、リフレッシュトークンは**7日で失効**する。また「1つの Google アカウント×1つのクライアントID につきリフレッシュトークンは100個まで」で、超えると古いものが黙って無効になる（[Using OAuth 2.0 to Access Google APIs](https://developers.google.com/identity/protocols/oauth2)）。→ 開発中に「1週間後に急に同期が止まる」現象はこれ。家族など数人だけで使う段階でも、テスト中のままなら毎週つなぎ直しが要る。

**Loris Leiva の連載（Laravel で Google カレンダー同期）**
- 3部構成：①利用者のカレンダーと予定を取ってきて Laravel のモデルに対応させる ②定期的な同期 ③Webhook（Google のプッシュ通知）での同期（[リポジトリの README](https://github.com/lorisleiva/blog-google-calendar)）。
- `app/` に `GoogleAccount.php` `Calendar.php` `Event.php` `Synchronization.php` `User.php` があり、「1人の利用者が複数の Google アカウントを持ち、アカウントごとに同期状態を持つ」設計（ファイル構成で確認。中身の詳細は未確認）。
- リポジトリにライセンス表記がないため、**コードの写しは不可**（著作権者の許諾がないと再利用できない）。考え方だけ借りる。
- 一般論として、プッシュ通知は取りこぼしがあるので「定期的な差分同期（syncToken）」を安全網として併用せよ、という解説がある（[Nango のブログ](https://nango.dev/blog/how-to-build-a-real-time-google-calendar-api-integration)、第三者の解説。検索抜粋で確認し、本文は未読）。

**別案：iCal の購読URLを配る（一方向・OAuth なし）**
- Laravel 製の業務管理 OSS「Leantime」は、カレンダー連携を「外部 iCal の購読（取り込み）」と「自分の予定を iCal で配信（書き出し）」で実現している。`spatie/icalendar-generator` を使用（[Leantime の Calendar サービス](https://github.com/Leantime/leantime/blob/master/app/Domain/Calendar/Services/Calendar.php)）。
- 長所：Google の審査も OAuth も要らない。短所：Google カレンダーが購読URLを読み直す間隔は**公式の数字は未確認**で、第三者の解説では「数時間〜1日程度、利用者は変えられない」（検索抜粋で確認。[Cheqroom](https://knowledge.cheqroom.com/helpcenter/how-often-does-google-update-its-calendar-feeds)、[Nocal](https://nocal.app/help/ics-and-subscriptions/why-subscribed-calendars-dont-update-instantly)）。「明日の朝に確かめる」予定が当日に反映されないことがあるので、主役には向かない。Google ログインを使わない利用者向けの予備手段としてなら有用。

### 7-2. Laravel で作られた、読んで学べるアプリ

GitHub で「laravel habit tracker」「laravel journal」「laravel task manager」を検索すると、上位は星0〜40程度の学習用の小品ばかりで、そのまま土台にできる完成度の Laravel 製タスク・日記・習慣アプリは見つからなかった。代わりに、大きな Laravel 製 OSS の中に参考になる部分がある。

| リポジトリ | 中身 | Laravel | ライセンス | 参考になる所 |
|---|---|---|---|---|
| [monicahq/monica](https://github.com/monicahq/monica)（星 約2.5万） | 個人向けの人間関係メモ（Personal CRM） | framework ^12, socialite, jetstream | **AGPL-3.0** | `Journal` `Post` `PostTemplate` `PostTemplateSection`（日記を「見出し付きの欄の並び」のひな形で書く）、`Goal` と `Streak`（`goal_id`＋`happened_at` だけの1日1行の連続記録）、`MoodTrackingEvent` |
| [Leantime/leantime](https://github.com/Leantime/leantime)（星 約1.2万） | 目標中心のプロジェクト管理。ADHD・自閉・ディスレクシアの人を意識して作ったと明記 | framework ^12.69, socialite | **AGPL-3.0** | Retrospectives（振り返りボード）、Ideas、Goal canvas、iCal 連携、「My work」ダッシュボード |
| [solidtime-io/solidtime](https://github.com/solidtime-io/solidtime)（星 約9千） | 時間記録 | framework ^13 | **AGPL-3.0** | 新しい Laravel での構成例 |
| [kcal-app/kcal](https://github.com/kcal-app/kcal)（星 約360） | 食事の記録（個人の日誌） | framework ^13, spatie/laravel-tags, medialibrary | MPL-2.0 | 1人用の記録アプリを Laravel で小さく組む例、目標と日々の記録の関係 |
| [BookStackApp/BookStack](https://github.com/BookStackApp/BookStack)（開発の場は Codeberg へ移動と表示） | Wiki | framework ^13.35, socialite | MIT | Socialite を使った外部ログインの組み込み例、素直な MVC の分け方 |

（Laravel のバージョンは各リポジトリの `composer.json` で確認。Monica・Leantime・solidtime の機能名はモデル名・ディレクトリ名・README で確認したもので、画面の動きまでは未確認。）

**ライセンスの注意**：AGPL-3.0 のコードを写して組み込むと、このアプリ全体を AGPL で公開し、ネット越しの利用者にもソースを渡す義務が生じる。Monica・Leantime・solidtime は**設計（テーブルの分け方・機能の切り方）を読むだけ**にして、コードは写さない。MIT／Apache-2.0／BSD のものは著作権表示を残せば使える。

### 7-3. 予想・答え合わせの OSS

| リポジトリ | 技術 | ライセンス | 状態 |
|---|---|---|---|
| [Sage-Future/fatebook](https://github.com/Sage-Future/fatebook) | Next.js, Prisma, tRPC, next-auth, Slack/Discord 連携, Chrome 拡張 | **MIT**（Copyright 2024 Sage） | 公開・更新あり（2026-06） |
| [bellroy/predictionbook](https://github.com/bellroy/predictionbook)（旧 tricycle/predictionbook） | Ruby on Rails | **BSD**（README に記載。GitHub の自動判定は none） | **アーカイブ済み**（読み取り専用） |
| [Metaculus/metaculus](https://github.com/Metaculus/metaculus) | TypeScript ほか | BSD-2-Clause | 活発 |
| [manifoldmarkets/manifold](https://github.com/manifoldmarkets/manifold) | TypeScript | MIT | 活発 |

**Fatebook のデータ構造（`prisma/schema.prisma` で確認）は、このアプリの「予想」テーブルの手本になる。**
- `Question`：`title`（予想の文）、`resolveBy`（答え合わせの期日）、`resolved`／`resolution`（`YES` `NO` `AMBIGUOUS` の3値）／`resolvedAt`、`pingedForResolution`（期日が来たことを知らせ済みか）、`notes`、`tags`。
- `Forecast`：`forecast`（確率。小数）、`comment`、`createdAt`。**1つの問いに何度でも予想を足せる**（考えが変わった履歴が残る）。
- `hideForecastsUntil` と `hideForecastsUntilPrediction`：**自分が予想するまで他人の予想を見せない**設定。→ 親子で同じ出来事に別々に仮説を立て、出し終えてから見せ合う機能にそのまま使える考え方。
- `AMBIGUOUS`（どちらとも言えない）を正式な答えにしているのは重要。日常の検証では「確かめられなかった」「問いの立て方が悪かった」が普通に起きる。

### 7-4. 似た構造を持つ OSS：認知行動療法（CBT）の「思考記録」

- [Flaque/quirk](https://github.com/Flaque/quirk)（GPL-3.0、星 約2,300、保守終了）：CBT の練習アプリ。「自動的に浮かんだ考え → 考えのくせ（認知のゆがみ）を選ぶ → 反論 → 別の考え方」という入力の流れは、「推測 → 根拠の弱さに気づく → 別の説明」と同じ形をしている。
- 作者の README の反省が示唆に富む：有料購読で事業化したところ、利用者は「使わない」「良くなって解約」「良くならないが続ける」に分かれ、**事業の仕組みが成功を失敗として、失敗を成功として扱ってしまう**ことに気づいて方向転換した（[README](https://github.com/Flaque/quirk)）。→ 思考の練習アプリも「上達したら使わなくなる」のが成功の形でありうる。続けた日数を成功の物差しにしない、という設計判断の根拠になる。

---

## 8. 子ども・親子で使える思考の練習

### 8-1. Harvard Project Zero「Thinking Routines（考える型）」

考えの進め方を、決まった問いかけにしたもの。主に授業で使われている。このアプリの各段階の問いかけ文に、いちばん素直に対応する。

| 型 | 問いの流れ | 出典 | このアプリでの使いどころ |
|---|---|---|---|
| See-Think-Wonder | 何が見える？ → どう思う？ → 何が不思議？　話し合う前にそれぞれが書く。「思った物」ではなく「見えた物」を言わせる | https://pz.harvard.edu/resources/see-think-wonder | 観察（事実だけ）→ 解釈 → まだ分からないこと |
| What Makes You Say That? | 何が起きている？ → **何を見てそう言える？**　続けるうちに、聞かれなくても根拠を添えるようになるという | https://pz.harvard.edu/resources/what-makes-you-say | 推測の欄の下に、毎回「そう言える事実は？」を出す |
| Claim-Support-Question | 主張 → 支える根拠 → 問い（まだ説明されていないことは？）。「なぜ正しいかもしれない？」「なぜ正しくないかもしれない？」と促す | https://pz.harvard.edu/resources/claim-support-question | 仮説と証拠、反対の事実 |
| I Used to Think… Now I Think… | 前はこう思った → 今はこう思う | https://pz.harvard.edu/resources/i-used-think-now-i-think | 夜・検証の後の振り返り |
| Tug for Truth | 本当か嘘かの綱引きの図に証拠を貼り、どちらとも言えない部分にも気づく | https://pz.harvard.edu/resources/tug-truth | 判定の「どちらとも言えない」 |

- 資料のライセンスは **CC BY-NC-ND 4.0**（営利に使うのも、書き換えるのも不可）。See-Think-Wonder のページで確認した。文言をそのまま載せず、出典を示して自分の言葉で書く方が安全（法律の判断ではない）。
- 医学教育で効果が試された VTS（1-3 を参照）も同じ系統で、問いは3つだけ。「何が起きている？／何を見てそう言える？／ほかに何が見つかる？」（https://www.gvsu.edu/artmuseum/visual-thinking-strategies-152.htm ）。3つ目の問いは「観察をもう一段深掘りする」ボタンに使える。

### 8-2. 親子で同じお題に答えて見せ合うアプリ

- **Talking Back - Parents & Kids**（https://apps.apple.com/us/app/id6761503007 ）
  - 流れ：毎日1問 → **全員が隠したまま答える → 全員そろったら一斉に見せる（Reveal）** → 「Talk About It」で会話を続ける。
  - 続けさせる工夫：連続記録、家族の統計、答えを残す日誌。Pro は月$9.99。
  - 評価は4.9だが、件数は15件と少ない。
  - 一斉に見せるので、相手の答えに引きずられない。
- **How Well Do U Know Your Child?**（https://apps.apple.com/app/id6746324782 ）
  - 流れ：**親が先に子どもの答えを予想する → 子どもが本当の答えを見せる。**
  - 「予想してから確かめる」を、そのまま家族の遊びにしている。
- **LOGIQ LABO の思考力チャレンジ**：親もきょうだいも同じ大会に出られる。結果を並べて比べられるかは未確認。
- 同じ「隠してから見せる」仕組み：Fatebook の「自分が予想するまで他人の予想を隠す」、Kialo の「他人の票を隠す」。

### 8-3. Philosophy for Children（P4C）と日本の「こども哲学」

- **流れ**：題材を見る → 考える時間 → 子どもが問いを出す → みんなで問いを選ぶ → 一緒に考える → 振り返る（https://durham-repository.worktribe.com/OutputFile/1378251 ）。
- **研究の結果**
  - Trickey & Topping（2004）*Research Papers in Education* 19(3)：対照群のある10本の系統的レビューで、平均効果量0.43（https://discovery.dundee.ac.uk/en/publications/philosophy-for-children-a-systematic-review/ ）。
  - EEF の無作為化試験（Gorard ほか 2015）：48校・2,821人で、読解0.12、算数0.10（https://dera.ioe.ac.uk/32011/ ）。
  - 2021年のさらに大きな試験では、学力の上乗せはなかった。EEF・NFER のページは開けず、検索抜粋で確認。
  - Wei & Chen（2025）*J Intell* 13(10)：メタ分析で g=0.59。ただし小さい研究ほど効果が大きく、無作為化試験は4本だけ（https://pmc.ncbi.nlm.nih.gov/articles/PMC12565533/ ）。
  - まとめ：**小規模な研究では中程度の効果があるが、大規模な試験では学力への効果は出ていない。**
- **日本の実践**：河野哲也ら（NPO法人こども哲学・おとな哲学アーダコーダ）。輪になって座り、問いを参加者で決め、話し合い、振り返る（https://souken.shingakunet.com/publication/.assets/2017_ccvol36_01.pdf ）。『こどものてつがく』は、こども哲学を「思考や議論の訓練ではなく、ケア的な哲学対話」と位置づけている。
- **借りられるもの**
  - 子どもが問いを出す役になる。
  - 親が正解を言わない。
  - 「訓練」より「一緒に考える時間」として出す。
  - 効果は学力ではなく、習慣と対話として語る。

### 8-4. 親子の機能で気をつけること

- 子ども向けの点数は、ベースラインスコア（+100〜0〜マイナス）が分かりやすい（2-4 を参照）。ただし点数で親子を比べると、競争になる。
- Habitica 型の「相手にダメージ」は使わない。2人の検証件数を足して、一緒に増やす形にする。
- 細谷功『具体と抽象』の練習（具体と抽象を行き来する）と組み合わせるなら、たとえば次のような欄が考えられる（案）。
  - 「この観察から言える一般的なきまりは？」（抽象へ）
  - 「そのきまりが正しければ、ほかにどんな場面で何が見える？」（具体へ戻る＝予想）

---

## 9. 全体の比較：このアプリに要る機能と、先にやっている例

| 要る機能 | 先にやっている例 | 足りない点 |
|---|---|---|
| 事実と推測を分けて書く | Golden Idol（文の型）、SOAP、CASUS（陰性の所見）、Project Zero（見えた物を言う） | 日常の題材に使える形のものはない |
| 仮説を2つ以上、順位を付ける | Human Dx、Kialo、Crimes & Punishments | 医療かゲームの中だけ |
| 証拠が仮説を支えるか崩すか | Kialo（0〜4）、Weed の構想、逆転裁判（つきつける） | ― |
| 予想＋確率＋期日 | Fatebook、PredictionBook、Metaculus | **カレンダーと連携しない** |
| 期日に知らせて答え合わせ | Fatebook（通知とメール）、Crimes & Punishments（後日の手紙） | ― |
| 予想を後から書き換えさせない | Obra Dinn（活字化）、意思決定日誌（後知恵対策） | ― |
| 確かめる日を Google カレンダーへ | Todoist（専用カレンダー）、Sunsama、TickTick | **予想の検証には使われていない** |
| 朝の計画・夜の振り返り | Sunsama、Akiflow、Stoic、Five Minute Journal | 推理の手順にはなっていない |
| 親子で隠して答え、一斉に見せる | Talking Back、Fatebook、Kialo | 推理の題材ではない |
| 手本と比べる | Consulting Detective（ホームズの解決）、Human Dx（他の人と比較） | ― |
| 罰のない続け方 | Brilliant、Duolingo、Streaks、Finch、Sunsama | ― |
| 施術記録との一体化 | らいらいリザーブ（数値の推移）、SOAP | **仮説と検証の形にしたものはない** |

→ **それぞれの部品には先にやっている例があるが、全部を1つにつなげた製品は、調べた範囲では見つからなかった。** 差をつける所は、つなぎ方（観察 → 仮説 → 予想 → カレンダー → 答え合わせ → 振り返り）と、ホームズに詳しい人が作る教材にある。

---

## 10. アプリへの示唆

### 10-1. まねる仕組み トップ10

1. **文の型で、事実と推測を分けさせる**（Golden Idol＋SOAP＋Project Zero）
   - 観察はまず短い「事実のカード」として書く。1枚ごとに「自分で見た／人から聞いた／なかった（陰性）」をタップで選ぶ（Daylio 並みの手軽さ）。
   - 推測は「[事実カード]から、[推測]と考える」という文の型でしか書けないようにする。推測の下には毎回「そう言える事実は？」を出す。
2. **予想は書いた時点で固定し、期日が来たら「活字化」する**（Obra Dinn＋意思決定日誌）
   - 予想を消したり書き換えたりはさせない。考えが変わったら「新しい予想」を履歴として足す（Fatebook の Forecast と同じ）。
   - 答え合わせの画面では、最初の予想と書いた日時を必ず見せる。これが後知恵の対策になる。
3. **Fatebook 型の「予想 → 期日 → 知らせ → 判定」**
   - 入力の問いかけは PredictionBook の3つ（何を予想する？／いつ分かる？／どのくらい確か？）。
   - 期日の欄は「いつ確かめる？」と聞き、そのまま Google カレンダーの予定にする。
   - 判定は4つ：当たり／外れ／どちらとも言えない／前提が崩れた。後の2つは採点しない（Metaculus）。
4. **Todoist 型のカレンダー連携**
   - アプリ専用のサブカレンダーを作り、そこにだけ書く。権限は `calendar.app.created` が第一候補。
   - 予定にはアプリのIDを `extendedProperties.private` で持たせ、`source.url` で答え合わせの画面に戻れるようにする。
   - 検証の予定は `transparency=transparent` にして、時間を塞がない。
   - Google 側で日時を動かされたら、期日を合わせる。予定が消されたら、カレンダーとのつながりを外すだけで、予想は消さない。
   - Google 側で新しく作られた予定は取り込まない。
5. **Sunsama 型の朝・夜の手順**（1画面ずつ進む）
   - 朝：昨日までの「期日の来た検証」を片づける → 今日の予定を見る（Things 型で予定は上）→ 観察したい場面を1つ決める。
   - 夜：今日の観察を事実と推測に分ける → 仮説を2つ → 予想と期日 → 「前はこう思った→今はこう思う」 → 1日を1タップで評価（Akiflow の Rate your Day）。
   - 夜を飛ばしたら、罰ではなく翌朝の最初の画面に回す。
6. **隠して答え、一斉に見せる**（Talking Back＋Fatebook＋Kialo）
   - 親子で同じ出来事にそれぞれ仮説と予想を出し、両方そろったら公開する。
   - 公開の画面では「どの事実を見たか」「どこから推測が分かれたか」を並べる。勝ち負けではなく、違いを見せる。
7. **仮説ごとに「合う事実／合わない事実」をつなぎ、強さを0〜4で付ける**（Kialo＋Weed＋CASUS）
   - 「まだ説明できないこと」の欄も作る（Kialo への批判を踏まえる）。
   - 観察を足すたびに仮説の順位を付け直し、その履歴を残す（Human Dx）。
8. **ホームズの「行き過ぎた推理」を直す練習問題**（大逆転裁判の共同推理）
   - ホームズに詳しい作り手が、手本と練習問題を作る。原作の推理を「事実」「推測」「飛躍」に分けた手本や、わざと飛躍させた推理の語を事実に差し替える問題。
   - 正解は人が作るので、AI の判定は要らない。答えた後は「ホームズならこう見た」と並べて読む（Consulting Detective）。
9. **振り返りの数字は「検証まで終えた件数」と「自信の付け方の正確さ」にする**
   - キャリブレーション図は、PredictionBook のように50%で折り返し、区間ごとの件数も出す。件数が少ないうちは「まだ判断できない」と表示する。
   - 子どもにはベースラインスコア（+100〜0〜マイナス）を使う。
   - 当たり外れは3件そろうごとにまとめて見せる（Obra Dinn）。
   - 「何件目の観察で当たりの仮説を挙げたか」も見せる（Human Dx）。
10. **罰のない続け方**
    - 休んでも途切れない仕組み（Brilliant の Streak Charge、Duolingo の Streak Freeze）。
    - 予定した休みの日は数えない（Streaks）。
    - 書き忘れを後から埋められる（Day One）。
    - 日付の境目は朝4時。連続記録は非表示にできる。
    - 知らせだけに頼らず、「夕食の後に1つ」のような出来事をきっかけにする（Stawarz）。
    - 1日の上限を設ける。たとえば未検証の仮説は5件まで（シンクシンク、Disco Elysium）。

### 10-2. まねない（失敗の原因）トップ5

1. **効果を大きく言う**（Lumosity と FTC の和解、遠い転移を否定する研究）
   - 「頭が良くなる」「診断力が上がる」「病気の予防」は書かない。
   - 言うのは「観察→仮説→検証の手順を、自分の生活で練習する記録帳」まで。
   - 施術者向けでは、Lumosity の例が健康の効果をうたう場合の直接の教訓になる。
2. **罰と圧力で続けさせる**（Habitica の体力とパーティーへのダメージ、Day One の消せない連続記録と0時の境目、Criminal Case のエネルギー制と広告待ち、Manifold の順位リーグ）
   - 連続記録が途切れると戻ってこない、という研究がある（Silverman & Barasch 2023）。
   - 罪悪感は、記録をやめる大きな理由の1つ（Epstein 2016）。
3. **AI に正しいかどうかを判定させる**（1人でウミガメのスープの判定ミス、Daily Holmes の「AI の講評」止まり）
   - 日常の推理の答え合わせは**現実（期日の検証）**に任せる。
   - AI を入れるとしても「なぜそう言える？」と問いかける役（逆転裁判の「ゆさぶる」）までにする。「AI の客観性は疑わしい」という見方とも合う。
   - また、偏りの一覧を見せるだけの機能は、研究上も効果が薄い（Norman 2017、Staal 2022）。
4. **勝手に予定を動かす、広すぎる権限を取る**（Motion の自動の組み直し、spatie パッケージの「カレンダー全体」の権限）
   - メインのカレンダーには書かない。自動の提案はしても、確定は人がする。
   - Google 側の変更を取り込む範囲は「アプリが作った予定の日時」だけに絞る。
5. **入力が重い、同じ問いのくり返し、仮説のない観察**（Stoic の機能の多さ、Reflectly の同じ質問の言い換え、イラスト探偵の物探し化）
   - いちばん軽い記録は「事実1枚と仮説1つ」で済むようにする。
   - 問いかけは前回の仮説を引いて変える（「水曜に立てた『鉢植えの水』の仮説は確かめた？」）。
   - 観察だけで終わらせず、必ず「なぜ？」（仮説）まで進ませる。

### 10-3. このアプリだけの差別化の案

1. **「予想が現実で答え合わせされる」ホームズの練習**
   - 題材は自分の生活。答え合わせは Google カレンダーの期日に来る。
   - Daily Holmes にも Fatebook にもない組み合わせ。
2. **ホームズに詳しい人が作る教材**
   - 原作の推理を「事実／推測／飛躍」に分けた手本。
   - 大逆転裁判の共同推理のように、ホームズの行き過ぎを直す練習。
   - 汎用の予想ツールや脳トレには出せない中身で、原作に詳しい人でなければ作れない。
   - 原作の訳文を載せる場合の著作権の扱いは、このR3では調べていない（未確認）。
3. **親子で「見方の違い」を見せる**
   - 隠して答え、一斉に見せる。
   - 違いは「どの事実を拾ったか」「どこで推測が分かれたか」の形で並べる。
   - 「相手の仮説を予想する」遊びも付けられる（How Well Do U Know Your Child? の型）。
4. **成長の物差しを「連続日数」でなく「推理の質」にする**
   - 検証まで終えた件数、自信の付け方の正確さ、何件目の観察で当たりを挙げたか、別の仮説を出した割合。
   - 「上達したら使う回数が減ってもよい」設計にする（Quirk の反省）。
5. **AI なしで成り立つ**
   - 答え合わせは現実、教材は人が作る、問いかけは決まった型。だから API の費用も、AI の判定ミスもない。
   - AI は後から「ゆさぶる」役として足せる。
6. **施術者版の土台**
   - SOAP の形のまま、前回の P（予想と確かめる日）と今回の S/O（痛みの数値など）を自動で並べる。
   - 調べた施術カルテアプリには、この機能がなかった。
   - 病名は出さず、「見立て」と呼ぶ。患者の情報は同意と保存方法を決めてから扱う。最初の版では扱わない。

### 10-4. 設計にそのまま使える具体案

**テーブルの分け方（Fatebook のデータ構造を手本に。名前は案）**

| テーブル | 中身 |
|---|---|
| `observations` | いつ・どこで・何の場面か |
| `observation_items` | 事実のカード：`kind`＝自分で見た／人から聞いた／なかった、`text` |
| `inferences` | 推測。どの事実カードから言ったか（中間テーブルでつなぐ） |
| `hypotheses` | `observation_id`、`text`、`rank`、`status`（検証中／支持／否定／どちらとも言えない／前提が崩れた） |
| `evidence_links` | `hypothesis_id`、`observation_item_id`、`direction`（合う／合わない）、`strength`（0〜4） |
| `predictions` | `hypothesis_id`、`text`、`probability`、`resolve_by`、`resolution`（YES／NO／AMBIGUOUS／ANNULLED）、`resolved_at`、`locked_at` |
| `prediction_revisions` | 予想を後から直したときの履歴（元の予想は消さない） |
| `calendar_links` | `prediction_id`、`google_calendar_id`、`google_event_id`、`etag` |
| `google_accounts` | `user_id`、暗号化したトークン、`sync_token`、通知チャンネルのIDと期限 |
| `reflections` | 日付、「前はこう思った」「今はこう思う」、1日の評価 |
| `family_groups`／`reveals` | 親子の組、隠して答えたものを公開した時刻 |
| `streak_days` | Monica の Streak のように「目標ID＋日付」だけを持つ1日1行 |

**Google カレンダー連携の手順（案）**
1. Google ログイン（Socialite）のときに、`calendar.app.created` を `access_type=offline` で求める。トークンは暗号化して保存する。
2. 最初の連携でアプリ専用のカレンダーを1つ作る（`calendars.insert`）。
3. 予想を登録したら、期日に予定を作る。中身は次のとおり。
   - タイトル例：「検証：鉢植えの水の仮説」
   - `extendedProperties.private.prediction_id`
   - `source.url`（答え合わせの画面）
   - `transparency=transparent`
4. 戻りの同期は、最初は15分ごとの差分同期（`syncToken`）だけにする。対象はアプリの予定の日時変更と削除だけ。`events.watch` は HTTPS と7日ごとの作り直しが要るので後回しにする。
5. 開発中の注意：OAuth の同意画面が「テスト中」のままだと、リフレッシュトークンは7日で切れる。審査を受けずに「本番」にして家族だけで使う方法がありそうだが、その場合は確認されていないアプリの警告が出て、利用者は100人まで（推論。実際の画面で要確認）。

**画面（案）**
- **今日**：上に今日の予定（読み取りの権限を足すかは要判断）。その下に「期日の来た検証」と「今日の観察」。
- **記録**：事実カード → 推測（文の型）→ 仮説を2つ → それぞれの予想・確率・「いつ確かめる？」。急いでいる日は「事実1枚と仮説1つ」で保存できる。
- **答え合わせ**：最初の予想と書いた日時を見せる → 判定を4つから選ぶ → 「前はこう思った→今はこう思う」。
- **振り返り（週）**：3件ずつまとめた当たり外れ、キャリブレーション図（件数つき）、検証まで終えた件数。
- **親子**：同じ出来事 → 隠して答える → 一斉に見せる → 違いを並べる。
- **練習問題**：ホームズの推理を直す、事実と推測を見分ける。正解と解説は人が作る。

**言葉の決まり（案）**
- 使う：観察、事実、推測、仮説、予想、検証、答え合わせ、見立て。
- 使わない：診断、治る、頭が良くなる。

### 10-5. このR3で確かめられなかったこと（次に確かめる候補）

- `calendar.app.created` がセンシティブ扱いか。Google Cloud Console の表示で確かめる。
- 「本番・未審査」で使うときの警告画面と、7日での失効が起きないこと。実際に試して確かめる。
- Sunsama で Google 側の予定を動かしたときの動き、Akiflow の書き込み先。
- Golden Idol で誤りが3つ以上のときの表示（転載記事の検索抜粋でしか見ていない）。続編の判定ルール。
- 大逆転裁判の共同推理のペナルティの量。
- 意思決定日誌のアプリ（Decidr など）。Annie Duke の「resulting」の一次資料。
- 日本の親子向けの思考アプリ（学研、Z会、算数オリンピック系）。親子の交換日記アプリ。
- 施術所が個人情報の医療向けガイダンスの対象に入るか。
- Human Dx の現在の状況。
- 原作ホームズの日本語訳を載せるときの著作権の扱い。
