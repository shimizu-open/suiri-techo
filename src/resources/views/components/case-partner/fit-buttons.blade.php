{{--
  荷札の右に置く大きな ○（合う）×（合わない）のボタン（子どもの指でも押しやすい直径 48px）。押すと印を付け（PUT fits.update）、
  付いている印をもう一度押すと外す（DELETE fits.destroy）。JavaScript なしで動く小さなフォーム2つ。いまの印は塗る（○ は緑・× は深い赤）。
  使い方：<x-case-partner.fit-buttons :hypothesis="$hypothesis" :clue="$clue" />（hypothesis は fits を読み込んだもの）
--}}
@props(['hypothesis', 'clue'])
@php($fit = $hypothesis->fits->firstWhere('clue_id', $clue->id)?->fit)
<div class="flex shrink-0 gap-1.5">
  {{-- 合う・合わないを1つずつボタンにする。付いている印なら、押すと外す送り先にする --}}
  @foreach (\App\Enums\Fit::cases() as $option)
    @php($on = $fit === $option)
    <form method="POST" action="{{ route($on ? 'fits.destroy' : 'fits.update', [$hypothesis, $clue]) }}">
      @csrf
      @method($on ? 'DELETE' : 'PUT')
      <input type="hidden" name="fit" value="{{ $option->value }}">
      <button type="submit" aria-pressed="{{ $on ? 'true' : 'false' }}" title="{{ $option->label() }}"
        aria-label="{{ $option->label() }}{{ $on ? config('suiri.case_partner.mark.fit_on') : '' }}" @class([
          'grid size-12 cursor-pointer place-items-center rounded-full border-2 text-xl font-bold',
          'border-ok bg-ok text-on-ink' => $on && $option === \App\Enums\Fit::Fits,
          'border-accent bg-accent text-on-ink' => $on && $option === \App\Enums\Fit::Contradicts,
          'border-rule bg-sheet text-muted hover:border-ink hover:text-ink' => ! $on,
        ])>{{ $option->symbol() }}</button>
    </form>
  @endforeach
</div>
