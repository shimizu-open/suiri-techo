{{--
  いまやることのカードに添える吹き出し（1つだけ）。ワトソンのひとことがあればそれを、なければこの段の言葉の一言の説明（explain）を出す。
  ワトソンのひとこと（決まった規則の問いかけ。AI ではない）は、問いの右の × で閉じられ、閉じた規則はその事件で出さない（保存は止めない）。
  × は問いと同じ行に置く（吹き出しの下に × だけの行を作らず、主なボタンのまわりを短く保つ）。
  保存した直後の警告（ほかの人がわかる情報らしきもの）は深い赤の吹き出しにし、説明も開いたまま出す。
  使い方：<x-case-step.bubble :note="$watson" :case="$case" explain="見立て＝…" />（note は WatsonNotes::forCase() の値か null）
  フォームの中に置かない（閉じるボタンが自分のフォームを持つため）
--}}
@props(['note' => null, 'case', 'explain' => null])
@php($w = config('suiri.case_board.watson'))
{{-- ワトソンのひとことがあればそれを、なければ言葉の説明があるときだけ、それを出す --}}
@if ($note)
  @php($alert = $note['alert'] || $note['rule'] === 'personal_info')
  <x-ui.hint :label="$w['label']" :tone="$alert ? 'answer' : 'plain'" {{ $attributes }}>
    <div class="flex items-start gap-2">
      <div class="min-w-0 flex-1">
        <p class="font-bold leading-snug">{{ $note['title'] }}</p>
        {{-- どの文で当てはまったかがわかれば、引用して見せる --}}
        @if ($note['quote'])
          <p class="mt-1 border-l-2 border-rule pl-2 text-sm">「{{ $note['quote'] }}」</p>
        @endif
        {{-- 警告なら説明を開いたまま、ふだんは「くわしく」にたたんで出す（押す所は 48px。見た目の行は詰める） --}}
        @if ($alert)
          <p class="mt-1 text-sm leading-relaxed">{{ $note['body'] }}</p>
        @else
          <details class="group/why mt-1 text-sm">
            <summary class="-my-2 inline-flex min-h-12 cursor-pointer items-center font-semibold text-muted underline underline-offset-2">{{ $w['more'] }}</summary>
            <p class="mt-1 leading-relaxed text-muted">{{ $note['body'] }}</p>
          </details>
        @endif
      </div>
      <form method="POST" action="{{ route('hints.dismiss', [$case, $note['rule']]) }}" class="-mt-7 -mr-3 shrink-0">
        @csrf
        <button type="submit" aria-label="{{ $w['close'] }}" title="{{ $w['close'] }}"
          class="grid size-12 cursor-pointer place-items-center rounded-xl text-xl text-muted hover:bg-mark hover:text-ink">×</button>
      </form>
    </div>
  </x-ui.hint>
@elseif (filled($explain))
  <x-ui.hint {{ $attributes }}>{{ $explain }}</x-ui.hint>
@endif
