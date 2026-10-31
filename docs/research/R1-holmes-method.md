# R1 ホームズの思考法モデル（原作に根ざした整理）

調査日：2026-10-31。英語の引用は、すべて Project Gutenberg の原文テキストをダウンロードし、文字列検索で照合したものです。章は Gutenberg 版の見出しに従います。日本語の要約は、既存の邦訳を見ずに筆者が原文から書いたものです。

## 0. この調査の要点

- ホームズの思考は「演繹」の一語では説明できない。原作から読み取れる技能は10個に整理できる（1章）。中心になるのは「見たこと（事実）と考えたこと（推理）を分ける」「説明を複数立てる」「『もしそうなら何が見えるはずか』を考えて確かめる」「間違えたら直す」の4つ。
- ホームズが deduction と呼ぶものの多くは、論理学でいうアブダクション（説明の仮説を思いつく推論）と帰納（確かめて確からしさを上げる推論）である。純粋な演繹は「仮説から予測を導く」段と「選択肢を消していく」段に出てくる（2章）。演繹を練習に組み込むなら、この**予測の段**として入れるのが正確である。
- ホームズのモデルは外科医ジョゼフ・ベル。ベル自身が「小さな違いを正確に見分けることが診断の本質」と書いている。診断と同じく、推理は確実ではなく、ベルも「時々は外した」とドイルは書いている（3章）。
- 教材にできる名場面を22件、原文で裏を取った（4章）。
- 日本語訳は、青空文庫の大久保ゆう訳が CC BY（表示）で使える。三上於菟吉訳は保護期間満了。ただしアプリの本文は「英語原文の短い引用＋自前の日本語要約」が一番安全（5章）。
- 作品と登場人物は日米英ともパブリックドメイン。ただしコナン・ドイル・エステートは米国などで「SHERLOCK HOLMES」の商標を登録している。日本の登録状況は今回確認できなかった。アプリ名の主役にホームズの名を置くのは避け、副題や説明で使うのが無難（6章）。

### 使った原典（Project Gutenberg）

| 作品 | Gutenberg 番号 | URL |
|---|---|---|
| A Study in Scarlet（緋色の研究） | 244 | https://www.gutenberg.org/ebooks/244 |
| The Sign of the Four（四つの署名） | 2097 | https://www.gutenberg.org/ebooks/2097 |
| The Adventures of Sherlock Holmes（冒険） | 1661 | https://www.gutenberg.org/ebooks/1661 |
| The Memoirs of Sherlock Holmes（回想。「ボール箱」を含む版） | 834 | https://www.gutenberg.org/ebooks/834 |
| The Hound of the Baskervilles（バスカヴィル家の犬） | 2852 | https://www.gutenberg.org/ebooks/2852 |
| The Return of Sherlock Holmes（生還） | 108 | https://www.gutenberg.org/ebooks/108 |
| The Valley of Fear（恐怖の谷） | 3289 | https://www.gutenberg.org/ebooks/3289 |
| His Last Bow（最後の挨拶） | 2350 | https://www.gutenberg.org/ebooks/2350 |
| The Case-Book of Sherlock Holmes（事件簿） | 69700 | https://www.gutenberg.org/ebooks/69700 |
| Memories and Adventures（ドイル自伝、1924） | 66991 | https://www.gutenberg.org/ebooks/66991 |

正典は長編4・短編56の計60編（英語版 Wikipedia「Klinger v. Conan Doyle Estate, Ltd.」の記述で確認）。

---

## 1. ホームズの思考の技能（10個）

### 1-0. 出発点：ホームズ自身が書いた「毎日の練習」

『緋色の研究』第1部2章「推理の科学（The Science of Deduction）」で、ワトソンは雑誌記事「人生の書（The Book of Life）」を読む。筆者はホームズ本人。記事は観察と分析で人のことがどこまで分かるかを論じ、最後に読者へ**毎日の練習**を勧めている。

> "Let him, on meeting a fellow-mortal, learn at a glance to distinguish the history of the man, and the trade or profession to which he belongs. Puerile as such an exercise may seem, it sharpens the faculties of observation, and teaches one where to look and what to look for."（A Study in Scarlet, Part I, Ch. II）

要約：人に会ったら、その人の来歴や職業をひと目で見分ける練習をせよ。子どもっぽく見えても、観察力が鋭くなり、どこを見て何を探せばよいかが身につく。

つまり「日常でホームズの視点を練習する」というこのアプリの目的は、原作の中にすでに書かれている。アプリの由来として紹介できる。ただし同じ章でワトソンは記事を "What ineffable twaddle!"（なんというたわごとだ）と笑い、記事が結論を "as infallible as so many propositions of Euclid"（ユークリッドの定理のように誤りがない）と言い張る点を「大げさ」と感じている。アプリは「誤りがない」とは約束しない。これも原作に沿った態度である。

ホームズ自身も、のちに自分の技を "my simple art, which is but systematized common sense"（体系立てた常識にすぎない。『白面の兵士』）と言っている。子ども向けの説明に使える。

### 1-1. 一覧

| # | 技能（画面での呼び名案） | ひとことで | 代表場面 |
|---|---|---|---|
| S1 | 見る→観察する | 目に入るものを、意識して拾う | ボヘミアの醜聞（階段17段） |
| S2 | ささいなことを拾う | 全体の印象より細部 | 花婿失踪事件（袖・鼻の跡） |
| S3 | 事実と推理を分ける | 「見た」と「考えた」を別の欄に | 四つの署名1章（郵便局） |
| S4 | 決めつけを遅らせる | データより先に結論を固めない | ボヘミアの醜聞、緋色の研究3章 |
| S5 | 別の説明を並べて消す | 候補を複数立て、合わないものを消す | ブラック・ピーター、白面の兵士 |
| S6 | 起きなかったことに気づく | ないもの・変わらなかったもの | 白銀号事件（犬） |
| S7 | 想像して予測し、確かめる | 「もしそうなら何が見えるはずか」 | 白銀号事件、赤毛組合、ソア橋 |
| S8 | 逆向きに推理する | 結果から、そこに至る道をたどる | 緋色の研究 第2部7章 |
| S9 | 知識の棚をつくる | 自分の仕事に効く知識を選んで深める | 緋色の研究2章（屋根裏部屋） |
| S10 | 間違いを認めて直す | 外れたら記録し、考え直す | 黄色い顔（ノーベリ） |

以下、各技能について「原文」「要約」「日常での意味」「毎日の練習（大人／子ども＝中高生／施術者＝整体・鍼灸など）」「よくある失敗」をまとめる。

### S1 見る→観察する

**原文**
> "You see, but you do not observe. The distinction is clear. For example, you have frequently seen the steps which lead up from the hall to this room." …… "Now, I know that there are seventeen steps, because I have both seen and observed."（A Scandal in Bohemia）

> "I see no more than you, but I have trained myself to notice what I see."（The Blanched Soldier）

> "The world is full of obvious things which nobody by any chance ever observes."（The Hound of the Baskervilles, Ch. 3）

**要約**：ワトソンは何百回も上った階段の段数を言えない。目には入っているが、意識して拾っていないからだ。ホームズは「君より多くを見ているわけではない。見たものに気づくよう自分を訓練しただけだ」と言う。観察は視力ではなく、注意の向け方の習慣である。

**日常での意味**：毎日見ているものほど見ていない。「数える」「比べる」「言葉にする」と、見たものが観察に変わる。

**毎日の練習**
- 大人：通勤路で「数えられるもの」を1つ決めて数える（駅の階段、信号の数）。翌日、記憶で書いてから確かめる。
- 子ども：家や学校の階段の段数を、まず予想で書き、次に数える。予想と実際の差を記録する。
- 施術者：来院した人が入口から椅子に座るまでを、施術を始める前に「事実だけ」で3行書く（例：左手で荷物を持っていた／靴のかかとの外側がすり減っている）。

**よくある失敗**：「ちゃんと見た」と思い込む。見たつもりの確認は、記憶で書いてから実物と照合すると崩れる。

### S2 ささいなことを拾う

**原文**
> "It has long been an axiom of mine that the little things are infinitely the most important."（A Case of Identity）

> "Never trust to general impressions, my boy, but concentrate yourself upon details. My first glance is always at a woman's sleeve. In a man it is perhaps better first to take the knee of the trouser."（A Case of Identity）

> "It is, of course, a trifle, but there is nothing so important as trifles."（The Man with the Twisted Lip）

> "It is of the highest importance in the art of detection to be able to recognise, out of a number of facts, which are incidental and which vital."（The Reigate Squires）

**要約**：全体の印象（「感じのいい人」「普通の部屋」）は細部を隠す。ホームズは袖やズボンの膝など、生活の跡が残る場所を最初に見る。ただし細部なら何でもよいわけではなく、たくさんの事実の中から「たまたまのもの」と「核心のもの」を選び分けることが大事だとも言う。

