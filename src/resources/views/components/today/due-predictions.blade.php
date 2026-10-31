{{--
  今日の画面：いちばん上の答え合わせのカードに出した1件のほかに、まだ答え合わせを待っている予想の一覧。
  ふだんはたたんでおき（「ほかにも◯件あるよ」）、押すと開く（JavaScript なしの <details>）。1件ずつ押すと、その予想の答え合わせへ。
  使い方：<x-today.due-predictions :predictions="$rest" label="ほかにも2件あるよ" />
  predictions：Prediction のコレクション（事件 caseFile を読み込み済み）。label：たたんだときの一言
--}}
@props(['predictions' => [], 'label'])
<details {{ $attributes->class(['group']) }}>
  <summary class="inline-flex min-h-11 cursor-pointer items-center gap-1 font-semibold text-answer">
    {{ $label }}
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
  </summary>
  <ul class="mt-2 space-y-2 text-left text-ink">
    {{-- 待っている予想を1件ずつ取り出し、押すと答え合わせへ進む小さなカードにする --}}
    @foreach ($predictions as $prediction)
      <li>
        {{-- 予想の画面があれば押せるカード、なければ中身だけを出す --}}
        @if (Route::has('predictions.show'))
          <a href="{{ route('predictions.show', $prediction) }}" class="flex items-center gap-3 rounded-xl border border-rule bg-sheet px-4 py-3 no-underline hover:border-answer">
            <x-today.due-prediction :prediction="$prediction" compact class="flex-1" />
            <span aria-hidden="true" class="text-xl text-answer">›</span>
          </a>
        @else
          <x-today.due-prediction :prediction="$prediction" compact class="rounded-xl border border-rule bg-sheet px-4 py-3" />
        @endif
      </li>
    @endforeach
  </ul>
</details>
