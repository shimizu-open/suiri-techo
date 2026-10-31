{{--
  事件の画面の机の上：どのカードを「いまやること」にするかを決め、進み具合・カード・ほかにできることを並べる。
  スマホは上から 進み具合 → 済んだ段 → カード → ほかにできること（閉じた事件だけは、はんこと「つまり？」を先にし、済んだ段は下へ。
  相棒の番の間は、渡し合いの部品が自分の進み具合と手がかりを出すので、進み具合と済んだ段を出さず、主なボタンを最初の画面に入れる）。
  パソコン（lg 以上）は、カードを左の広い列、進み具合と済んだ段を右の細い列に置く。
  相棒モードで見せ合う前は、見立ての一覧を出さない（かくれた見立ての数も見せない。いまの番の見立ては渡し合いの部品の中だけ）。
  カードの決め方：閉じた事件 → 閉じたカード、相棒モードで見せ合う前 → 相棒の渡し合い、それ以外は ?open= の頼み
  （edit-case・edit-clue-{id}・edit-hyps・add-clue・add-hypothesis・close・fit-{id}・predict-{id}）を先にし、
  なければいまの一手（NextStep の key）。いまの一手が「予想を立てる」で、まだ ○× のない手がかりがあれば、先に ○× のカード（とばせる）。
  使い方：board.blade.php から CaseBoardQuery の値をそのまま渡す
--}}
@props([
    'case', 'hypotheses', 'hiddenCount' => 0, 'turn' => null, 'partnerName' => null, 'selfName' => null, 'kid' => false,
    'step', 'watson' => null, 'board' => null, 'suggest' => [], 'atticOptions' => [], 'prompts' => null, 'open' => null,
    'today' => null, 'checkDefault' => null, 'calendarConnected' => false,
])
@php($openId = fn (string $prefix) => str_starts_with((string) $open, $prefix) ? (int) substr((string) $open, strlen($prefix)) : null)
@php($editClue = $case->clues->firstWhere('id', $openId('edit-clue-')))
@php($alive = $hypotheses->filter(fn ($h) => $h->status->isAlive()))
@php($asked = $case->hidesHypotheses() ? null : $alive->firstWhere('id', $openId('predict-') ?? $openId('fit-')))
@php($target = $asked ?? ($step['hypothesis'] ?? null))
@php($unmarked = $target ? $case->clues->reject(fn ($c) => $target->fits->contains('clue_id', $c->id)) : collect())
@php($fitting = $target && $asked === null && $unmarked->isNotEmpty() && ($case->clues->count() > 1 || $alive->count() > 1))
{{-- どのカードを出すかを、上から順に当てはまったもので決める（閉じた → 見立てにかかわらない ?open= の頼み → 相棒の番 →
     見立てにかかわる ?open= の頼み → ○× の段 → いまの一手） --}}
@php($card = match (true) {
    $case->isClosed() => $open === 'edit-case' ? 'edit-case' : 'closed',
    $open === 'edit-case' => 'edit-case',
    $editClue !== null => 'edit-clue',
    in_array($open, ['add-clue', 'clue'], true) => 'add-clue',
    $open === 'close' => 'close',
    $turn !== null => 'partner',
    $open === 'edit-hyps' && $hypotheses->isNotEmpty() => 'edit-hyps',
    $open === 'add-hypothesis' && $case->clues->isNotEmpty() => 'add-hypothesis',
    $asked !== null && str_starts_with((string) $open, 'fit-') && $case->clues->isNotEmpty() => 'fit',
    $asked !== null => 'predict',
    $step['key'] === 'predict' && $fitting => 'fit',
    default => $step['key'],
})
@php($stage = config('suiri.case_board.stage_of.'.($card === 'fit' && $asked === null ? 'fit' : $step['key'])))
@php($main = $card === $step['key'] || ($card === 'fit' && $asked === null) || $card === 'partner')
<div {{ $attributes->class(['grid gap-5 lg:grid-cols-[minmax(0,1fr)_15rem] lg:items-start lg:gap-8']) }}>
  <x-case-step.stages :case="$case" :hypotheses="$turn === null ? $hypotheses : collect()" :hidden-count="0" :turn="$turn" :partner-name="$partnerName"
    :stage="$stage" :tone="$step['key'] === 'wait' ? 'check' : null" :kid="$kid"
    :class="\Illuminate\Support\Arr::toCssClasses(['lg:sticky lg:top-16 lg:col-start-2 lg:row-start-1', 'max-lg:order-last' => $card === 'closed', 'max-lg:hidden' => $card === 'partner'])" />

  <div class="min-w-0 lg:col-start-1 lg:row-start-1">
    <x-case-step.now :card="$card" :case="$case" :step="$step" :hypotheses="$hypotheses" :target="$target" :edit-clue="$editClue"
      :hidden-count="$hiddenCount" :turn="$turn" :partner-name="$partnerName" :self-name="$selfName" :kid="$kid" :watson="$watson"
      :suggest="$suggest" :attic-options="$atticOptions" :prompts="$prompts" :today="$today" :check-default="$checkDefault"
      :calendar-connected="$calendarConnected" :number="$main && $stage !== null ? $stage + 1 : null" />
    <x-case-step.more :card="$card" :case="$case" :hypotheses="$hypotheses" :turn="$turn" :board="$board" :kid="$kid" class="mt-8" />

    {{-- 相棒モードで見せ合ったあとなら、相棒の渡し合いの部品の「見せ合い」を下に置く（中に id="reveal" があり、見せ合った直後はここへ戻る） --}}
    @if ($board !== null)
      <details open class="group mt-8">
        <summary class="flex min-h-12 cursor-pointer items-center gap-3 border-b border-rule font-semibold">
          <x-ui.step-icon name="partner" class="size-6 text-hypothesis" />
          <span class="flex-1">{{ __(config('suiri.case_board.more.reveal'), ['count' => collect($board['rows'])->where('differs', true)->count()]) }}</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" class="size-5 text-muted group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <div class="pt-4">
          <x-case-partner.flow :case="$case" :board="$board" :partner-name="$partnerName" :self-name="$selfName" :kid="$kid" />
        </div>
      </details>
    @endif
  </div>
</div>