**日常での意味**：印象を言葉にする前に、細部を3つ挙げる。そのうえで「どれが一番、説明を必要としているか」を選ぶ。

**毎日の練習**
- 大人：会議や来客のあと、印象（「疲れていそう」）ではなく、それを感じさせた細部を3つ書く。
- 子ども：自分のくつ・かばん・筆箱の「すり減り」「汚れ」を1つ選び、どうしてそうなったか説明を書く。
- 施術者：「印象」欄と「細部」欄を分けて記録する。印象（「姿勢が悪い」）には必ず細部（「右肩が左より約2cm高い」など）を添える。

**よくある失敗**：細部を集めすぎて、どれが大事か選ばない。ホームズの言う「核心の事実」を1つ選ぶ欄を設けるとよい。

### S3 事実と推理を分ける（確かさの度合いも分ける）

**原文**
> "For example, observation shows me that you have been to the Wigmore Street Post-Office this morning, but deduction lets me know that when there you dispatched a telegram."（The Sign of the Four, Ch. I）

> "So much is observation. The rest is deduction."（同上）

> "The difficulty is to detach the framework of fact—of absolute undeniable fact—from the embellishments of theorists and reporters."（Silver Blaze）

> "…there are a few inferences which are very distinct, and a few others which represent at least a strong balance of probability."（The Adventure of the Blue Carbuncle）

**要約**：ホームズは自分の頭の中で、「観察で分かったこと」と「そこから推理したこと」をはっきり分けている。靴の甲に付いた赤い土（観察）と、それが郵便局の前の工事現場の土で、近所ではほかに見当たらない色だという知識（ホームズは「私の知る限り」と断っている）から「郵便局へ行った」。さらに、午前中ずっと向かいにいたので手紙は書いていない、机には切手もはがきもある、だから「電報を打った」（推理）。白銀号事件では、新聞や噂の飾りから「動かせない事実の骨組み」をはがすことが一番難しい、と言う。青いガーネットの帽子の推理では、結論を「はっきり言えるもの」と「かなり確からしいもの」に分けて述べている。

確かさの度合いを言葉にする例は他にもある。兄の懐中時計を読み解いたあとでは "I could only say what was the balance of probability."（確からしさを言っただけだ。『四つの署名』1章）。『緋色の研究』4章では、犯人が赤ら顔だという推理を "a more daring shot"（もっと大胆な当て推量）と自分で呼んでいる。

**日常での意味**：「見た」「聞いた」と「たぶん」「〜と思う」を混ぜない。推理には「確か／かなり確か／当て推量」の札を付ける。

**毎日の練習**
- 大人：日記や業務メモを書くとき、文末を「〜だった（見た）」と「〜だろう（考えた）」に分ける。
- 子ども：ニュースや友だちの話を1つ選び、「本当に見た・聞いたこと」と「その人の考え」に線を引き分ける。
- 施術者：施術記録を「所見（見た・触れた・測った）」「本人の言葉（主訴）」「自分の解釈」の3欄に分ける。解釈には確かさ（高・中・低）を付ける。

**よくある失敗**：本人の言葉や自分の解釈を、所見の欄に書いてしまう（「肩こりが改善した」は本人の報告で、測った事実ではない）。

### S4 決めつけを遅らせる（データの前に理論を固めない）

**原文**
> "It is a capital mistake to theorise before one has data. Insensibly one begins to twist facts to suit theories, instead of theories to suit facts."（A Scandal in Bohemia）

> "It is a capital mistake to theorize before you have all the evidence. It biases the judgment."（A Study in Scarlet, Part I, Ch. III）

> "Data! data! data!" he cried impatiently. "I can't make bricks without clay."（The Adventure of the Copper Beeches）

> "One forms provisional theories and waits for time or fuller knowledge to explode them."（The Adventure of the Sussex Vampire）

**要約**：データより先に理論を立てると、知らないうちに事実を理論に合わせてねじ曲げてしまう。判断がゆがむ。粘土なしにレンガは作れない。ただし、ホームズは考えるのをやめているわけではない。『サセックスの吸血鬼』では「仮の理論を立て、時間かより多くの知識がそれを壊すのを待つ」と言い、『黄色い顔』では自分の仮説を「推測にすぎないが、すべての事実を説明している。説明できない新事実が出たら考え直せばよい」と言う（"It is all surmise." "But at least it covers all the facts. When new facts come to our knowledge which cannot be covered by it, it will be time enough to reconsider it."）。

**日常での意味**：「考えるな」ではなく、「仮の考えは仮のまま持つ」。仮説に合わない事実を捨てない。

**毎日の練習**
- 大人：何か判断する前に「今の結論は仮」と書き、「これが出たら考えを変える」という条件を1つ書く。
- 子ども：テストの答え合わせ前に、自信のない答えに「仮」と印を付け、なぜ迷ったかを一言書く。
- 施術者：初回の見立ては「仮の見立て」と明記し、「次回これが変わっていなければ見立てを変える」という条件を書いておく。

**よくある失敗**：最初の見立てに合う事実だけを集める。合わない事実を書く欄を必ず置く。

### S5 別の説明を並べて、合わないものを消す

**原文**
> "One should always look for a possible alternative, and provide against it. It is the first rule of criminal investigation."（The Adventure of Black Peter）

> "How often have I said to you that when you have eliminated the impossible whatever remains, *however improbable*, must be the truth?"（The Sign of the Four, Ch. VI）

> "That process," said I, "starts upon the supposition that when you have eliminated all which is impossible, then whatever remains however improbable, must be the truth. It may well be that several explanations remain, in which case one tries test after test until one or other of them has a convincing amount of support."（The Blanched Soldier）

> "Circumstantial evidence is a very tricky thing," … "It may seem to point very straight to one thing, but if you shift your own point of view a little, you may find it pointing in an equally uncompromising manner to something entirely different."（The Boscombe Valley Mystery）

**要約**：ブラック・ピーターでは、警部が一人の青年を犯人と決めたとき、ホームズは「別の可能性を探し、それに備えるのが捜査の第一原則だ」と言って、自分の線を追い続ける。不可能を除けば、残ったものがどれほどありそうになくても真実だ、という有名な言葉は『四つの署名』6章と『緑柱石の宝冠』に出る。『白面の兵士』ではそれに大事な一文が加わる。「説明がいくつか残ることもある。そのときは、どれかが十分な裏付けを得るまで、検査を一つずつ重ねる」。同じ話でホームズは、若者が離れに隠されている理由として「犯罪で隠れている」「心の病」「病気による隔離」の3つを並べ、一つずつ検討して消している。

**日常での意味**：最初に思い付いた説明に飛び付かず、少なくとも3つ並べる。「不可能を除く」は、候補の一覧が漏れていないことが前提である（下の「失敗」参照）。

**毎日の練習**
- 大人：困ったこと（「家族の機嫌が悪い」「売上が落ちた」）に説明を3つ書き、それぞれ「これなら他に何が見えるはずか」を1行ずつ書く。
- 子ども：「朝、玄関が濡れていた」など身近な謎に説明を3つ書く。家族に答えを聞く前に、どれが一番ありそうか理由を書く。
- 施術者：変化（よくなった・悪くなった）に対し、施術以外の説明（自然な経過、生活の変化、他の治療や薬、測り方のばらつき、期待や気分）を必ず並べる。

**よくある失敗**：(1) 候補の一覧に漏れがあるまま「残ったものが真実」と言い切る。『白面の兵士』でも、ホームズが残した「ハンセン病」は皮膚科医の診察で「偽ハンセン病（魚鱗癬）で、うつらない」と訂正される（"It is not leprosy."）。消去法は、最後に専門家の検査で確かめるところまでがセット。(2) 一度消した説明を、新しい事実が出ても戻さない。

### S6 起きなかったこと・ないものに気づく

**原文**
> "Is there any point to which you would wish to draw my attention?"
> "To the curious incident of the dog in the night-time."
> "The dog did nothing in the night-time."
> "That was the curious incident," remarked Sherlock Holmes.（Silver Blaze）

> "Obviously the midnight visitor was some one whom the dog knew well."（Silver Blaze）

> "Dumb-bell—there's only one. Where's the other?"（The Valley of Fear, Part I, Ch. IV）

> "I *know* that that mark was not there when I examined the hall yesterday."（The Adventure of the Norwood Builder）

**要約**：厩舎には番犬がいたのに、夜中に誰かが馬を連れ出しても、馬屋の二階で寝ていた若者が起きるほどには吠えなかった。起きるはずのことが起きなかった。だから夜の訪問者は犬がよく知る人物だ。恐怖の谷では、一対のはずのダンベルが一つしかないことに気づく。ノーウッドの建築業者では、決定的とされた親指の跡が「昨日調べたときにはなかった」ことから、跡は夜のうちに付けられたと見抜く。

**日常での意味**：「何があるか」だけでなく「あるはずなのにないもの」「いつもと変わらなかったこと」を探す。そのためには「ふだん」を知っている必要がある。

