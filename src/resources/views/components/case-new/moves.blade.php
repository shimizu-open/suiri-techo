{{--
  事件を作る画面の、カードの下の小さな1行：左に「もどる」、右に「あとで書く」（書かずに次の段へ進む）。
  使い方：<x-case-new.moves skip />（skip を付けると「あとで書く」も出す）。押したときの動きは resources/js/parts/stepper.js。
  JavaScript が動かないときは全部の段が縦に並ぶので、この行は隠れたまま（フォームに data-stepping が付いたときだけ見せる）
--}}
@props(['skip' => false])
<div class="hidden items-center justify-between gap-3 group-data-[stepping]/new:flex">
  <button type="button" data-step-back class="-ml-2 inline-flex min-h-11 cursor-pointer items-center gap-1 rounded-lg px-2 text-base font-semibold text-muted hover:bg-mark hover:text-ink">
    <span aria-hidden="true">‹</span>{{ config('suiri.case_new.back') }}
  </button>
  {{-- 書かずに進めてよい段（見たこと・どうして？）だけ、「あとで書く」を出す --}}
  @if ($skip)
    <button type="button" data-step-next data-skip class="-mr-2 inline-flex min-h-11 cursor-pointer items-center gap-1 rounded-lg px-2 text-base font-semibold text-muted underline underline-offset-4 hover:bg-mark hover:text-ink">
      {{ config('suiri.case_new.later') }}<span aria-hidden="true">›</span>
    </button>
  @endif
</div>
