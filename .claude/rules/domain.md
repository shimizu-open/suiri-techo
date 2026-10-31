---
paths:
  - "src/app/**"
  - "src/routes/**"
  - "src/database/**"
  - "src/config/suiri.php"
  - "src/config/watson.php"
  - "src/resources/views/**"
---

# 中心の流れ・言葉・壊さない決まり

人間向けの元は `docs/design/words.md`（言葉）・`requirements.md`（要件）・`data-model.md`（表）。**この表と words.md は同じ中身にそろえる**（片方を直したら、もう片方も直す）。表にない言葉が要るときは、先に words.md に足してから使う。

## 中心の流れ（コアループ）

予定・タスク・ふと気づいたこと →「気になる」で**事件**（なぞ）を作る → **手がかり**（見た／聞いた／なかった）を足す → **見立て**（説明の案。2つ以上を勧める）を立て、手がかりに ○× を付けて消していく → **予想**（もしそうなら〜が見えるはず・確かさ 50〜95％・確かめる日）を立てる → **確かめるタスク**ができ、カレンダーの専用カレンダーに入る → その日に**答え合わせ**（当たり／外れ／どちらとも言えない／前提が崩れた）→ 外れは**ノーベリ帳**（何を見落とした？）、わかったことは**屋根裏の棚**（つまり？／たとえば？）。

タスク管理は「確かめることを忘れない」ための道具。思考の練習を毎日の宿題にしない。

## 言葉＝データ＝コードの名前（短い版）

| 画面の言葉 | コードの名前（モデル／表／URL・列挙） |
|---|---|
| 事件・なぞ | `CaseFile`／`case_files`／`/cases`（`{case}` は `CaseFile` に結ぶ。`case` は PHP の予約語）・`title` |
| 場面（日常／親子／仕事） | `Scene`（`daily`／`family`／`work`） |
| 捜査中・解決・迷宮入り（事件の一覧のつまみでは大人にも「おあずけ」＋「おあずけ＝迷宮入り」の一言。`case_list.tabs`） | `CaseStatus`（`open`／`solved`／`cold`） |
| 手がかり（見た／聞いた／なかった） | `Clue`／`clues`・`ClueKind`（`seen`／`heard`／`absent`） |
| 見立て（検討中・有力・消えた・当たり） | `Hypothesis`／`hypotheses`・`HypothesisStatus`（`considering`／`likely`／`eliminated`／`confirmed`） |
| 合う・合わない（○×） | `Fit`（`fits`／`contradicts`）／`clue_hypothesis` |
| もしそうなら（予想）・確かさ・確かめる日 | `Prediction`／`predictions`・`confidence`・`check_on` |
| 答え合わせ（当たり・外れ・どちらとも言えない・前提が崩れた） | `predictions.result`・`PredictionResult`（`hit`／`miss`／`unclear`／`void`） |
| 何を見落とした？ | `MissReason`（`few_clues`／`single_hypothesis`／`assumption`／`wrong_check`／`chance`） |
| ノーベリ帳 | `/norbury` |
| 屋根裏の棚（つまり？／たとえば？） | `AtticNote`／`attic_notes`／`/attic`・`rule`／`examples` |
| 相棒・見せ合う | `users.partner_name`・`HypothesisAuthor::Partner`・`case_files.revealed_at` |
| ワトソンのひとこと | `App\Services\Watson`・`config/watson.php`（AI ではない。決まった規則で一度に1つ） |
| 提案・書き出し（AI なし） | まとめ役 `App\Queries\Cases\SuggestionsQuery`（決まった型 `StarterSuggestions`・屋根裏の棚 `AtticSuggestionsQuery`・確かめる日 `CheckDateSuggestionsQuery`）・`config/suiri.php` の `suggest`・言葉の重なり `App\Support\WordOverlap`（押すと入力欄に入るだけ。保存は本人） |
| タスク（ふつう・確かめる。画面の言葉は「やること」） | `Task`／`tasks`／`/tasks/create`・`/tasks/{task}/edit`（一覧はきょうの画面 `/today` にまとめ、`/tasks` は `/today` へ移すだけ）・`TaskKind`（`normal`／`verify`） |
| 予定（読むだけ） | `App\Services\Google\CalendarAgenda` |
| きょう（今日の予定とタスクを1つにまとめた画面）・手引き・ふりかえり | `/today`（`?open=upcoming`・`someday`・`done` でその枠を開く）・`/guide`・`/review`／`weekly_reflections` |
| 技能・名推理カード・練習メニュー | `database/data/guide/skills.json`・`scenes.json`（`scene_attempts`）・`practices.json` |
| 確かさの答え合わせ | `App\Services\Review\Calibration` |
| カレンダーとつなぐ | `CalendarConnection`／`calendar_connections`／`/calendar` |
| 招待制（一般公開のときは出さない。新しく登録できる人の決め方） | `config('suiri.invite_only')`（`.env` の `SUIRI_INVITE_ONLY`。既定 false＝一般公開で、Google アカウントがあればだれでも登録できる。true で名簿の人だけ）・名簿 `Invitation`／`invitations`・`invite:add`／`invite:list`／`invite:remove` |