**毎日の練習**
- 大人：一日の終わりに「今日なかったこと」を1つ書く（いつも来る連絡が来なかった、など）。理由の候補も書く。
- 子ども：部屋や教室で「昨日と比べてなくなったもの」を探すゲーム。家族が1つだけ物を隠す。
- 施術者：本人が「言わなかったこと」を記録する欄を置く（例：痛みの話はしたが睡眠の話は一度も出ない）。前回の記録と比べ「変わらなかったこと」も書く。

**よくある失敗**：ふだんの状態を記録していないと、「ないこと」に気づけない。基準になる記録を先に作る。

### S7 想像して予測し、確かめる

**原文**
> "See the value of imagination," said Holmes. "It is the one quality which Gregory lacks. We imagined what might have happened, acted upon the supposition, and find ourselves justified."（Silver Blaze）

> "I put myself in the man's place and, having first gauged his intelligence, I try to imagine how I should myself have proceeded under the same circumstances."（The Musgrave Ritual）

> "The knees of his trousers." "And what did you see?" "What I expected to see."（The Red-Headed League）

> "He will not even go out of his way to verify his own solutions, and would rather be considered wrong than take the trouble to prove himself right."（The Greek Interpreter。兄マイクロフトについて）

> "…when this original intellectual deduction is confirmed point by point by quite a number of independent incidents, then the subjective becomes objective…"（The Adventure of the Sussex Vampire）

**要約**：白銀号事件で、ホームズは馬の行き先を「メイプルトンにいる」と絞り、"Let us take that as a working hypothesis and see what it leads us to."（それを作業仮説として、どこへ導くか見よう）と言う。そして "If our supposition is correct, then the horse must have crossed that, and there is the point where we should look for his tracks."（想定が正しければ、馬は月曜の夜に濡れていたあの窪地を渡ったはずで、そこに足跡を探すべきだ）と予測し、実際に蹄鉄と合う足跡を見つける。想像して、その想定で動き、正しさが確かめられた。赤毛組合では「地下でトンネルを掘っているなら、助手のズボンの膝は擦り切れて汚れているはずだ」と予測し、道を尋ねるふりをして膝を見て「予想どおりのもの」を確かめた。ステッキで歩道を叩いたのは、地下室が店の前に伸びているかを調べるためだった。ソア橋では、自分の推理どおりなら同じ傷ができるはずだと、ワトソンの拳銃に紐と石を結んで実験し、欄干に二つ目の欠け跡を作って "Was there ever a more exact demonstration?" と言う。ブラック・ピーターでは、肉屋で吊るした豚に銛を突き立てる実験をして、一撃で貫くには強く慣れた腕が必要だと確かめている。一方、観察と推理では弟より上の兄マイクロフトは、自分の答えを確かめに行かないので探偵には向かない、とホームズは言う。

**日常での意味**：これが「演繹」が一番生きる場所。「もしこの説明が正しいなら、〇〇が見えるはず」と予測を立て、見に行く。複数の独立した事実で確かめられたとき、主観が客観になる（サセックスの吸血鬼）。

**毎日の練習**
- 大人：説明ごとに「正しければ見えるはずのこと」を書き、確かめに行く日をカレンダーに入れる。
- 子ども：「明日の朝、校庭は濡れているか」を天気・前日の様子から予想し、理由を書いて翌朝確かめる。
- 施術者：「この見立てが正しければ、次回までに〇〇が変わるはず／〇〇の動きで痛みが出るはず」と書き、次回の確認項目にする。外れたら見立てを変える。

**よくある失敗**：確かめる手段がない予測を立てる。予測は「見て分かる」「測って分かる」形で書く。また、人を相手に「試す」ことには限度がある。ホームズ自身、『悪魔の足』で危険な煙の実験に友を巻き込んだことを "It was an unjustifiable experiment even for one's self, and doubly so for a friend." と謝っている。

### S8 逆向きに推理する

**原文**
> "In solving a problem of this sort, the grand thing is to be able to reason backwards. That is a very useful accomplishment, and a very easy one, but people do not practise it much. In the every-day affairs of life it is more useful to reason forwards, and so the other comes to be neglected. There are fifty who can reason synthetically for one who can reason analytically."（A Study in Scarlet, Part II, Ch. VII）

> "As Cuvier could correctly describe a whole animal by the contemplation of a single bone, so the observer who has thoroughly understood one link in a series of incidents should be able to accurately state all the other ones, both before and after."（The Five Orange Pips）

> "…it is not really difficult to construct a series of inferences, each dependent upon its predecessor and each simple in itself. If, after doing so, one simply knocks out all the central inferences and presents one's audience with the starting-point and the conclusion, one may produce a startling, though possibly a meretricious, effect."（The Adventure of the Dancing Men）

**要約**：出来事の流れを聞いて「結果どうなるか」を言える人は多い。しかし結果だけを見て、そこに至った段階を自分で組み立てられる人は少ない。ホームズはこれを「逆向きに、あるいは分析的に推理する」と呼ぶ。踊る人形の冒頭では、ワトソンの指のチョーク跡から「南アフリカの株に投資しないことにした」と言い当て、途中の6つの鎖（チョーク→ビリヤード→相手はサーストン→彼の投資話→小切手帳は僕の引き出し→投資しない）を示す。途中を抜いて最初と最後だけ見せると驚かれるが、それは見かけ倒しの効果だとホームズは自分で言っている。

**日常での意味**：結果から「その一つ前に何があったはずか」を一段ずつさかのぼる。途中の段を全部書けば、誰でもたどれる。

**毎日の練習**
- 大人：今日起きた結果（「会議が長引いた」）から、さかのぼって原因の鎖を3段書く。
- 子ども：「なぜ体操服が泥だらけ？」を、結果から一段ずつ「その前は？」と書く。鎖の数を数える。
- 施術者：今の状態（「右膝の内側が痛む」）から、生活・動作・既往を一段ずつさかのぼる仮説の鎖を書く。どの段がまだ確かめられていないかに印を付ける。

**よくある失敗**：途中の段を飛ばして結論を言う（見かけ倒しの効果）。飛ばした段は、だいたい確かめていない段でもある。

### S9 知識の棚をつくる（頭の屋根裏部屋と専門知識）

**原文**
> "I consider that a man's brain originally is like a little empty attic, and you have to stock it with such furniture as you choose." …… "It is of the highest importance, therefore, not to have useless facts elbowing out the useful ones."（A Study in Scarlet, Part I, Ch. II）

> "He possesses two out of the three qualities necessary for the ideal detective. He has the power of observation and that of deduction. He is only wanting in knowledge…"（The Sign of the Four, Ch. I）

> "Here, for example, is one 'Upon the Distinction between the Ashes of the Various Tobaccoes.' In it I enumerate a hundred and forty forms of cigar-, cigarette-, and pipe-tobacco…"（The Sign of the Four, Ch. I）

> "Breadth of view, my dear Mr. Mac, is one of the essentials of our profession. The interplay of ideas and the oblique uses of knowledge are often of extraordinary interest."（The Valley of Fear, Part I, Ch. VII）

**要約**：人の頭はもともと小さな空の屋根裏部屋で、何を置くかは自分で選ぶ。何でも詰め込むと役立つ知識が埋もれる。ホームズは理想の探偵の三条件を「観察」「推理」「知識」とし、観察と推理だけでは足りないと言う。タバコの灰140種の研究、足跡の研究、職業による手の形の研究、刺青の研究（赤毛組合）、耳の形の研究（ボール箱）、新聞の活字の見分け（バスカヴィル家の犬4章）などを自分で積み上げている。一方で、恐怖の谷では「視野の広さ」と「知識の思わぬ使い道」を重んじ、ライオンのたてがみでは "I am an omnivorous reader with a strangely retentive memory for trifles." と言う。屋根裏の比喩（選べ）と、雑食の読書（広く）は、原作の中で緊張関係にある。

**日常での意味**：自分の仕事や目的に効く分野を一つ決めて、図鑑のように深める。そのうえで、ほかの分野にも「思わぬ使い道」がないか開いておく。

**毎日の練習**
- 大人：自分の「研究テーマ」を1つ決め（靴のすり減り方、手の荒れ方、植物など）、毎日1件ずつ実例を記録して自分だけの図鑑を作る。
- 子ども：好きな分野で「見分け図鑑」を作る（車の車種、鳥の種類、葉っぱの形）。1日1ページ。
- 施術者：姿勢・歩き方・靴底・手のたこなど、仕事や生活の跡を「職業と体の跡」図鑑にまとめる（ホームズの「職業による手の形」の研究と同じ発想）。出典（解剖学の教科書など）も添える。

**よくある失敗**：知識が古いまま固まる。青いガーネットでホームズは「頭が大きいから知的だ」（"a man with so large a brain must have something in it"）と推理するが、これは当時の考え方で、現代の科学では根拠が弱い。前提にしている知識そのものを、定期的に見直す必要がある。

