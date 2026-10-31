{{--
  相棒の渡し合いの「1人の番」（相棒か自分）。その人がまだ書いていなければ「スマホを渡してね」の付箋、書いたあとは ○ を付けるカードを出す。
  「はじめる」「ほかの理由も書く」「書けたら、長男さんをよぼう」は同じページの中の印（#relay-…）へのリンクで、印の付いた部分だけを見せる
  （CSS の :target。JavaScript なしで動き、ページを読み直しても同じ所に戻る）。書いた文の送り直し（エラー）のときは、書く欄を初めから開く。
  使い方：<x-case-partner.turn :case :who="'partner'" :names :word :kid />（flow が置く。names は2人の呼び名、word は「見立て」の言葉）
--}}
@props(['case', 'who', 'names', 'word', 'kid' => false])
@php($other = $who === 'partner' ? 'self' : 'partner')
@php($mine = $case->hypotheses->filter(fn ($h) => $h->author->value === $who)->values())
@php($partnerCount = $case->hypotheses->filter->isByPartner()->count())
@php($step = $who === 'partner' ? 1 : 2)
@php($here = route('cases.show', ['case' => $case, 'turn' => $who]))
@php($retry = old('form_key') === 'partner-hypothesis')
@php($ids = ['write' => 'relay-'.$who.'-write', 'together' => 'relay-together'])
<div>
  {{-- 書く欄：送り直しのときは初めから見せ、それ以外は印（#relay-…-write）へ飛んだときだけ見せる。
       進み具合の丸は付けない（つまみの「ステップ 1 / 3」で足り、主なボタンを最初の画面に入れるため。丸は渡す付箋と「見せ合おう」に付ける） --}}
  <section id="{{ $ids['write'] }}" @class(['peer/write scroll-mt-16', 'hidden target:block' => ! $retry])>
    <x-case-partner.write-card :case="$case" :who="$who" :names="$names" :word="$word" :kid="$kid" :step="$step"
      :cancel="$mine->isNotEmpty() ? $here : null"
      :skip="$who === 'partner' && $mine->isEmpty() ? route('cases.show', ['case' => $case, 'turn' => $other]) : null" />
  </section>

  {{-- 送り直し（書く欄だけを見せる）のときでなければ、ほかの段も置く --}}
  @unless ($retry)
  {{-- まだ書いていなければ付箋、書いたあとは ○ を付けるカード（自分の番なら「見せ合おう」のカードも）を出す --}}
  @if ($mine->isEmpty())
    <div class="space-y-4 peer-target/write:hidden">
      <x-case-partner.relay-progress :at="$who" :names="$names" :partner-done="$partnerCount > 0" />
      <x-ui.handoff :to="$names[$who]" :href="'#'.$ids['write']">
        {{-- ひとことの2つの文（だれの番か・だれが見ないか）を1行ずつ出す --}}
        @foreach (config('suiri.case_partner.handoff') as $line)
          <span class="block">{{ __($line, ['name' => $names[$who], 'other' => $names[$other]]) }}</span>
        @endforeach
      </x-ui.handoff>
    </div>
  @else
    {{-- 自分の番なら、「いっしょに見せ合おう！」のカードを置いておく（印 #relay-together へ飛んだときだけ見せる） --}}
    @if ($who === 'self')
      <section id="{{ $ids['together'] }}" class="peer/together hidden scroll-mt-16 space-y-4 target:block">
        <x-case-partner.relay-progress at="reveal" :names="$names" :partner-done="$partnerCount > 0" />
        <x-case-partner.together :case="$case" :names="$names" :partner-count="$partnerCount" :self-count="$mine->count()"
          :more="'#'.$ids['write']" :partner-href="route('cases.show', ['case' => $case, 'turn' => 'partner'])" />
      </section>
    @endif
    {{-- ○ のカード（書く欄・「見せ合おう」のカードを開いたときは隠す）。進み具合の丸は付けない（荷札とボタンを最初の画面に入れるため） --}}
    <div class="space-y-4 peer-target/write:hidden peer-target/together:hidden">
      <x-case-partner.mark-card :case="$case" :who="$who" :names="$names" :word="$word" :kid="$kid" :step="$step"
        :hypotheses="$mine" :more="'#'.$ids['write']">
        {{-- 相棒の番なら自分の番へ渡すリンク、自分の番なら「見せ合おう」のカードへ進むリンクを主なボタンにする --}}
        @if ($who === 'partner')
          <x-ui.big-button :href="route('cases.show', ['case' => $case, 'turn' => 'self'])" tone="hypothesis" icon="partner">{{ __(config('suiri.case_partner.pass'), ['other' => $names['self']]) }}</x-ui.big-button>
        @else
          <x-ui.big-button :href="'#'.$ids['together']" tone="hypothesis" icon="partner">{{ __(config('suiri.case_partner.call'), ['other' => $names['partner']]) }}</x-ui.big-button>
        @endif
      </x-case-partner.mark-card>
    </div>
  @endif
  @endunless
</div>