- 表示の言葉は列挙の `label(?Scene $scene)` が `config/suiri.php` の `labels` から引く。**仕事の場面の出し分け**（見た→見た・触れた・測った／聞いた→ご本人の言葉／見立て→見立て（診断ではありません）／もしそうなら→次回、確かめること）もそこ。コードの名前は変えない。
- 子ども向けの言い換え（調べ中・わかった・おあずけ、「かも」など）は words.md の表の「子ども向け」の列。**出す条件は1つ：ふだんの場面（設定）が「親子」の人**（`User::prefersKidWords()`。事件ごとの場面では切り替えない。手引きだけは `?kid=0`・`?kid=1` で選んだ向きが先）。画面ごとの対応は words.md の 2-2（短い版）：
  - 事件の一覧：つまみ（調べ中・わかった・おあずけ）と場面の札（ふだん・おやこ・しごと）。迷宮入りのつまみは大人にも「おあずけ」と出し、「おあずけ＝迷宮入り」と一言説明する（`case_list.tabs`・`tabs_kid`・`leads.cold`）
  - 事件を作る：場面の呼び名、手がかりの種類の説明（目でたしかめた など）、見立ての呼び名（「かも」）
  - 事件の画面：「かも」と全部ちがったときの問い、別の説明の型の言い方と予想の穴埋めの文、確かさの目安、「いつ見に行く？」「この日に見に行く」、閉じた事件の「わかった！」「図鑑にしまう」（`case_board.kid`）
  - 答え合わせ：4つの結果（あたり・はずれ…）と「どこでまちがえた？」、はんこの言葉、「かも」を「あたり」にする？
  - 今日：今日の練習の短い説明（`kid_description`）
  - ふりかえり・ノーベリ帳・屋根裏の棚：呼び名（まちがいノート・ぼくの図鑑・わたしの図鑑）、見落としと結果の言い方
  - 手引きの入口：わざの子ども向けの名前（`kid_name`）を先に、大人向けの名前を横に小さく
  - 手引き（技能・3つの考え方）：何も選んでいなければ子ども向けの説明（`?kid=` で選んだ向きが先）
  - 練習メニュー：子ども向けの説明（`kid_description`）を先に
  - 名推理カード：表に「まずは声に出して言ってみよう」（`guide.front.say_aloud`。ほかの人には `think_first`）、子ども向けのヒントを初めから開いておく

## 段の呼び名と、画面の決まった言葉（words.md の 1-2）