### S10 間違いを認めて直す

**原文**
> "Watson," said he, "if it should ever strike you that I am getting a little over-confident in my powers, or giving less pains to a case than it deserves, kindly whisper 'Norbury' in my ear, and I shall be infinitely obliged to you."（The Yellow Face）

> "I had," said he, "come to an entirely erroneous conclusion which shows, my dear Watson, how dangerous it always is to reason from insufficient data." …… "I can only claim the merit that I instantly reconsidered my position…"（The Adventure of the Speckled Band）

> "Because I made a blunder, my dear Watson—which is, I am afraid, a more common occurrence than any one would think who only knew me through your memoirs."（Silver Blaze）

> "Such slips are common to all mortals, and the greatest is he who can recognise and repair them."（The Disappearance of Lady Frances Carfax）

**要約**：黄色い顔では、ホームズは依頼人の妻の秘密を脅迫がらみと推理したが、真相はまったく違う、温かい事情だった。夜、ホームズは「自分が自信過剰になっていたら『ノーベリ』とささやいてくれ」と頼む。まだらの紐では、ジプシーの存在と「バンド」という言葉に引きずられて間違った方向へ行ったが、すぐに考え直した。白銀号事件では出発が遅れた理由を「へまをした。君の記録だけ読む人が思うより、よくあることだ」と認める。レディ・フランシス・カーファクスの失踪では「誤りは誰にでもある。最も偉いのは、それに気づいて直せる人だ」と言う。恐怖の谷1章の暗号でも、最新の年鑑で解こうとして意味不明な言葉が出たとき、相手は古い年鑑を使ったのだと気づいてやり直している。

**日常での意味**：間違いは隠すものではなく、記録して次に生かすもの。予測が外れたときこそ、考え直す合図になる。

**毎日の練習**
- 大人：「ノーベリ帳」をつける。外れた推理・思い込みを1件ずつ書き、原因（データ不足／一つの説明に飛び付いた／前提の知識が古い）に分類する。
- 子ども：その日の「勘違い」を1つ書いて、家族と見せ合う。勘違いを書けた日をほめる。
- 施術者：見立てが外れた例を匿名化して記録し、月に一度読み返す。何の情報が足りなかったかを書く。

**よくある失敗**：当たった推理だけを覚えている。ワトソンも黄色い顔の冒頭で、成功談ばかり書くのは自然だが、と断ってホームズの失敗談を記録している。

### 1-2. 付記：ホームズの弱点から学ぶ（特に施術者と子ども向けに大事）

- **言い当てることと、相手への配慮は別。** ホームズはワトソンの兄の時計を読み解き、兄が酒で身を崩したことまで言い当ててワトソンを傷つける。そして "Viewing the matter as an abstract problem, I had forgotten how personal and painful a thing it might be to you." と謝る（四つの署名1章）。グロリア・スコット号では、老トレヴァーの過去を言い当てた結果、相手が気を失って倒れる。観察した内容を本人にそのまま言うかどうかは、別の判断である。施術の場でも、学校でも、「観察はノートに書く。口に出すのは相手のためになるときだけ」をルールにしたい。
- **説明すると「なんだ、簡単だ」と言われる。** 赤毛組合でホームズは "I begin to think, Watson," said Holmes, "that I make a mistake in explaining." と苦笑する。アプリの解説は「種明かしされると簡単に見える。だから練習で身につく」と伝える材料になる。
- **感情を判断から切り離す。** "The emotional qualities are antagonistic to clear reasoning."（四つの署名2章）。ただし、これは冷たさにもつながる（ワトソンは「計算機だ」と言う）。判断の場面と、人として接する場面を分ける、という読み方がよい。

---

## 2. 「演繹」は何をしているのか：演繹・帰納・アブダクション

### 2-1. ホームズの言う deduction

ホームズは自分の技を "The Science of Deduction"（『緋色の研究』第1部2章、『四つの署名』1章の章題）と呼び、「見て、演繹した（I see it, I deduce it.）」（ボヘミアの醜聞）と言う。しかしホームズの用法は、論理学の厳密な「演繹」（前提が正しければ結論も必ず正しい推論）とは一致しない。この点は、記号論の研究者が繰り返し指摘してきた。

- トマス・シービオク＆ジーン・ユミカー＝シービオク「『君は私のやり方を知っている』：パースとシャーロック・ホームズの並置」（Semiotica 26(3–4), 1979, pp. 203–250。改稿版は Sebeok, *The Play of Musement*, Indiana UP, 1981, pp. 17–52）。全文は http://www.visual-memory.co.uk/b_resources/abduction.html で確認。冒頭に、ホームズの "I never guess."（四つの署名）と、パースの "But we must conquer the truth by guessing, or not at all."（手稿692）を並べて掲げている。マルチェロ・トルッツィ（1973）が先に、ホームズの「演繹」とパースのアブダクションの類似を指摘していたことも紹介している。
- ウンベルト・エーコ＆トマス・シービオク編『The Sign of Three: Dupin, Holmes, Peirce』（Indiana University Press, 1983/1984, ISBN 978-0-253-35235-4）。デュパン、ホームズ、パースのアブダクションを論じた論文10本の論集（英語版 Wikipedia「Umberto Eco」「Thomas Sebeok」の書誌で確認）。邦訳『三人の記号』（東京図書）があるが、書誌の詳細は今回未確認。エーコの論文がアブダクションを数種類に分けている点も、今回は原文未確認。
- 哲学者ニーニルオト（Philosophy of Science 66, 1999）も「ホームズの『演繹』の論理は典型的にアブダクションだ」と書いている（英語版 Wikipedia「Abductive reasoning」の引用で確認）。

### 2-2. 三つの推論の違い（パースの豆の例）

パース「Deduction, Induction, and Hypothesis」（Popular Science Monthly 13, 1878）の例。英語版 Wikipedia「Abductive reasoning」に引かれた形で確認した。

| | 演繹（deduction） | 帰納（induction） | アブダクション（パースは当初 hypothesis と呼んだ） |
|---|---|---|---|
| 材料 | きまり＋この場合 | この場合＋結果 | きまり＋結果 |
| 豆の例 | この袋の豆はみな白い。これらはこの袋の豆だ。→ これらは白い | これらはこの袋から（無作為に）取った豆だ。これらは白い。→ この袋の豆はみな白い | この袋の豆はみな白い。これらは（妙に）白い。→ これらはこの袋の豆だろう |
| 確かさ | 前提が正しければ必ず正しい | 例が多いほど確からしい。例外の可能性は残る | 一番ありそうな説明。外れることがある |
| 新しい考え | 生まない（前提に含まれていたことを取り出す） | 生まない（確かめる） | 生む |

パースは1903年の講義で、アブダクションをこう定式化している（英語版 Wikipedia「Abductive reasoning」で確認。Collected Papers 5.188–189）。

> The surprising fact, C, is observed;
> But if A were true, C would be a matter of course,
> Hence, there is reason to suspect that A is true.

要約：驚くべき事実Cがある。Aが本当ならCは当たり前のことになる。だからAを疑ってみる理由がある。

シービオクらはパースに従い、三つの推論を**一つの流れ**として説明している。驚く事実を説明する仮説を採るのがアブダクション、その仮説から必然的・確率的に起きるはずのことを導くのが演繹、それを実際に確かめるのが帰納。パースはアブダクションを「新しい考えを始める唯一の議論」（CP 2.97）と呼び、"Abduction seeks a theory. Induction seeks for facts." とも書いている（いずれもシービオクらの論文中の引用で確認）。

ホームズ自身の言葉も、この流れに合う。

> "Say, rather, into the region where we balance probabilities and choose the most likely. It is the scientific use of the imagination, but we have always some material basis on which to start our speculation."（The Hound of the Baskervilles, Ch. 4）

要約：当て推量の領域ではない。確からしさを比べて一番ありそうなものを選ぶ領域だ。想像力の科学的な使い方だが、出発点には必ず具体的な材料がある。——これはアブダクション（最良の説明を選ぶ推論）の説明そのものである。

### 2-3. 名場面を三つの推論に当てはめる

