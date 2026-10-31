{{--
  いまやることのカードを1枚だけ出す（どれを出すかは desk が決めて card で渡す）。保存した直後の警告（ほかの人がわかる情報らしきもの）だけは、
  見落とさないようカードの上に出し、ふだんのワトソンのひとことはカードの中（主なボタンの下）に1つだけ添える。
  card：add_clue・add-clue（もう1つ）・add_hypothesis・add-hypothesis（もう1つ）・all_eliminated・fit・predict・wait・resolve・close・closed・
        partner（相棒モードで見せ合う前）・edit-case・edit-clue・edit-hyps。number：つまみの「ステップ n / 5」（いまの一手のカードだけ。ほかは null）
  使い方：desk から <x-case-step.now :card :case :step :hypotheses :target :edit-clue … /> と渡す
--}}
@props([
    'card', 'case', 'step', 'hypotheses', 'target' => null, 'editClue' => null, 'hiddenCount' => 0, 'turn' => null, 'partnerName' => null,
    'selfName' => null, 'kid' => false, 'watson' => null, 'suggest' => [], 'atticOptions' => [], 'prompts' => null, 'today' => null,
    'checkDefault' => null, 'calendarConnected' => false, 'number' => null,
])
@php($total = count(config('suiri.case_list.steps')))
@php($alert = $watson && ($watson['alert'] || $watson['rule'] === 'personal_info'))
@php($note = $alert ? null : $watson)
<section aria-label="{{ config('suiri.ui.now') }}" {{ $attributes }}>
  {{-- 保存した直後の警告なら、カードの上に赤い吹き出しで出す --}}
  @if ($alert)
    <x-case-step.bubble :note="$watson" :case="$case" class="mb-4" />
  @endif

  {{-- カードの種類で、出すカードを分ける --}}
  @switch($card)
    @case('add_clue')
    @case('add-clue')
      <x-case-step.clue-card :case="$case" :kid="$kid" :more="$card === 'add-clue'" :number="$number" :total="$total" :note="$note"
        :prompt="$prompts['clue'] ?? null" :questions="$suggest['clue'] ?? []" />
      @break
    @case('add_hypothesis')
    @case('add-hypothesis')
    @case('all_eliminated')
      <x-case-step.hypothesis-card :case="$case" :kid="$kid" :number="$number" :total="$total" :note="$note"
        :variant="$card === 'all_eliminated' ? 'eliminated' : ($hypotheses->isEmpty() ? 'first' : 'more')" :prompt="$prompts['hypothesis'] ?? null"
        :types="$suggest['types'] ?? []" :emphasize="$suggest['emphasize'] ?? false" :attic="$suggest['attic'] ?? []" />
      @break
    @case('fit')
      <x-case-step.fit-card :case="$case" :hypothesis="$target" :kid="$kid" :number="$number" :total="$total" :note="$note" />
      @break
    @case('predict')
      <x-case-step.predict-card :case="$case" :hypothesis="$target" :kid="$kid" :number="$number" :total="$total" :note="$note"
        :prompt="$prompts['prediction'] ?? null" :template="$suggest['templates'][$target->id] ?? null" :dates="$suggest['dates'] ?? []"
        :attic="$suggest['attic'] ?? []" :attic-options="$atticOptions" :today="$today" :check-default="$checkDefault" />
      @break
    @case('wait')
      <x-case-step.wait-card :case="$case" :prediction="$step['prediction']" :kid="$kid" :number="$number" :total="$total" :note="$note"
        :calendar-connected="$calendarConnected" />
      @break
    @case('resolve')
      <x-case-step.resolve-card :case="$case" :prediction="$step['prediction']" :kid="$kid" :number="$number" :total="$total" :note="$note" />
      @break
    @case('close')
      <x-case-step.close-card :case="$case" :kid="$kid" :suggested="$step['key'] === 'close'" :note="$note" />
      @break
    @case('closed')
      <x-case-step.closed-card :case="$case" :kid="$kid" :note="$note" />
      @break
    @case('partner')
      {{-- 相棒の渡し合いの部品に、いまの番と2人の呼び名・子ども向けの言葉を渡す --}}
      <x-case-partner.flow :case="$case" :board="null" :partner-name="$partnerName" :turn="$turn" :self-name="$selfName" :kid="$kid" />
      @break
    @case('edit-case')
      <x-case-step.edit-case-card :case="$case" :kid="$kid" />
      @break
    @case('edit-clue')
      <x-case-step.edit-clue-card :case="$case" :clue="$editClue" :kid="$kid" />
      @break
    @case('edit-hyps')
      <x-case-step.edit-hyps-card :case="$case" :hypotheses="$hypotheses" :partner-name="$partnerName" :kid="$kid" />
      @break
  @endswitch
</section>
