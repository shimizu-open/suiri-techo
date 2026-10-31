{{--
  見たこと1つが見立てに合うかの ○（合う）・×（合わない）の大きなボタン2つ（48px）。押すと印を付け（fits.update・入力は fit）、
  付いている印をもう一度押すと外す（fits.destroy）。いまの印は塗る（○ は緑、× は深い赤）。JavaScript なしの小さなフォーム2つ。
  使い方：<x-case-step.fit-buttons :hypothesis :clue :fit="Fit|null" back="fit-12" />
  back：送ったあと戻ってくるカード（?open= の値。隠し項目 back_open で送り、受付 RedirectsToCase が同じカードへ戻す）
--}}
@props(['hypothesis', 'clue', 'fit' => null, 'back' => null])
@php($w = config('suiri.case_board.cards.fit'))
<div class="flex shrink-0 gap-1.5">
  {{-- 合う・合わないを1つずつボタンにする。付いている印なら、押すと外す送り先にする --}}
  @foreach (\App\Enums\Fit::cases() as $option)
    @php($on = $fit === $option)
    <form method="POST" action="{{ route($on ? 'fits.destroy' : 'fits.update', [$hypothesis, $clue]) }}">
      @csrf
      @method($on ? 'DELETE' : 'PUT')
      <input type="hidden" name="fit" value="{{ $option->value }}">
      {{-- 戻るカードが決まっていれば、一緒に送る --}}
      @if ($back)
        <input type="hidden" name="back_open" value="{{ $back }}">
      @endif
      <button type="submit" aria-pressed="{{ $on ? 'true' : 'false' }}"
        aria-label="{{ $w[$option->value] }}{{ $on ? $w['undo'] : '' }}" title="{{ $w[$option->value] }}" @class([
          'grid size-12 place-items-center rounded-full border-2 text-xl font-bold',
          'border-ok bg-ok text-on-ink' => $on && $option === \App\Enums\Fit::Fits,
          'border-accent bg-accent text-on-ink' => $on && $option === \App\Enums\Fit::Contradicts,
          'border-rule bg-sheet text-muted hover:border-ink hover:text-ink' => ! $on,
        ])>{{ $option->symbol() }}</button>
    </form>
  @endforeach
</div>