| 場面 | アブダクション（説明を思いつく） | 演繹（予測を導く・選択肢を消す） | 帰納（確かめる・経験から一般化） |
|---|---|---|---|
| 四つの署名1章：郵便局 | 赤い土→郵便局へ行ったのだろう | 手紙は書いていない、切手もはがきもある→残るのは電報（"Eliminate all other factors…"） | （ワトソンが「どちらも正解」と認める） |
| 白銀号事件：カレー | アヘンの味を隠せる料理はカレーだ | 外部の男にはその夜カレーを出させることはできない→外部の男は除外 | 犬・カレー・足跡など独立の事実がそろう |
| 白銀号事件：犬・羊 | 吠えなかった→犬の知っている人物だ／腱を切る細工には練習が要るはず | 犬が知る人物なら厩舎の内部の者に絞られる／練習したなら近くの羊に跡が出るはず | 「羊が3頭足を引きずっている」と厩番の返事（ホームズ自身は「とても大胆な当て推量」と呼ぶ） |
| 赤毛組合 | 助手は地下でトンネルを掘っている | もしそうなら膝は擦り切れているはず／地下室は銀行の方へ伸びているはず | 膝を見る、歩道を叩く、銀行の位置を確かめる |
| 緋色の研究3〜4章：犯人の背丈 | 歩幅から背の高い男 | もし背が高いなら、壁の文字は目の高さ＝6フィート強にあるはず | 文字の高さで計算を検算 |
| 踊る人形：暗号 | 記号は文字を表している | 英語で一番多い文字はE→一番多い記号がE | 他の語を当てはめて意味が通るか確かめる |
| ソア橋 | 自殺を他殺に見せかけた | もしそうなら、拳銃が石に引かれて欄干に当たり、同じ欠けができるはず | 実験で二つ目の欠けを作る |
| 黄色い顔（失敗例） | 脅迫されている | もしそうなら〜（予測） | 実際は外れ→考え直す |

この表から分かるように、ホームズの場面には三つの推論がそろっている。**演繹は「予測を立てる」「候補を消す」段にあり、その前提（「犬は知らない人に吠える」「郵便局へ行く理由は手紙か電報」）は経験から来た帰納的な知識である。** だから結論は「必ず正しい」のではなく「一番ありそう」にとどまる。ホームズも懐中時計の場面で "the balance of probability" と言っている。

### 2-4. ほかの研究者の見方

- **ギンズブルグの「徴候（手がかり）」の考え方**：カルロ・ギンズブルグ「Morelli, Freud, and Sherlock Holmes: Clues and Scientific Method」（History Workshop 9, 1980, pp. 5–36。『The Sign of Three』pp. 81–118 に再録）。原題 "Spie"（1979）。美術史家モレッリが、絵の作者を耳や爪のような細部の描き癖で見分けたこと、フロイトが言い間違いのような細部から心を読んだこと、ホームズが細部から犯人を読むことを並べ、その根を医学の症候学や、狩人が足跡を読む古い知恵に求めた（英語版 Wikipedia「Giovanni Morelli」の記述で確認。原論文は今回未読）。ホームズが耳の形の研究を論文にしている点（ボール箱）は、モレッリの耳と重なる。日常や施術の場で小さな手がかりを読み合わせる練習には、この「一つひとつは小さな徴候を読み合わせる知」が一番近いかもしれない。
- **コニコヴァ『Mastermind: How to Think Like Sherlock Holmes』**（Viking, 2013）：マインドフルネス（注意の向け方）と論理的思考を、ホームズを題材に論じた一般書。速く直感的で信じやすい頭の使い方を「システム・ワトソン」、ゆっくり手順を踏み全体を見る使い方を「システム・ホームズ」と呼ぶ。頭の屋根裏部屋の比喩を記憶の整理に使い、「自分の信念を支える証拠と崩す証拠の両方を探す」ことを勧める（英語版 Wikipedia の書籍記事で確認）。邦訳『シャーロック・ホームズの思考術』（早川書房）の訳者・刊行年の詳細は今回未確認。心理学研究の紹介書であり、本書自体が効果を実験で示したものではない点に注意。

### 2-5. 大人と子どもへの説明（そのまま使える文案）

**大人向け**
「ホームズの『演繹』は、論理学の演繹（前提が正しければ必ず正しい推論）だけを指すのではありません。原作の推理を分けると、①気になる事実から『一番ありそうな説明』を思いつく（アブダクション）、②その説明が正しければ何が見えるはずかを導く（演繹）、③実際に見て確かめ、経験として積み上げる（帰納）、の三段が回っています。演繹は②の『予測を立てる』段と、『候補を消す』段で働きます。このアプリでは、演繹を独立した問題として解くより、毎日の出来事で『もし〜なら〜のはず』を書く練習として組み込みます。」

**子ども向け（中高生）**
「推理には3つの道具がある。
- **きまり→あてはめ（演繹）**：『雨の日は校庭が使えない』『今日は雨』→『今日は校庭が使えない』。きまりが正しければ必ず当たる。
- **集めて→きまり（帰納）**：『月曜は5回とも小テストがあった』→『月曜は小テストがあるらしい』。たくさん集めるほど当たりやすいけど、例外もある。
- **ふしぎ→説明（アブダクション）**：『玄関が濡れている』→『夜に雨が降ったのかな？』。一番ありそうな説明を思いつく道具。外れることもあるから、必ず確かめる。
ホームズは、ふしぎを見つけたら説明を思いつき（アブダクション）、『それなら〇〇も見えるはず』と考え（演繹）、見に行って確かめる（帰納）。これを一瞬でぐるぐる回しているんだ。」

（『具体と抽象』との関係を付け加えるなら、帰納は具体から抽象へ、演繹は抽象から具体へ、アブダクションは具体を説明する抽象を思いつく動き、と対応づけられる。これは筆者の整理で、細谷功氏の本の記述との照合はしていない。）

---

## 3. モデルになったジョゼフ・ベル博士と、医学の診断

### 3-1. 確認できた事実

- ジョゼフ・ベル（1837–1911）。エディンバラの外科医で、エディンバラ大学医学部で教えた（arthur-conan-doyle.com の「Joseph Bell」項 https://www.arthur-conan-doyle.com/wiki/Joseph_Bell で確認）。
- ドイルは学生時代にベルの外来係（out-patient clerk）を務めた。自伝『Memories and Adventures』（1924）第3章「Recollections of a Student」にこうある。ベルは手術の腕もよかったが「強みは診断で、それも病気だけでなく職業や性格まで診断した」。外来患者を順に通す役のドイルは、ベルが「自分が質問で得たより多くを、数回の素早い視線で知る」のを見た。そして "Occasionally the results were very dramatic, though there were times when he blundered."（劇的に当たることもあったが、外すこともあった）。
- 自伝の有名な場面：ベルは初対面の患者に「軍にいたね」「除隊して間もない」「ハイランド連隊」「下士官」「バルバドス駐留」と言い当て、学生にこう説明した。礼儀正しい男なのに帽子を取らなかった——軍隊では取らないが、除隊して長ければ民間の作法を覚えているはずだ。威厳があり、明らかにスコットランド人。病気は象皮病で、これは西インド諸島の病気で英国のものではない。ドイルは続けて、聞き手の「ワトソンたち」には奇跡に見えたが、説明されると簡単になった、と書く。
- 同書第8章「My First Literary Success」：ホームズを考えたとき "I thought of my old teacher Joe Bell, of his eagle face, of his curious ways, of his eerie trick of spotting details. If he were a detective he would surely reduce this fascinating but unorganized business to something nearer to an exact science." と書いている。
- 『シャーロック・ホームズの冒険』単行本（1892年10月）は「恩師ジョゼフ・ベルに」献呈された。1892年5月4日のドイルからベルへの手紙に "It is most certainly to you that I owe Sherlock Holmes." とある（いずれも arthur-conan-doyle.com の記述で確認。献辞・手紙の現物は未確認）。

### 3-2. ベル自身の文章（1892年）

ベルは『The Bookman』1892年12月号に、ドイルとホームズについての文章を寄せた（のち『緋色の研究』Ward, Lock & Bowden 版1893年の序文「Mr. Sherlock Holmes」として再録）。本文は https://www.arthur-conan-doyle.com/index.php/The_Adventures_of_Sherlock_Holmes_(article_1892) で確認した。要所は次のとおり。

> "The precise and intelligent recognition and appreciation of minor differences is the real essential factor in all successful medical diagnosis."

要約：小さな違いを正確に、意味を分かって見分けることこそが、成功する診断の本質である。

> "Try to learn the features of a disease or injury as precisely as you know the features, the gait, the tricks of manner of your most intimate friend."（ベルの師、外科医サイムの口癖として紹介）

要約：病気やけがの特徴を、親友の顔つき・歩き方・癖を知っているのと同じくらい正確に覚えよ。親友なら、同じ服の群衆の中でもすぐ分かる。違いはささいな点にしかないのに。

> "…senses to notice facts, and education and intelligence to apply them. Mere acuteness of the senses is not enough."

要約：必要なのは、事実に気づく感覚と、それを当てはめる教育と知性。感覚が鋭いだけでは足りない。（足跡が白人の靴だと分かるのは追跡者の目だが、どこで作られた靴かを言うには靴革の専門家が要る、という例を挙げる。）

> "The importance of the infinitely little is incalculable."

（細菌学の進歩を例に、限りなく小さいものの重要性ははかりしれない、と言う。花婿失踪事件の「小さなことこそ重要」と響き合う。）

