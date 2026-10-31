{{--
  「見立てを見直そう」のカードの中の見立て1つ：本文（ちがった見立ては線で消す）・書いた人（相棒モード）・状態を選ぶ大きなボタン
  （hypotheses.update・入力は status）・○× をつける（?open=fit-{id}）・もしそうならを立てる（?open=predict-{id}）・本文を直す／消す。
  使い方：<x-case-step.hyp-row :hypothesis :case :partner-name :kid />（hypothesis は fits を、case は clues を読み込んだもの）
  送ったあとは、受付が隠し項目 back_open を読んで、このカード（?open=edit-hyps）の hyp-{id} の場所へ戻す（消したときもこのカードへ）
--}}
@props(['hypothesis', 'case', 'partnerName' => null, 'kid' => false])
@php($h = $hypothesis)
@php($w = config('suiri.case_board.cards.edit_hyps'))
@php($key = 'hyp-edit-'.$h->id)
<li id="hyp-{{ $h->id }}" class="scroll-mt-16 space-y-3 border-t border-rule pt-4 first:border-t-0 first:pt-0">
  {{-- 相棒モードの事件なら、だれの見立てかを上に小さく出す --}}
  @if ($case->partner_mode)
    <p class="text-sm font-semibold text-hypothesis">{{ $h->isByPartner() ? __($w['by_partner'], ['who' => $partnerName]) : $w['by_self'] }}</p>
  @endif
  <p @class(['text-lg leading-snug font-semibold break-words whitespace-pre-line', 'text-muted line-through' => $h->isEliminated()])>{{ $h->body }}</p>
  <form method="POST" action="{{ route('hypotheses.update', $h) }}" class="flex flex-wrap gap-1.5" aria-label="{{ $h->body }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="back_open" value="edit-hyps">
    {{-- 状態を1つずつボタンにする。いまの状態は塗って、押されている印を付ける --}}
    @foreach (\App\Enums\HypothesisStatus::cases() as $status)
      <button type="submit" name="status" value="{{ $status->value }}" aria-pressed="{{ $h->status === $status ? 'true' : 'false' }}" @class([
        'inline-flex min-h-12 cursor-pointer items-center rounded-xl border-2 px-3 text-base font-semibold',
        'border-hypothesis bg-hypothesis text-on-ink' => $h->status === $status,
        'border-rule bg-sheet text-muted hover:border-hypothesis hover:text-ink' => $h->status !== $status,
      ])>{{ $kid ? $status->kidLabel() : $status->label() }}</button>
    @endforeach
  </form>
  <div class="flex flex-wrap items-center gap-x-4">
    {{-- 消えていない見立てなら、○× と「もしそうなら」の入口を出す --}}
    @if (! $h->isEliminated())
      {{-- ○× の入口は、見たことがあるときだけ出す --}}
      @if ($case->clues->isNotEmpty())
        <a href="{{ route('cases.show', ['case' => $case, 'open' => 'fit-'.$h->id]) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $w['fit'] }}</a>
      @endif
      <a href="{{ route('cases.show', ['case' => $case, 'open' => 'predict-'.$h->id]) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $w['predict'] }}</a>
    @endif
    <details class="w-full" @if (old('form_key') === $key) open @endif>
      <summary class="inline-flex min-h-12 cursor-pointer items-center font-semibold text-muted">{{ config('suiri.case_board.done.edit') }}</summary>
      <form method="POST" action="{{ route('hypotheses.update', $h) }}" class="mt-2 space-y-3">
        @csrf
        @method('PATCH')
        <input type="hidden" name="form_key" value="{{ $key }}">
        <input type="hidden" name="back_open" value="edit-hyps">
        <x-case.textarea name="body" :label="config('suiri.case_board.cards.hypothesis.label')" :value="$h->body" :form-key="$key" rows="2" required
          :maxlength="config('suiri.limits.hypothesis_body')" />
        <x-ui.button variant="secondary">{{ config('suiri.case_board.cards.edit_case.button') }}</x-ui.button>
      </form>
      <x-ui.confirm :action="route('hypotheses.destroy', $h)" class="mt-2" :message="$w['delete_message']">
        <input type="hidden" name="back_open" value="edit-hyps">
      </x-ui.confirm>
    </details>
  </div>
</li>
