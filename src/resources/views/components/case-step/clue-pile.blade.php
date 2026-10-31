{{--
  集めた見たこと（手がかり）の束。たたむと「見たこと 3つ」の1行（<x-ui.done-summary> と同じ形）、開くと証拠品の荷札が並ぶ。
  捜査中なら荷札の右に「直す」を置き、押すと「見たことを直す」がいまやることのカードになる（?open=edit-clue-{id}）。
  使い方：<x-case-step.clue-pile :case="$case" :kid="$kid" title="見たこと 3つ" />（case は clues を読み込んだもの）
  種類の言葉は、仕事の場面なら仕事の言い方、ふだんの場面が親子の人なら子ども向けの言い方にする
--}}
@props(['case', 'kid' => false, 'title', 'open' => false])
@php($work = $case->scene === \App\Enums\Scene::Work)
<details @if ($open) open @endif {{ $attributes->class(['group border-b border-rule']) }}>
  <summary class="flex min-h-12 cursor-pointer items-center gap-3 py-2">
    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-clue-soft text-clue">
      <x-ui.step-icon name="eye" class="size-5" />
    </span>
    <span class="min-w-0 flex-1 truncate font-semibold">{{ $title }}</span>
    <span class="sr-only">{{ config('suiri.ui.summary_open') }}</span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 shrink-0 text-muted group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
  </summary>
  <ul class="space-y-2 pb-3">
    {{-- 見たことを足した順に1枚ずつ、番号つきの荷札にする --}}
    @foreach ($case->clues as $clue)
      <li>
        <x-ui.evidence-tag :no="$loop->iteration" :label="$work ? $clue->kind->label($case->scene) : ($kid ? $clue->kind->kidLabel() : $clue->kind->label())">
          <span class="break-words whitespace-pre-line">{{ $clue->body }}</span>
          {{-- 捜査中なら、直すカードへのリンクを札の右に置く --}}
          @if ($case->isOpen())
            <x-slot:actions>
              <a href="{{ route('cases.show', ['case' => $case, 'open' => 'edit-clue-'.$clue->id]) }}"
                class="inline-flex min-h-12 items-center rounded-lg px-2 text-sm font-semibold text-clue hover:bg-sheet">{{ config('suiri.case_board.done.edit') }}</a>
            </x-slot:actions>
          @endif
        </x-ui.evidence-tag>
      </li>
    @endforeach
  </ul>
</details>