ベルはさらに、診断に要る道具として「見える目と聞こえる耳、感覚の印象をすぐ記録しいつでも呼び出せる記憶、理論を編み、切れた鎖をつなぎ、もつれた手がかりをほどく想像力」を挙げる。また「この方法はヴォルテールの『ザディーグ』が教えたものであり、医学や外科のよい教師は毎日それを実践している」とも書く。

### 3-3. 診断は確実ではない、という前提

シービオクらの論文は、フランスの研究者メサック（1929）を引き、ドイルはベルにならって診断を患者の人柄や生活全体に広げたが、診断は「決して完全に厳密ではなく、迷いや誤りを含む」と述べる。

さらに同論文は、パースの手稿696から、医学での帰納（経験からの一般化）の難しさを論じた長い一節を引いている。要約すると：

- 医学では、体質の違い、治療の混在、偶然や未知の影響、気候や季節など、乱す要因が多い。だから事実は非常に多く、鋭い目で吟味しなければならない。
- それなのに人々は、限られた・不正確な・偏った経験から薬の良し悪しを言い切る。十数例で症状が軽くなったのを見ただけで「どんな場合にも効く」と言い、さらには一、二例の伝聞で治療を勧める。
- そこには誤りが重なっている。(1) 病気の定義があいまいで、何の標本か分からない。(2) 例の数が少なすぎる。(3) 伝聞は珍しい話ほど伝わるので、偏った標本になる。(4) 共通点として言うこと（「よくなった」）がぼんやりしている。(5) 目の前の例が、その標本と同じ種類か確かめずに当てはめる。(6) 過去の例で分かったこと以上を言ってしまう。

これは、施術者が「施術のあとよくなった」を「施術でよくなった」と読みたくなる場面（前後即因果の誤り）への、そのまま使える点検表になる。

### 3-4. 「観察は訓練できるか」についての研究（参考）

医学生に美術作品の観察を教えると視診の記述がよくなるか、を調べた研究がある。今回は Crossref で書誌だけを確認し、本文（対象数・効果の大きさ）は読めなかったため、効果の強さは**未確認**。いずれも医学部での教育研究で、規模は小さいと理解されている（筆者の既知情報。要確認）。施術者向けの練習の「考え方の裏付け」としては使えるが、「効果が実証された」とは書かない。

- Dolev JC, Friedlaender LK, Braverman IM. Use of Fine Art to Enhance Visual Diagnostic Skills. *JAMA*. 2001;286(9):1020. doi:10.1001/jama.286.9.1020
- Naghshineh S, Hafler JP, Miller AR, et al. Formal Art Observation Training Improves Medical Students' Visual Diagnostic Skills. *J Gen Intern Med*. 2008;23(7):991–997. doi:10.1007/s11606-008-0667-0
- Mukunda N, Moghbeli N, Rizzo A, et al. Visual art instruction in medical education: a narrative review. *Med Educ Online*. 2019;24(1):1558657. doi:10.1080/10872981.2018.1558657（叙述的レビュー。メタ分析ではない）

### 3-5. 施術者向けの練習への落とし込み

| ベル／ホームズの考え | 施術者の日々の練習 |
|---|---|
| 小さな違いを見分ける（ベル） | 来院時の観察を、施術前に事実だけで3行。毎回同じ項目（歩き方・立ち方・靴底・荷物の持ち手）で書き、前回と比べる |
| 親友の顔のように覚える（サイム） | 「よく見る状態」の自分用見本帳を作る（写真ではなく言葉で。個人情報は残さない） |
| 感覚＋知識（ベル） | 所見に、根拠にした知識（教科書・研修）を一行添える |
| 別の説明を並べる（ブラック・ピーター） | 変化があったら、施術以外の説明を最低2つ書く |
| もしそうなら〜のはず（白銀号事件） | 次回の確認項目を1つ書き、カレンダーの予約に紐づける |
| 外れたら直す（黄色い顔） | 外れた見立てを月1回ふり返る |
| 言い当てても口に出さない配慮（四つの署名1章、グロリア・スコット号） | 生活や私生活についての推測は記録にとどめ、本人に言うのは必要なときだけ |

線引き：施術者向けの機能は「考え方の練習と記録」に限り、病気の診断名をアプリが示すことはしない。医療機関の受診が必要な状態の判断については、別の調査担当の範囲とし、ここでは踏み込まない。

---

## 4. 教材にできる名推理の場面（22件）

すべて英語原文で場面と台詞を確認した。「技能」は1章の S1〜S10。

| # | 作品・章 | 場面 | ホームズが見た手がかり | 推理 | どう確かめたか | 技能 |
|---|---|---|---|---|---|---|
| 1 | 緋色の研究 第1部1・2章 | 初対面のワトソン | 医者風だが軍人の雰囲気。顔は日焼け、手首は白い。やつれた顔。左腕を不自然にかばう | 熱帯で負傷した軍医→アフガニスタン帰り | 本人が驚き認める。推論の鎖を2章で説明 | S1, S8 |
| 2 | 緋色の研究 第1部2・3章 | 通りの男 | 手の甲の青い錨の刺青、軍人らしい姿勢、規定どおりの頬ひげ、命令に慣れた態度 | 退役した海兵隊の軍曹 | 男が部屋に来て本人が答える | S1, S3 |
| 3 | 緋色の研究 第1部3・4章 | ローリストン・ガーデンズの現場 | 粘土と埃の上の歩幅、壁の血文字の高さ | 犯人は6フィート超の男 | 「人は壁に字を書くとき目の高さに書く」で歩幅の計算を検算 | S7, S3 |
| 4 | 緋色の研究 第1部3章 | 壁の文字 RACHE | 警部は「女性名レイチェルの書きかけ」と読む | ドイツ語で「復讐」の意味。レイチェル探しは無駄 | 言葉の知識。捜査の向きを変える | S9, S5 |
| 5 | 四つの署名 1章 | ウィグモア街の郵便局 | 靴の甲の赤い土。郵便局の向かいの工事現場の土の色（近所でほかに見当たらない）。朝ずっと手紙は書いていない。机に切手とはがき | 郵便局へ行った（観察）→電報を打った（推理） | ワトソンが認める | S3, S5 |
| 6 | 四つの署名 1章 | 兄の懐中時計 | 二か所のへこみと擦り傷、内側の質屋の番号4つ、鍵穴まわりの無数の引っかき傷 | 不注意な人、困窮と時々の好転、酒に溺れた | ワトソンが認める。ホームズは「確からしさを言っただけ」と言い、配慮を欠いたことを謝る | S3, S2 |
| 7 | ボヘミアの醜聞 | 階段と靴 | 階段の段数。ワトソンの左靴の内側の平行な6本の切り傷 | 雨の中を歩いた／不器用な女中がいる | ワトソンが認める | S1 |
| 8 | ボヘミアの醜聞 | 手紙の便箋 | 透かしの Eg・P・Gt | P=Papier、Gt=Gesellschaft、Eg=エグリア（ボヘミア）→ドイツ語圏の人が書いた | 地名事典（Continental Gazetteer）を引いて確かめる | S9, S7 |
| 9 | 赤毛組合 | 依頼人ジェイベズ・ウィルソン | 右手が左手より大きい、コンパスの襟章、右袖口のてかり、魚の刺青、中国の硬貨 | 肉体労働の経験、フリーメイソン、最近よく書き物をした、中国にいた | 本人が認める（「なんだ、簡単だ」と言われる） | S2 |
| 10 | 赤毛組合 | 助手と歩道 | 助手が地下室にこもる癖 | 銀行へトンネルを掘っている | 膝の擦り切れを「予想どおり」確認、歩道を叩いて地下の向きを確認、銀行の位置を確認 | S7 |
| 11 | 花婿失踪事件 | メアリー・サザーランド | 袖のビロードの二本線、鼻の両脇の鼻眼鏡の跡、左右ちぐはぐで半分だけ留めた靴、破れた手袋とインク | 近視のタイピスト、急いで家を出た、出る直前に手紙を書いた | 本人の驚き・返答 | S2 |
| 12 | 青いガーネット | 落とし物の帽子 | 帽子の大きさ・品質・型の古さ・手入れの状態など | 知的、以前は裕福で今は落ちぶれた…（確かさを段階分け） | 持ち主ヘンリー・ベイカーが現れる | S3, S9（前提の古さに注意） |
| 13 | まだらの紐 | 依頼人ヘレン・ストーナー／誤った推理 | 手袋の中の往復切符の半券、左袖の7か所の新しい泥 | 朝の列車で来た、その前に馬車で悪路を走った／当初はジプシー犯人説に傾く | 本人が認める／窓と扉から危険は来ないと分かり、すぐ考え直す | S2, S10 |
| 14 | 白銀号事件 | カレー・番犬・羊・足跡 | 厩番の夕食はカレー、アヘンの混入。犬は吠えなかった | 外部の男は除外。夜の訪問者は犬の知る人物。馬はメイプルトンにいる（作業仮説） | 窪地で蹄鉄と合う足跡を発見、羊が足を引きずっていると確認、など独立した事実がそろう | S6, S5, S7 |
| 15 | 黄色い顔 | 依頼人のパイプ／妻の秘密 | パイプの右側の焦げ、琥珀の吸い口の噛み跡 | 左利き、たくましい人／妻は脅迫されている | パイプは当たる。妻の件は外れ、「ノーベリ」と頼む | S2, S10 |
| 16 | ギリシャ語通訳 | 兄マイクロフトと窓の外の男 | 日焼け、権威ある態度、軍靴、帽子の跡、喪服、自分で買い物 | 最近除隊したインド帰りの砲兵の下士官、妻を亡くし子がいる | 兄弟で互いの推理を補い合う（ホームズは兄が「確かめに行かない」点を弱点とする） | S1, S7 |
| 17 | マスグレーヴ家の儀式 | 執事の行動を再現 | 儀式文の謎、楡の木の影、地下室の石 | 「相手の立場に身を置き、その知能を測って、自分ならどうしたか想像する」 | 実際に測って歩き、地下室を見つける | S7, S8 |
| 18 | ライゲートの大地主 | 破れた紙片 | 't' の筆跡の強弱 | 二人が一語ずつ交互に書いた手紙 | 筆跡の比較で強い字と弱い字を分ける | S2, S9 |
| 19 | 踊る人形 | ワトソンの投資／暗号 | 左手の指の間のチョーク／記号の出現回数 | 投資しないことにした（6つの鎖）／一番多い記号はE | ワトソンが認める／他の語を当てはめて解読し、偽の手紙で犯人を呼び出す | S8, S9 |
| 20 | ノーウッドの建築業者 | 親指の跡 | 前日に調べた壁にはなかった | 跡は夜のうちに付けられた＝偽の証拠 | 自分の前日の観察記録と照合 | S6 |
| 21 | ブラック・ピーター | 犯人の取り違え | 銛が壁に深く刺さるほどの一撃 | 弱々しい青年には無理。別の可能性を追う | 肉屋で豚に銛を突く実験 | S5, S7 |
| 22 | ソア橋（事件簿） | 欄干の欠け | 遺体の正面の欄干の下側に新しい欠け | 拳銃が紐と石に引かれて当たった＝自殺を他殺に見せかけた | ワトソンの拳銃で再現実験し、同じ欠けを作る | S7, S6 |

