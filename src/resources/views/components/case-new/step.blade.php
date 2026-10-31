{{--
  事件を作る画面の1つの段（1問）の入れ物。上に進み具合の丸の列、その下に中身（いまやることのカード）を置く。
  使い方：<x-case-new.step :n="2"> <x-ui.step-card …>…</x-ui.step-card> </x-case-new.step>
  n：何番目の段か（1〜4。段の名前は config('suiri.case_new.steps')）。
  JavaScript が動くときは、resources/js/parts/stepper.js がいまの段だけを見せ、フォームに data-stepping を付ける。
  進み具合の丸の列は、そのときだけ見せる（JavaScript がないと全部の段が縦に並ぶので、丸の列は要らない）
--}}
@props(['n'])
{{-- 段ごとの色（なぞ・作るはふつうの紺、見たことは手がかりの紺、どうして？は見立てのからし色） --}}
@php($tones = ['plain', 'clue', 'hypothesis', 'plain'])
@php($steps = collect(config('suiri.case_new.steps'))->map(fn ($label, $i) => ['label' => $label, 'tone' => $tones[$i] ?? 'plain', 'done' => $i < $n - 1, 'current' => $i === $n - 1])->all())
<section data-step="{{ $n }}" {{ $attributes->class(['scroll-mt-16']) }}>
  <div class="mb-4 hidden group-data-[stepping]/new:block">
    <x-ui.progress :steps="$steps" :count="false" />
  </div>
  {{ $slot }}
</section>
