{{--
  相棒の渡し合い（C-11）の入口。1台のスマホを相棒と自分で渡し合って見立てを書き、いっしょに見せ合う流れを、いまの段の大きなカード1枚で出す。
  段：① 相棒にスマホを渡す（付箋）→ ② 相棒が書いて、合う手がかりに ○ を付ける →「書けたら、父さんにわたしてね」→
      ③ 自分の番（同じ流れ）→ ④「いっしょに見せ合おう！」→ ⑤ 見せ合い（2人の見立てと、手がかりの荷札ごとの ○× の違い）
  使い方：<x-case-partner.flow :case="$case" :board="$board" :partner-name="$partnerName" :turn="$turn" :self-name="$selfName" :kid="$kid" />
  case：clues・hypotheses.fits を読み込んだ事件（App\Queries\Cases\CaseBoardQuery の case）。
  turn：見せ合う前のいまの番（'partner'・'self'。見せ合ったあとは null）。board：見せ合ったあとの表（RevealBoard::build() の値。見せ合う前は null）。
  partner-name：相棒の名前（設定）。self-name：自分の表示名（設定。省くと「あなた」）。kid：子ども向けの言葉を出すか（ふだんの場面が親子の人）。
  見せ合う前は、いまの番の人の見立てだけを出す（もう片方の文は HTML にも入れない）。相棒モードでない事件では何も出さない
--}}
@props(['case', 'board' => null, 'partnerName' => null, 'turn' => null, 'selfName' => null, 'kid' => false])
@php($t = config('suiri.case_partner'))
{{-- 2人の呼び名（長男さん・父さん）。名前がなければ「相棒」「あなた」 --}}
@php($names = [
  'partner' => filled($partnerName) ? $partnerName.$t['honorific'] : config('suiri.labels.words.partner'),
  'self' => filled($selfName) ? $selfName.$t['honorific'] : $t['self_fallback'],
])
@php($word = $kid ? \App\Support\Label::kidWord('hypothesis') : \App\Support\Label::word('hypothesis', $case->scene))
{{-- 見せ合ったあとなら見せ合いの画面を、見せ合う前で捜査中ならいまの番の流れを出す（どちらでもなければ何も出さない）。
     どちらも、日本語を文節で折り返す（「渡し／てね」のように語の途中で切らない。中の部品にも受け継がれる） --}}
@if ($board !== null)
  <div {{ $attributes->class(['[word-break:auto-phrase]']) }}>
    <x-case-partner.reveal :case="$case" :board="$board" :names="$names" :word="$word" :kid="$kid" />
  </div>
@elseif (in_array($turn, ['partner', 'self'], true) && $case->isOpen())
  <div {{ $attributes->class(['[word-break:auto-phrase]']) }}>
    <x-case-partner.turn :case="$case" :who="$turn" :names="$names" :word="$word" :kid="$kid" />
  </div>
@endif