予備（表に入れなかったが使える場面）
- 白面の兵士：3つの説明を立てて一つずつ消す→最後は皮膚科医の診察で訂正（S5 の限界を教える教材）。
- アビ屋敷：3つのグラスのうち1つだけに澱（おり）→「説明は二つだけ」と並べ、二人分を三つに見せかけたと推理（S5）。
- バスカヴィル家の犬1章：ワトソンのステッキ推理を「誤りから真実へ導かれた」と評し、医師名簿で確かめる。ホームズ自身も「結婚記念の贈り物」という点で推理を外す（S3, S10）。
- プライオリ・スクール：自転車の轍で進行方向を見分ける推理。ドイルは自伝で、読者の指摘を受けて自分で自転車を走らせて試し、後輪と前輪の重なりでは方向は分からず「読者が正しく私が間違っていた」と認めた（ただし起伏のある荒れ地では上りで轍が深くなるので結論は救われる、とも書く）。作者自身の検証と訂正の例として使える（S7, S10）。

---

## 5. 日本語訳の権利（青空文庫）

青空文庫のドイル作品一覧（作家番号9：https://www.aozora.gr.jp/index_pages/person9.html）と各図書カード、作品ファイルの奥付で確認した。

| 訳者 | 主な作品（青空文庫の題） | 権利の状態 | 根拠 |
|---|---|---|---|
| 大久保ゆう（1982– ） | 赤毛連盟、ボヘミアの醜聞、緋のエチュード、唇のねじれた男、蒼炎石、まだらのひも、最後の事件、ライギット・パズル など多数 | **CC BY 2.1 JP（表示）**。訳者に断りなく利用・複製・再配布できる。図書カードには「＊著作権存続＊」と表示（翻訳の権利が生きているという意味） | 各ファイル奥付「この翻訳は『クリエイティブ・コモンズ 表示 2.1 日本 ライセンス』によって公開されています」（例：赤毛連盟 https://www.aozora.gr.jp/cards/000009/card8.html ） |
| 大久保ゆう | シルヴァブレイズ（2022年公開） | **CC BY 4.0 国際** | 奥付（https://www.aozora.gr.jp/cards/000009/card61393.html ） |
| 大久保ゆう（改訳者） | 土色の顔（黄色い顔）、同一事件（花婿失踪事件） | CC BY 2.1 JP。図書カードに三上於菟吉・加藤朝鳥の名も並び、奥付は「改訳者：大久保ゆう」 | card50714, card54910 の奥付 |
| 三上於菟吉（1891–1944） | 黄色な顔、白銀の失踪、空家の冒険、暗号舞踏人の謎 など | 保護期間満了（図書カードに著作権存続の表示なし）。自由に使える。ただし底本は1930年刊で、青空文庫は「今日からみれば不適切と受け取られる可能性のある表現がみられる」と注記 | card43028 |
| 枯葉、山本ゆうじ | 悪魔の足、まだらの紐をめぐる冒険 など | 著作権存続。ファイルは外部サイトにあり、利用条件は今回未確認（枯葉訳はリンク先にファイルがないと注記） | card3392, card535 |

確認できた補足
- 大久保訳の奥付には「翻訳の底本：Arthur Conan Doyle (1891) "The Red-Headed League" 上記の翻訳底本は、著作権が失効しています」とあり、原作が日本で保護期間満了であることも分かる。
- 大久保ゆう訳の『土色の顔』では、ホームズが頼む言葉は「ノーベリ」と訳されている（三上訳は「ノーブリー」）。

アプリでの使い方の判断材料
1. **一番安全なのは、英語原文（パブリックドメイン）の短い引用＋自前の日本語要約**。この文書もその形で書いている。
2. 日本語の名台詞を画面に出したい場合は、**大久保ゆう訳を CC BY の条件で使う**。表示に必要なのは、訳者名（大久保ゆう）、作品名、ライセンス名とリンク（CC BY 2.1 JP または 4.0）、改変した場合はその旨。商用利用も可。訳者の推薦を受けているように見せる書き方はしない。
3. 三上於菟吉訳は自由に使えるが、言葉づかいが古く、注記のとおり不適切な表現を含む場合がある。子ども向けには不向き。
4. 新潮文庫・創元推理文庫・光文社文庫などの市販の訳（訳者が存命、または没後70年未満）は使わない。著作権法32条の「引用」の範囲で数行を示すことは理屈の上では可能だが、教材として繰り返し表示するアプリでは、主従関係などの条件を満たし続けるのが難しい。

---

## 6. 名前の注意：著作権と商標

### 6-1. 著作権（確認できたこと）

- **日本**：ドイル（1930年没）の原作の保護期間は満了している（青空文庫の大久保訳奥付に「翻訳底本は、著作権が失効」と明記）。英国の作品には戦時加算（英国は3794日。日本語版 Wikipedia「戦時加算 (著作権法)」で確認）があるが、それを足しても満了済み。正確な満了日は今回計算していない（未確認）。
- **英国**：没後50年の1980年末に一度満了し、EU の制度に合わせた改正で1996年に復活、没後70年の2000年末に再び満了（英語版 Wikipedia「Sherlock Holmes」）。
- **米国**：1923年以降に出た『事件簿』の10編が最後まで保護されていたが、2019〜2023年の各1月1日に順に満了し、**2023年1月1日で全作品と登場人物がパブリックドメイン**（同上。AP通信の記事を出典に挙げている）。
- **裁判**：Klinger v. Conan Doyle Estate, Ltd.（第7巡回区控訴裁判所、2014年、755 F.3d 496）は、保護が切れた作品に描かれたホームズとワトソンの要素は自由に使えると判断した。2020年にはエステートが映画『エノーラ・ホームズの事件簿』をめぐり、まだ保護中の作品にしか出ない性格（温かさなど）を使ったとして提訴したが、2020年12月18日に双方の合意で取り下げ（再提訴不可）となった（英語版 Wikipedia「Klinger v. Conan Doyle Estate, Ltd.」「Sherlock Holmes」）。現在は米国でもこの種の主張の根拠となる作品は残っていない。
- **注意**：パブリックドメインなのはドイルの原作とシドニー・パジェット（1908年没）の挿絵まで。BBC『SHERLOCK』、グラナダ版ドラマ、映画の画像・ロゴ・俳優の姿、近年の翻訳やパスティーシュは別に権利がある。

### 6-2. 商標（分かる範囲）

コナン・ドイル・エステートの公式サイト「Trademarks & Copyrights」（https://www.conandoyleestate.com/licensing/trademarks-and-copyrights 、「2019年6月時点の改訂版」と明記）によると：

