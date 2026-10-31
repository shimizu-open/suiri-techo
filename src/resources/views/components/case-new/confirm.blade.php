{{--
  事件を作る画面の4問目「これで事件を作るよ」：なぞを事件の題（明朝体）で、見たこと・どうして？を荷札の形で見せ、「事件を作る」で送る。
  使い方：<x-case-new.confirm :draft="$draft" :kid="$kid" />（draft は Queries/Cases/NewCaseDraft の戻り値）。
  札の中身は、それまでの段の入力を resources/js/parts/stepper.js が写す（data-echo）。札の「直す」でその段へもどる。
  JavaScript が動かないときは全部の段が縦に並んでいるので、札は出さずに「事件を作る」のボタンだけを出す
--}}
@props(['draft', 'kid' => false])
@php($w = config('suiri.case_new.confirm'))
@php($names = config('suiri.case_new.steps'))
@php($word = $kid ? \App\Support\Label::kidWord('hypothesis') : \App\Support\Label::word('hypothesis'))
<x-case-new.step :n="4">
  <x-ui.step-card :step="4" :total="4" tone="plain" :title="$w['title']" :hint="$w['hint']">
    <div class="hidden space-y-2.5 group-data-[stepping]/new:block">
      <div class="flex items-start gap-2 border-b border-rule pb-3">
        <div class="min-w-0 flex-1">
          <p class="text-xs font-bold text-muted">{{ $names[0] }}<span aria-hidden="true">・</span><span data-echo="scene"></span></p>
          <p data-echo="title" class="mt-0.5 font-mincho text-xl font-bold break-words"></p>
        </div>
        <x-case-new.goto :n="1" />
      </div>

      <x-ui.evidence-tag tone="clue" :label="$names[1]">
        <span data-echo-filled="clue_body" class="block break-words">
          <span data-echo="clue_kind" class="mr-1 rounded-md bg-sheet px-1.5 py-0.5 text-sm font-bold text-clue"></span><span data-echo="clue_body"></span>
        </span>
        <span data-echo-empty="clue_body" class="block text-muted">{{ $w['empty'] }}</span>
        <x-slot:actions><x-case-new.goto :n="2" /></x-slot:actions>
      </x-ui.evidence-tag>

      <x-ui.evidence-tag tone="hypothesis" :label="$word">
        <span class="block group-has-[input[data-partner]:checked]/new:hidden">
          <span data-echo-filled="hypothesis_body" class="block break-words"><span data-echo="hypothesis_body"></span></span>
          <span data-echo-empty="hypothesis_body" class="block text-muted">{{ $w['empty'] }}</span>
        </span>
        {{-- 相棒の名前がある人には、相棒モードを入れたときの言い方を用意しておく（入れたときだけ見える） --}}
        @if ($draft['partner'])
          <span class="hidden text-muted group-has-[input[data-partner]:checked]/new:block">{{ __($w['partner'], ['partner' => $draft['partner']]) }}</span>
        @endif
        <x-slot:actions><x-case-new.goto :n="3" /></x-slot:actions>
      </x-ui.evidence-tag>
    </div>

    <x-slot:action><x-ui.big-button icon="plus">{{ $w['submit'] }}</x-ui.big-button></x-slot:action>
    <x-slot:later><x-case-new.moves /></x-slot:later>
  </x-ui.step-card>
</x-case-new.step>
