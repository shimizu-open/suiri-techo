{{--
  いまやることのカード「この見立てに合う？」（○× を付ける段。任意で、とばせる）。
  上に見立てを引用し、見たことの荷札ごとに ○（合う）・×（合わない）の大きなボタン。× の付いた見立てには「ちがった にする」を勧める。
  主なボタンは「次へ：もしそうならを考える」（?open=predict-{id}）。見立ての横の「とばす」も同じ所へ進む（○× は任意）。
  使い方：<x-case-step.fit-card :case :hypothesis :kid :number :total :note />（hypothesis は fits を、case は clues を読み込んだもの）
  カードの id は hyp-{id}（○× を押したあと、受付がこの場所へ戻す）
--}}
@props(['case', 'hypothesis', 'kid' => false, 'number' => null, 'total' => null, 'note' => null])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
@php($h = $hypothesis)
@php($work = $case->scene === \App\Enums\Scene::Work)
@php($contradicted = $h->fits->contains(fn ($f) => $f->fit === \App\Enums\Fit::Contradicts))
<x-ui.step-card id="hyp-{{ $h->id }}" class="scroll-mt-16" :step="$number" :total="$total" icon="question" tone="hypothesis"
  :title="$t('fit.title')" :hint="$t('fit.hint')">
  <div class="flex items-start gap-3">
    <blockquote class="min-w-0 flex-1 border-l-2 border-hypothesis pl-3">
      <p class="text-sm font-bold text-hypothesis">{{ $work ? \App\Support\Label::word('hypothesis', $case->scene) : ($kid ? \App\Support\Label::kidWord('hypothesis') : $t('fit.quote')) }}</p>
      <p class="leading-relaxed font-semibold break-words">{{ $h->body }}</p>
    </blockquote>
    <a href="{{ route('cases.show', ['case' => $case, 'open' => 'predict-'.$h->id]) }}" class="-mt-2 inline-flex min-h-12 shrink-0 items-center gap-0.5 px-1 text-sm font-semibold text-muted">
      {{ $t('fit.skip') }}<span aria-hidden="true">›</span>
    </a>
  </div>
  <ul class="space-y-2">
    {{-- 見たことを1つずつ荷札にし、右に ○× のボタンを置く --}}
    @foreach ($case->clues as $clue)
      <li>
        <x-ui.evidence-tag :no="$loop->iteration" :label="$work ? $clue->kind->label($case->scene) : ($kid ? $clue->kind->kidLabel() : $clue->kind->label())">
          <span class="break-words">{{ $clue->body }}</span>
          <x-slot:actions>
            <x-case-step.fit-buttons :hypothesis="$h" :clue="$clue" :fit="$h->fits->firstWhere('clue_id', $clue->id)?->fit" :back="'fit-'.$h->id" />
          </x-slot:actions>
        </x-ui.evidence-tag>
      </li>
    @endforeach
  </ul>
  {{-- × の付いた見立てなら、「ちがった」にすることを勧める（消さずに記録が残る） --}}
  @if ($contradicted)
    <form method="POST" action="{{ route('hypotheses.update', $h) }}" class="flex flex-wrap items-center gap-3 rounded-xl bg-accent-soft px-4 py-3">
      @csrf
      @method('PATCH')
      <span class="min-w-0 flex-1">{{ $t('fit.eliminate') }}</span>
      <x-ui.button variant="secondary" name="status" value="eliminated">{{ $t('fit.eliminate_button') }}</x-ui.button>
    </form>
  @endif
  <x-ui.big-button :href="route('cases.show', ['case' => $case, 'open' => 'predict-'.$h->id])" tone="hypothesis" icon="arrow">{{ $t('fit.button') }}</x-ui.big-button>
  <x-case-step.bubble :note="$note" :case="$case" :explain="$t('fit.explain')" />
  <x-slot:later>{{ $t('fit.later') }}</x-slot:later>
</x-ui.step-card>
