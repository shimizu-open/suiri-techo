{{--
  「ほかにできること」：いまやることのカードの下に、小さく1行ずつ。押すと、その用事がいまやることのカードになる（?open=…）。
  捜査中：見たことをもう1つ・見立てをもう1つ・AI に相談する用に写す（<x-case.ai-copy>。見立てが見えて、仕事の場面でないときだけ。
  「見立てをもう1つ書く」のすぐ下に置く）・見立てを見直す・なぞを直す・事件を閉じる。
  相棒モードで見せ合う前は、見立てにかかわる行を出さない（見立ては渡し合いの流れで書く）。閉じた事件：開き直す・なぞを直す。
  いま出ているカードと同じ用事の行は出さない。
  使い方：<x-case-step.more :card="$card" :case :hypotheses :turn :board :kid />（card は desk が決めたカードの種類。kid：子ども向けの言葉）
--}}
@props(['card', 'case', 'hypotheses', 'turn' => null, 'board' => null, 'kid' => false])
@php($w = ($kid ? config('suiri.case_board.kid.more') : []) + config('suiri.case_board.more'))
@php($open = $case->isOpen())
@php($together = $open && $turn === null)
@php($rows = array_filter([
  'add-clue' => $open && ! in_array($card, ['add_clue', 'add-clue'], true) ? ['eye', $w['add_clue']] : null,
  'add-hypothesis' => $together && $case->clues->isNotEmpty() && ! in_array($card, ['add_hypothesis', 'add-hypothesis', 'all_eliminated'], true) ? ['question', $w['add_hypothesis']] : null,
  'ai-copy' => $together && $hypotheses->isNotEmpty() && $case->scene !== \App\Enums\Scene::Work ? ['sparkle', null] : null,
  'edit-hyps' => $together && $hypotheses->isNotEmpty() && $card !== 'edit-hyps' ? ['check', $w['edit_hyps']] : null,
  'edit-case' => $card !== 'edit-case' ? ['book', $w['edit_case']] : null,
  'close' => $open && $card !== 'close' ? ['attic', $w['close']] : null,
]))
<nav aria-label="{{ $w['heading'] }}" {{ $attributes }}>
  <h2 class="text-sm font-bold text-muted">{{ $w['heading'] }}</h2>
  <ul class="mt-1">
    {{-- 出す行を1つずつ、押すとその用事のカードになるリンクにする --}}
    @foreach ($rows as $key => [$icon, $label])
      <li class="border-b border-rule">
        {{-- AI に相談する用に写す行は写すボタンの部品を、ほかはリンクを出す --}}
        @if ($key === 'ai-copy')
          <div class="py-1"><x-case.ai-copy :case="$case" :board="$board" /></div>
        @else
          <a href="{{ route('cases.show', ['case' => $case, 'open' => $key]) }}" class="flex min-h-12 items-center gap-3 py-1 text-base text-ink no-underline hover:bg-mark">
            <x-ui.step-icon :name="$icon" class="size-5 text-muted" />
            <span class="min-w-0 flex-1">{{ $label }}</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" class="size-4 text-muted"><path d="m9 6 6 6-6 6"/></svg>
          </a>
        @endif
      </li>
    @endforeach
    {{-- 閉じた事件なら、開き直すボタンを最後の行に置く --}}
    @if (! $open)
      <li class="border-b border-rule">
        <form method="POST" action="{{ route('cases.reopen', $case) }}">
          @csrf
          @method('DELETE')
          <button type="submit" class="flex min-h-12 w-full cursor-pointer items-center gap-3 py-1 text-left text-base text-ink hover:bg-mark">
            <x-ui.step-icon name="sparkle" class="size-5 text-muted" />
            <span class="flex-1">{{ config('suiri.case_board.cards.closed.reopen') }}</span>
          </button>
        </form>
      </li>
    @endif
  </ul>
</nav>
