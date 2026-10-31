{{--
  事件を作る画面の「つぎへ」の大きなボタン。押すと次の段へ進むだけで、送信はしない（resources/js/parts/stepper.js）。
  使い方：<x-case-new.next tone="clue" />。tone：<x-ui.big-button> の色（primary・clue・hypothesis など）。
  JavaScript が動かないときは全部の段が縦に並ぶので、このボタンは隠れたまま（フォームに data-stepping が付いたときだけ見せる）
--}}
@props(['tone' => 'primary'])
<div class="hidden group-data-[stepping]/new:block">
  <x-ui.big-button type="button" data-step-next :tone="$tone">{{ config('suiri.case_new.next') }}</x-ui.big-button>
</div>