| 画面の言葉 | コードの名前（config／部品） |
|---|---|
| なぞ → 見たこと → どうして？ → もしそうなら → 答え合わせ（見たこと＝手がかり・どうして？＝見立て・もしそうなら＝予想。設計書の言葉は初めて出る所で一言説明） | `case_list.steps`／`<x-ui.progress>` |
| いまやること（問い・例・入力欄・主なボタンの1枚） | `<x-ui.step-card>` |
| ステップ 2 / 5・いまここ・済み | `ui.step`・`ui.now`・`ui.done`／`<x-ui.progress>` |
| あとでできること | `ui.later`／`step-card` の `later` |
| これでOK（保存のボタン。押したら「保存したよ」） | 各画面のかたまりの `save`（`case_partner.write.save` など） |
| 確かめた（答え合わせのはんこ。中に結果） | `ui.stamp_top`／`<x-ui.stamp>` |
| 事件 No.12（URL の番号と同じ） | `case_board.page_title`・`case_list.case_no`・`resolve.back`／`case_files.id` |
| No.3（手がかりの荷札の番号） | `ui.tag_no`／`<x-ui.evidence-tag>` |
| 提案のチップ（書き出し・ほかの説明の型・候補。押すと入力欄に入るだけ） | `suggest.captions`／`<x-case.suggest-chips>` |
| ◯◯にスマホを渡してね・受け取った！はじめる（相棒モード） | `ui.handoff_title`・`ui.handoff_button`／`<x-ui.handoff>` |

## ChatGPT・Claude とつなぐ（読むだけの窓口。words.md の 1-3。決まりの全体は `rules/ai-connect.md`）

画面では「窓口」「MCP」と言わず「ChatGPT・Claude とつなぐ」で通す（説明書だけ「MCP（AI とアプリをつなぐ決まり）」と言い換える）。

| 画面の言葉 | コードの名前（config／部品／URL） |
|---|---|
| ChatGPT・Claude とつなぐ（読むだけ。仕事の場面は見せない） | 設定の枠 `<x-settings.ai-panel>`／`ai_connect.panel` |
| 窓口の住所（`https://suiri.agi-japan.com/mcp`） | `/mcp`・`App\Mcp\Servers\SuiriServer`／`panel.address_label` |
| つないでいる AI（名前・戻る先・最後につながった日） | `App\Queries\Ai\ConnectedAppsQuery`（Passport の `oauth_access_tokens` を AI ごとにまとめる）／`panel.connected_title` |
| つなぐのをやめる（通行証をすべて取り消す） | `App\Actions\Ai\RevokeAiAccess`／`DELETE /settings/ai/{app}`（`settings.ai.revoke`）／`panel.stop` |
| 同意の画面（許可する・やめる。AI の名前・戻る先・読めるもの・読めないもの・渡る会社と国） | `views/oauth/authorize`（値は `App\Support\Ai\ConsentView`）／`/oauth/authorize`（`passport.authorizations.*`）／`ai_connect.consent` |
| 許可したあとに戻る先（確かな手がかりは名前でなく住所） | `ConsentView` の `redirectHost`／`consent.host_label` |
| 通行証・更新の札（1時間・30日。説明書とポリシーで使う） | Passport の `oauth_access_tokens`・`oauth_refresh_tokens`／`ai_connect.tokens` |
| AI に相談する用に写す（写すボタン。端末に写すだけ・仕事の場面では出さない） | `<x-case.ai-copy>`・`App\Support\Ai\ConsultText`・`resources/js/parts/ai-copy.js`／`ai_connect.copy` |

## 使わない言葉（`bin/check` が画面の文言で止める）

- 診断・治療・治る・効く・処方・おすすめの施術（医療の判断に見える）→ 見立て・変化・確かめること。「改善」も画面では使わない（機械では止めていない。ワトソンの検出用の言葉だけは例外）。
- 患者・顧客・カルテ、コードの `patient`・`customer`・`client`・`karte` → 作らない（他人を表す表・列・クラスを持たない）。OAuth の決まった名前（`oauth_clients`・`client_id`・`Passport::client()`）だけは例外で、つないでくる AI のサービスのこと。自前の名前では「AI」「app」と呼ぶ（`rules/ai-connect.md`）。
- 頭が良くなる・思考力が伸びる・脳トレ・認知症予防 → 観察・仮説・検証の練習。
- 正解・不正解（日常の事件について）→ 当たり・外れ（予想について）。
- 演繹・帰納・アブダクション → 毎日の画面では出さない（手引きの画面だけ）。
- 連続◯日・ランキング・ポイント → 確かめた件数・考えを変えた件数。
- シャーロック・ホームズをアプリ名・ロゴの主役にしない（作品の紹介としてだけ）。
- 否定の注意書き（「診断ではありません」「病気の診断・治療・予防を目的としていません」「連続記録やポイントはないよ」「連続記録なし」「点数はつけない」）は通る（`bin/check` の `NEGATION`）。「連続5日！」の表示と、「連続記録がないと続かない」「〜しないと」のような、ほしがらせる・せかす言い方は止まる。例外を足すときは `bin/check` の `WORD_ALLOW` に理由つきで。

