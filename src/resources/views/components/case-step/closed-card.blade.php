{{--
  閉じた事件の画面の中心：「解決！」か「おあずけ」の大きなはんこと、閉じた日・結論。その下に、屋根裏の棚へ1問ずつ聞くカード
  「つまり、何がわかった？」（送り先は attic.store・入力は rule・examples・case_file_id）。書き始めると次の問い
  「ほかに当てはまりそうなことは？」が出る（CSS の :has。使えない端末では2問とも並ぶ）。
  この事件からもう棚にしまったこと（case の atticNotes を読み込んであれば）があれば、聞かずにそれを見せる。
  まだ答え合わせしていない予想があれば、その入口も出す（閉じたあとでも結果は書ける）。
  使い方：<x-case-step.closed-card :case :kid :note />
--}}
@props(['case', 'kid' => false, 'note' => null])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
@php($solved = $case->status === \App\Enums\CaseStatus::Solved)
@php($word = $t($solved ? 'closed.solved' : 'closed.cold'))
@php($shelved = $case->relationLoaded('atticNotes') ? $case->atticNotes : collect())
@php($pending = $case->predictions->filter->isPending()->sortBy('check_on')->first())
<div class="space-y-6">
  <div class="flex items-center gap-5">
    <span role="img" aria-label="{{ $word }}" @class([
      'grid size-28 shrink-0 -rotate-6 place-items-center rounded-full border-[3px] p-[3px] font-bold',
      'border-answer text-answer' => $solved, 'border-dashed border-muted text-muted' => ! $solved,
    ])>
      <span @class(['grid size-full place-items-center rounded-full border text-center leading-tight', 'border-answer' => $solved, 'border-transparent' => ! $solved,
        'text-2xl' => mb_strlen($word) <= 3, 'text-xl' => mb_strlen($word) === 4, 'text-lg' => mb_strlen($word) > 4])>{{ $word }}</span>
    </span>
    <div class="min-w-0">
      <p class="text-sm text-muted">{{ $t('closed.date', ['date' => $case->closed_at?->isoFormat('M月D日')]) }}</p>
      {{-- 結論が書いてあれば、はんこの横に出す --}}
      @if (filled($case->conclusion))
        <p class="mt-1 text-lg leading-relaxed font-semibold break-words whitespace-pre-line">{{ $case->conclusion }}</p>
      @endif
    </div>
  </div>

  {{-- まだ答え合わせしていない予想があれば、答え合わせの入口を出す --}}
  @if ($pending)
    <p class="flex flex-wrap items-center gap-x-3 rounded-xl bg-answer-soft px-4 py-2">
      <span>{{ $t('closed.pending', ['date' => \Illuminate\Support\Carbon::parse($pending->check_on)->isoFormat('M月D日')]) }}</span>
      <a href="{{ route('predictions.show', $pending) }}" class="inline-flex min-h-12 items-center font-semibold text-answer">{{ $t('closed.pending_button') }}</a>
    </p>
  @endif

  {{-- この事件からもう棚にしまったことがあればそれを、なければ「つまり、何がわかった？」のカードを出す --}}
  @if ($shelved->isNotEmpty())
    <x-ui.done-summary icon="attic" :title="$t('closed.shelved')" :items="$shelved->pluck('rule')->all()" open />
  @else
    <x-ui.step-card icon="attic" :title="$t('closed.rule')" :hint="$t('closed.rule_hint')">
      <form method="POST" action="{{ route('attic.store') }}" class="group/attic space-y-4">
        @csrf
        <input type="hidden" name="case_file_id" value="{{ $case->id }}">
        <x-ui.textarea name="rule" id="f-step-rule" :label="\App\Support\Label::word('rule')" rows="2" required
          :maxlength="config('suiri.limits.attic_rule')" :placeholder="$t('closed.placeholder')" />
        {{-- 2問目：「つまり？」を書き始めるまで隠す --}}
        <div class="group-has-[textarea[name=rule]:placeholder-shown]/attic:hidden">
          <x-ui.textarea name="examples" id="f-step-examples" :label="$t('closed.examples')" rows="2" :maxlength="config('suiri.limits.attic_examples')"
            :placeholder="$t('closed.examples_placeholder')" />
        </div>
        <x-ui.big-button icon="attic">{{ $t('closed.button') }}</x-ui.big-button>
        <x-ui.privacy-note :scene="$case->scene" />
      </form>
      <x-case-step.bubble :note="$note" :case="$case" :explain="$t('closed.explain')" />
      <x-slot:later>{{ $t('closed.later') }}</x-slot:later>
    </x-ui.step-card>
  @endif
</div>