- 米国、EU、英国などで、ドイル、シャーロック・ホームズ、チャレンジャー教授などの名前と姿について**商標とコモンロー上の権利を持つ**と主張している。
- 登録として挙げているもの（抜粋）：SHERLOCK HOLMES（文字）米国登録 4313984・4690745、オーストラリア登録 1471464、SHERLOCK HOLMES CONAN DOYLE ESTATE LTD（ロゴ）米国登録 5396601 など。SHERLOCK HOLMES の米国出願（審査中）が複数、WIPO／中国の出願もある。
- 日本に関する記載は、PROFESSOR CHALLENGER（国際登録 1244163 で日本を指定）と、ARTHUR CONAN DOYLE の出願（日本などを指定）のみ。**SHERLOCK HOLMES の日本での登録は、このリストには載っていない。**
- ただし、このリストは2019年時点のもので、著作権の記載（「米国で最後の保護作品を持つ」）は2023年以降は当てはまらない。各登録がどの商品・サービスの区分か、現在も有効かは今回確認していない（未確認）。
- **日本の特許庁データベース（J-PlatPat）で、第三者による「シャーロック」「ホームズ」「SHERLOCK HOLMES」「221B」「ベーカー街」などを含む登録の有無は、今回は確認できなかった（未確認）。** アプリに関係する区分は、第9類（ダウンロードできるソフト）、第41類（教育・知識の教授）、第42類（オンラインで提供するソフト＝SaaS）が中心。施術者向けに広げるなら第44類（医療・あん摩など）も見ておく。

### 6-3. アプリ名の判断材料

- 原作がパブリックドメインなので、**アプリの説明や教材の中で「シャーロック・ホームズ」の名前と原作の場面を使うことは問題が小さい**。
- 一方で、**アプリ名（商品名）の主役に「シャーロック・ホームズ」を置くこと**は、日本の第三者の商標や、エステートが日本で今後取る可能性のある商標とぶつかるおそれがある。エステートは過去に、権利が切れた後も出版社や配信会社へ使用料を求めてきた実績がある（Klinger 事件、エノーラ事件）。小さな個人開発では、法的に勝てても対応の手間が重い。
- おすすめ：**アプリ名は独自の言葉にし、副題や紹介文で「シャーロック・ホームズの観察法に学ぶ」と説明する。** 公開前に J-PlatPat で上の区分を検索する。エステートのロゴ、BBC や映画の画像は使わない。挿絵が必要ならパジェットの原画（パブリックドメイン）か、自作のイラストを使う。
- 「ベーカー街」「221B」を名前に入れる案は、同じ作り手の既存アプリ（Baker Street English）と紛らわしくなる点にも注意。

---

## 7. アプリへの示唆

### 7-1. 練習の基本の型（1回3〜5分。毎日のタスクにする）

原作の流れをそのまま画面の順番にする。

1. **見たこと**（S1, S2）：事実だけ。3つまで。「数えたもの」があれば数字で。
2. **なかったこと**（S6）：あるはずなのになかったもの、いつもと変わらなかったこと。空欄でもよい。
3. **考えたこと**（S3）：説明を書き、確かさを3段階で選ぶ。
4. **別の説明**（S5）：最低1つは必須。2つ目以降は任意。
5. **もしそうなら**（S7＝演繹の段）：「この説明が正しければ〇〇が見えるはず」。確かめる日をカレンダーに入れる（Google カレンダー連携の使いどころ）。
6. **確かめた結果**（S7）：当たり／外れ／分からない。
7. **直したこと**（S10）：外れたら「ノーベリ帳」へ自動で送る。

### 7-2. 練習メニュー案（技能ごと。毎日1つを出題）

| メニュー名（案） | 技能 | 内容 | 由来 |
|---|---|---|---|
| 17段チャレンジ | S1 | 毎日見ているものを、記憶で数えてから実物で確かめる | ボヘミアの醜聞 |
| 袖と膝 | S2 | 人や物の「生活の跡」が残る場所を1つ選び、細部を3つ書く | 花婿失踪事件 |
| 郵便局ノート | S3 | 一つの出来事を「観察」と「推理」に分けて書く | 四つの署名1章 |
| 仮の札 | S4 | 結論に「仮」を付け、「これが出たら考えを変える」条件を書く | サセックスの吸血鬼、黄色い顔 |
| 第一原則 | S5 | 説明を3つ並べ、合わないものを理由つきで消す | ブラック・ピーター |
| 吠えなかった犬 | S6 | 今日「なかったこと」を1つ探す | 白銀号事件 |
| 膝を見に行く | S7 | 予測を書き、確かめる日を決める | 赤毛組合 |
| 鎖をつなぐ | S8 | 結果から原因の鎖を3段さかのぼる。飛ばした段に印 | 踊る人形、緋色の研究 第2部7章 |
| 屋根裏の棚 | S9 | 自分の見分け図鑑に1件足す | タバコの灰の研究 |
| ノーベリ帳 | S10 | 外れた推理を1件記録し、原因を選ぶ | 黄色い顔 |
| 人生の書 | 総合 | 週1回、人や物から来歴を推理してノートに書く（本人には言わない） | 緋色の研究 第1部2章 |

### 7-3. 画面の言葉（用語集の候補）

| 画面の言葉 | 意味 | 子ども向けの言い換え |
|---|---|---|
| 見たこと | 観察した事実 | 目でたしかめたこと |
| なかったこと | あるはずなのに、ないもの | 「ない」を見つける |
| 考えたこと | 推理（説明の仮説） | 「たぶん」のこと |
| 確かさ：確か／かなり確か／当て推量 | ホームズの「はっきり言える」「確からしい」「大胆な当て推量」に対応 | ぜったい／たぶん／あてずっぽう |
| 別の説明 | ほかの仮説 | ほかの「かも」 |
| もしそうなら | 仮説から導く予測（演繹） | それなら、〇〇のはず |
| 確かめた | 予測を見に行った結果 | 見に行った |
| ノーベリ帳 | 外れた推理の記録 | まちがいノート（まちがいは宝もの） |
| 屋根裏の棚 | 自分で選んで深める知識 | ぼくの図鑑・わたしの図鑑 |

「演繹」「帰納」「アブダクション」の語は解説画面に置き、毎日の入力画面には出さない（演繹の考え方を伝えつつ、入力の負担を軽くする）。解説では2-5の文案を使う。

### 7-4. 施術者モード（将来の応用先）

- 記録画面を「所見（見た・触れた・測った）」「本人の言葉」「自分の解釈（確かさ付き）」「別の説明」「次回確かめること」に分ける。
- 「よくなった」と書かれたら、施術以外の説明（自然な経過・生活の変化・他の治療・測り方のばらつき・期待）を選ぶチェック欄を出す。根拠はパースの医学的帰納の6つの誤り（3-3）。
- 「次回確かめること」を予約日のタスクとしてカレンダーに入れる。
- 生活や私生活に関する推測は「本人には伝えない」既定にする（四つの署名1章の時計、グロリア・スコット号）。
- 個人情報は残さない設計を既定にし、ふり返りは匿名化した例で行う。

### 7-5. AI を使う場合の役割

「AI は与えられた記録から離れて客観的に確かめられない」という懐疑は、原作の立場とも合う。ホームズが重んじるのは「現実で確かめること」（マイクロフトの弱点、ソア橋の実験）であり、頭の中のもっともらしさではない。AI を入れるなら**判定役ではなく、問いかけ役**に限るのがよい。

- 固定の問い：「それは見たこと？考えたこと？」「別の説明は？」「それが正しければ何が見えるはず？」「確かめる日は？」。これはルールで出せるので、まずは AI なしで作れる。
- AI に任せるとしても、利用者の結論を見せずに「別の説明」の候補を出させる程度にとどめ、正解を言わせない。

### 7-6. 教材コンテンツ

- 4章の22場面を「事件カード」にする。表面：手がかりだけ（英語原文の短い引用＋自前の日本語要約）。裏面：ホームズの推理、どう確かめたか、練習できる技能。利用者が自分で推理してから裏返す。
- 「ホームズの失敗」カード（黄色い顔、まだらの紐、白面の兵士の訂正、プライオリ・スクールの自転車）を必ず混ぜ、「名探偵も外す。直せるのが強さ」と伝える。
- 子ども向けの最初の一枚は「17段」（ボヘミアの醜聞）と「吠えなかった犬」（白銀号事件）が分かりやすい。

### 7-7. 書き方の約束（コンテンツ制作用）

- 英語の引用は Gutenberg の本文から短く。作品名と章を必ず添える。
- 日本語は自前の要約。日本語の台詞を引くなら大久保ゆう訳を CC BY の表示つきで。
- 「ホームズの方法で必ず分かる」とは書かない（原作でもワトソンが「誤りがない」という主張を笑い、ベルも外している）。
- アプリ名にシャーロック・ホームズの名を主役として入れない。入れる場合は公開前に J-PlatPat で確認する。