## 壊さない決まり

1. **予想の文（`statement`）と確かさ（`confidence`）は保存後に書き換えない**（後知恵を防ぐ）。直す画面・ルート・Action を作らない。考えが変わったら新しい予想を立てる。取り下げ（`withdrawn_at`）は答え合わせ待ち（`isPending()`）の予想だけできる（答え合わせ済み・取り下げ済みは受付が 403、Action は何もしない）。答え合わせの画面では最初の予想の文と書いた日時（`created_at`）を必ず見せる。
   - 答え合わせ待ちの予想の**確かめるタスク**（`Task::checksPendingPrediction()`）：期日・時刻を直す（タスクを直す画面・専用カレンダー）と、予想の `check_on`・`check_at` も同じ値にする（`Tasks\SyncPredictionCheck`。同じトランザクション）。期日は空にできない。題名（予想の文の写し）は直せない。消せない（403）ので、やめるときは予想を取り下げる（直す画面に取り下げのボタン）。
2. **他人を表す表・列を作らない。** すべての記録は `user_id` を持ち、取り出すときは必ず持ち主で絞る（Policy で強制し、機能ごとに「他人の記録が見えない」テスト）。親を消したら子も消える（`cascadeOnDelete`）。
3. **カレンダーに本文を送らない。** 送るのはタスクの題名・日時・アプリに戻るリンクと、どのタスクの予定かを見分ける番号（`suiri_task_id`）だけ（番号はアプリの中の通し番号で、利用者の記録の中身ではない。`rules/google.md`）。
4. **ワトソンのひとことは保存を止めない。** 一度に1つ、閉じられる（`dismissed_hints`）。言葉と文は `config/watson.php` に集め、コードに直書きしない。
5. **連続記録・ポイント・順位を作らない。** 休んでも何も減らない。
6. **相棒モード：見せ合うまで（`revealed_at` が null の間）互いの見立てを隠す。**
7. **日付だけの列は `Y-m-d` の文字列で比べる**（sqlite と MySQL の違いを避ける）。状態・種類の列は文字列で持ち、`app/Enums/` にキャスト。
8. **本番の記録を消さない。** 表を足す・直すのは移行（migration）だけ。`migrate:fresh` や seed のやり直しを前提にしない。
9. **回数制限は名前つき**（`AppServiceProvider` の `RateLimiter::for`）。`throttle:10,1` は使わない（`bin/check` が止める）。
10. 運営者が記録の中身を読める管理画面を作らない（招待制に戻したときの名簿の管理もコマンドで）。
11. **登録できる人は `config('suiri.invite_only')` だけで切り替える**（既定 false＝一般公開。戻すときは `.env` に `SUIRI_INVITE_ONLY=true`）。名簿と照らし合わせるのは true のときだけで、コードに「招待制」「一般公開」を決め打ちしない。前から使っている人は `google_id` で照合するので、どちらのときもログインできる。

## 入口

| やりたいこと | 触る場所 |
|---|---|
| 層・置き場所・行数の上限・名前の付け方 | `rules/architecture.md` |
| 数字・文言・選択肢・表示の言葉 | `config/suiri.php`（ワトソンは `config/watson.php`、決まりごとのページは `config/legal.php`） |
| 画面とルートの一覧 | `docs/design/basic-design.md` の 2 |
| 表の列 | `docs/design/data-model.md` |
