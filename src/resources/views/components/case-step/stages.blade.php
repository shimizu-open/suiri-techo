{{--
  進み具合と、済んだ段の要約。スマホでは、横の丸の列（<x-ui.progress>）と、中身のある段の要約（たためる1行）だけを、カードの上に出す。
  パソコン（lg 以上）では右の細い列に、5つの段を縦に並べた目次にする（中身のある段は要約、ない段は丸と名前。いまの段は段の色の枠）。
  段の名前と並びは一覧と同じ（config の case_list.steps）、色は case_board.stage_tones。
  使い方：<x-case-step.stages :case :hypotheses :hidden-count :turn :partner-name :stage="2" tone="check" :kid />
  stage：いまの段（0 から。null なら全部済み）。tone：いまの段の色を変えるとき（確かめる日を待つ間は check）。
  hypotheses：いま見えている見立て（相棒モードで見せ合う前は、いまの番の人の分だけ。hidden-count はかくれている数）
--}}
@props(['case', 'hypotheses', 'hiddenCount' => 0, 'turn' => null, 'partnerName' => null, 'stage' => null, 'tone' => null, 'kid' => false])
@php($labels = config('suiri.case_list.steps'))
@php($keys = array_keys($labels))
@php($tones = config('suiri.case_board.stage_tones'))
@php($w = config('suiri.case_board.done'))
@php($state = fn (int $i) => $stage === null || $i < $stage ? 'done' : ($i === $stage ? 'current' : 'todo'))
@php($toneOf = fn (int $i) => $i === $stage && $tone ? $tone : $tones[$keys[$i]])
@php($predictions = $hypotheses->flatMap->predictions->sortBy('id')->values())
@php($has = ['clue' => $case->clues->isNotEmpty(), 'hypothesis' => $hypotheses->isNotEmpty() || $hiddenCount > 0, 'prediction' => $predictions->isNotEmpty()])
@php($now = fn (int $i) => $state($i) === 'current' ? '・'.$w['now'] : '')
{{-- いまの段の丸の色（ring：枠／text：名前）。check は予想と同じ緑 --}}
@php($faces = [
  'plain' => ['ring' => 'border-ink', 'text' => 'text-ink'], 'clue' => ['ring' => 'border-clue', 'text' => 'text-clue'],
  'hypothesis' => ['ring' => 'border-hypothesis', 'text' => 'text-hypothesis'], 'prediction' => ['ring' => 'border-prediction', 'text' => 'text-prediction'],
  'check' => ['ring' => 'border-prediction', 'text' => 'text-prediction'], 'answer' => ['ring' => 'border-answer', 'text' => 'text-answer'],
])
<aside aria-label="{{ $w['heading'] }}" {{ $attributes->class(['min-w-0']) }}>
  <x-ui.progress class="lg:hidden" :count="false"
    :steps="collect($keys)->map(fn ($k, $i) => ['label' => $labels[$k], 'done' => $state($i) === 'done', 'current' => $state($i) === 'current', 'tone' => $toneOf($i)])->all()" />
  <h2 class="mb-1 text-sm font-bold text-muted max-lg:sr-only">{{ $w['heading'] }}</h2>
  <ol class="max-lg:mt-3">
    {{-- 段を1つずつ取り出す。中身（見たこと・見立て・予想）のある段は要約を、ない段はパソコンでだけ丸と名前を出す --}}
    @foreach ($keys as $i => $key)
      <li @class(['max-lg:hidden' => ! ($has[$key] ?? false)])>
        {{-- 段の種類で、出す要約を分ける（見たこと＝荷札の束・どうして？＝見立ての一覧・もしそうなら＝予想の一覧） --}}
        @switch(($has[$key] ?? false) ? $key : 'plain')
          @case('clue')
            <x-case-step.clue-pile :case="$case" :kid="$kid" :title="__($w['clues'], ['count' => $case->clues->count()]).$now($i)" />
            @break
          @case('hypothesis')
            <x-ui.done-summary icon="question" tone="hypothesis" :title="__($w['hypotheses'], ['count' => $hypotheses->count()]).$now($i)"
              :items="$hypotheses->map(fn ($h) => ($case->partner_mode && $turn === null ? ($h->isByPartner() ? $partnerName : config('suiri.labels.hypothesis_author.self')).'・' : '').($kid ? $h->status->kidLabel() : $h->status->label()).'：'.$h->body)
                ->when($hiddenCount > 0, fn ($items) => $items->push(__($w['hidden'], ['who' => $turn === 'self' ? $partnerName : config('suiri.labels.hypothesis_author.self'), 'count' => $hiddenCount])))->all()"
              :edit-href="$case->isOpen() && $turn === null && $hypotheses->isNotEmpty() ? route('cases.show', ['case' => $case, 'open' => 'edit-hyps']) : null"
              :edit-label="$w['review']" />
            @break
          @case('prediction')
            <x-ui.done-summary icon="arrow" tone="prediction" :title="__($w['predictions'], ['count' => $predictions->count()]).$now($i)"
              :items="$predictions->map(fn ($p) => match (true) {
                $p->isResolved() => $kid ? $p->result->kidLabel() : $p->result->label(),
                $p->isWithdrawn() => $w['withdrawn'],
                default => __($w['pending'], ['date' => \Illuminate\Support\Carbon::parse($p->check_on)->isoFormat('M月D日')]),
              }.'：'.$p->statement)->all()" />
            @break
          @default
            <p class="flex min-h-12 items-center gap-3 border-b border-rule py-2">
              {{-- 段の様子で丸の形を変える：済み＝塗った丸にチェック／いま＝段の色の枠／まだ＝うすい丸 --}}
              @switch($state($i))
                @case('done')
                  <span class="grid size-8 shrink-0 place-items-center rounded-full bg-mark text-ink"><x-ui.step-icon name="check" class="size-5" /></span>
                  <span class="font-semibold">{{ $labels[$key] }}</span>
                  @break
                @case('current')
                  <span class="grid size-8 shrink-0 place-items-center rounded-full border-[3px] bg-sheet {{ $faces[$toneOf($i)]['ring'] }}"></span>
                  <span class="font-bold {{ $faces[$toneOf($i)]['text'] }}">{{ $labels[$key] }}</span>
                  <span class="text-sm text-muted">{{ $w['now'] }}</span>
                  @break
                @default
                  <span class="grid size-8 shrink-0 place-items-center"><span class="size-4 rounded-full border-2 border-rule bg-paper"></span></span>
                  <span class="text-muted">{{ $labels[$key] }}</span>
              @endswitch
            </p>
        @endswitch
      </li>
    @endforeach
  </ol>
</aside>
