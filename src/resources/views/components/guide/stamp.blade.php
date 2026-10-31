{{--
  カードに押す小さなはんこ（「推理を書いた」「ホームズが外した」）。丸い枠と少しの傾きで、札とは違う「押した印」に見せる。
  使い方：<x-guide.stamp tone="ok">推理を書いた</x-guide.stamp>
  tone：ok（緑：自分の推理を書いた）・accent（深い赤：ホームズが外した）
--}}
@props(['tone' => 'ok'])
<span {{ $attributes->class([
  'inline-flex shrink-0 items-center rounded-full border-2 px-2.5 py-0.5 text-xs font-bold whitespace-nowrap',
  'border-ok text-ok -rotate-3' => $tone === 'ok',
  'border-accent text-accent rotate-3' => $tone === 'accent',
]) }}>{{ $slot }}</span>
