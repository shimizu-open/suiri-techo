{{--
  このアプリについて（GET /about）。何をするアプリか・言えること言わないこと・注意書き・作った理由。
  注意書きの文は config('suiri.notes')（docs の調査 R5 の 9-2 の文案）。いちばん上に、やさしい言い方の一言と要点（<x-legal.plain>）を置く
--}}
<x-layouts.guest title="このアプリについて">
  <h1 class="text-2xl">このアプリについて</h1>
  <x-legal.plain page="about" />
  <x-legal.article>
    <p>推理手帳（Deduction Notebook）は、シャーロック・ホームズの作品に出てくるものの見方を、毎日の生活の中で練習して記録する手帳です。</p>

    <h2>何をするアプリ？</h2>
    <p>予定や出来事から「気になること」を事件にし、手がかりから見立てを立て、「もしそうなら」の予想を確かめる日をカレンダーに入れ、答え合わせで見立てを直します。タスク管理は「確かめることを忘れない」ために使います。</p>
    <ol>
      <li><strong>手がかり</strong>：見たこと・聞いたこと・「なかった」ことを、考えたことと分けて書きます。</li>
      <li><strong>見立て</strong>：説明の案を2つ以上立て、手がかりに ○ と × を付けて、合わないものを消していきます。</li>
      <li><strong>もしそうなら</strong>：見立てが正しければ見えるはずのことを予想し、確かさ（50〜95％）と確かめる日を決めます。予想の文と確かさは、あとから書き換えられません（後知恵を防ぐため）。</li>
      <li><strong>答え合わせ</strong>：当たり・外れ・どちらとも言えない・前提が崩れた、を記録します。外れはノーベリ帳に、わかったことは屋根裏の棚にためます。</li>
    </ol>

    <h2>お約束できること・できないこと</h2>
    <ul>
      <li>言えること：観察 → 仮説 → 予想 → 確かめる → 振り返る、の手順を、自分の生活で練習して記録する手帳です。</li>
      <li>言わないこと：{{ config('suiri.notes.effect') }}</li>
      <li>採点や正解の判定はしません。答え合わせは、確かめる日の現実に任せます。アプリの中では AI を使っていません（自分の ChatGPT・Claude から記録を読ませる窓口は、つなぎたい人だけが設定の画面からつなげます）。</li>
      <li>連続記録・ポイント・順位はありません。休んでも何も減りません。</li>
    </ul>

    <h2>気をつけてほしいこと</h2>
    <ul>
      <li>{{ config('suiri.notes.medical') }}</li>
      <li>{{ config('suiri.notes.privacy') }}</li>
      <li>施術者の方へ：{{ config('suiri.notes.privacy_work') }}</li>
      <li>Google カレンダーとつなぐと（任意）　送るもの：{{ config('suiri.calendar.consent.send') }}　送らないもの：{{ config('suiri.calendar.consent.not_send') }}　読むもの：{{ config('suiri.calendar.consent.receive') }}</li>
      <li>{{ config('suiri.notes.age') }}</li>
    </ul>

    <h2>ホームズの名前について</h2>
    <p>作品の紹介として、アーサー・コナン・ドイルのシャーロック・ホームズの物語（パブリックドメインの原文）を引用しています。本アプリは、作品の権利者とは関係がありません。</p>

    <h2>運営者</h2>
    <p>{{ config('legal.operator_name') }}（{{ config('legal.contact_email') }}）</p>
    <p>くわしくは<a href="{{ route('privacy') }}">プライバシーポリシー</a>と<a href="{{ route('terms') }}">利用規約</a>をご覧ください。</p>
  </x-legal.article>
</x-layouts.guest>
