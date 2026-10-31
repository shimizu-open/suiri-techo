{{--
  ふりかえりの大きな数字の1つ（3つ並べた数の札の中の1マス）。上に名前、下に大きな数と単位。値が null（数えようがない）なら「—」と出す。
  責める言葉は出さない。使い方：<x-review.stat label="書いた事件" :value="3" unit="件" note="…" />。note：数字の下の短い補足（省略可）
--}}
@props(['label', 'value' => null, 'unit' => '', 'note' => null])
<div {{ $attributes->class(['px-3 py-3']) }}>
  <p class="text-xs leading-tight font-bold text-muted">{{ $label }}</p>
  <p class="mt-1.5 leading-none">
    {{-- 値があれば大きく、なければ「—」 --}}
    @if ($value !== null)
      <span class="text-2xl font-bold tabular-nums">{{ $value }}</span><span class="ml-0.5 text-sm text-muted">{{ $unit }}</span>
    @else
      <span class="text-2xl font-bold text-muted">—</span>
    @endif
  </p>
  {{-- 補足があれば小さく出す --}}
  @if ($note)
    <p class="mt-1 text-xs text-muted">{{ $note }}</p>
  @endif
</div>
