{{--
  小さな札（状態や種類のしるし）。使い方：<x-ui.badge tone="accent">外れ</x-ui.badge>
  tone：plain（枠だけ・既定）・ink（淡い紺）・accent（深い赤：注意・外れ）・ok（緑：当たり・済み）
--}}
@props(['tone' => 'plain'])
<span {{ $attributes->class([
  'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold whitespace-nowrap',
  'border border-rule text-muted' => $tone === 'plain',
  'bg-mark text-ink' => $tone === 'ink',
  'bg-accent-soft text-accent' => $tone === 'accent',
  'bg-ok-soft text-ok' => $tone === 'ok',
]) }}>{{ $slot }}</span>
