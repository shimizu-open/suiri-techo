{{--
  事件を作る画面の確かめの段で、札の右に置く小さな「直す」ボタン。押すと n 番目の段へもどる（resources/js/parts/stepper.js）。
  使い方：<x-case-new.goto :n="2" />
--}}
@props(['n'])
<button type="button" data-step-goto="{{ $n }}" class="inline-flex min-h-11 cursor-pointer items-center rounded-lg px-2 text-sm font-semibold text-muted underline underline-offset-4 hover:bg-sheet hover:text-ink">
  {{ config('suiri.case_new.confirm.edit') }}
</button>
