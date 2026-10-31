{{--
  名推理カードの紙の形（本物のカードのように、外の太い枠と内の細い枠の二重の枠、左上にカードの番号、右上に「表」「裏」）。
  表（手がかり）・裏（ホームズの推理）・入口の小さな見本のどれも、この形にして「これはカード」とひと目でわかるようにする。
  使い方：<x-guide.card-face :number="7" :total="23" side="front"> 中身 </x-guide.card-face>
  number：並びの何枚目か（No.07 と出す。省略可）。total：全部で何枚か（No.07 の後ろに小さく「/ 23」。省略可）。side：front（表・紙の色）・back（裏・淡い紺）。
  corner（slot）：右上の「表」「裏」の代わりに置くもの（はんこなど。省略可）。中身（slot）：題・手がかり・推理など
--}}
@props(['number' => null, 'total' => null, 'side' => 'front'])
<div {{ $attributes->class(['rounded-2xl border-2 border-ink bg-sheet p-1.5']) }}>
  <div @class([
    'rounded-xl border px-4 pt-3 pb-4',
    'border-ink/25 bg-sheet' => $side === 'front',
    'border-clue/30 bg-clue-soft' => $side === 'back',
  ])>
    <div class="mb-1 flex items-center justify-between gap-2 text-sm font-bold tabular-nums text-muted">
      <span>{{ $number ? sprintf('No.%02d', $number) : '' }}<span class="font-semibold">{{ $number && $total ? ' / '.$total : '' }}</span></span>
      {{-- 右上に置くもの（はんこなど）が渡されていればそれを、なければ「表」「裏」を出す --}}
      @isset($corner)
        {{ $corner }}
      @else
        <span>{{ $side === 'back' ? '裏' : '表' }}</span>
      @endisset
    </div>
    {{ $slot }}
  </div>
</div>
