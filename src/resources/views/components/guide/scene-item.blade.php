{{--
  名推理カードの一覧の1行（番号・題・作品名と章、ホームズが外した場面と自分の推理を書いたカードには、はんこ）。行のどこを押してもカードの表へ進む。
  使い方：<x-guide.scene-item :scene="$scene" :number="7" :done="in_array($scene['key'], $doneKeys)" />
  scene：scenes.json の1件（表の欄だけでよい）。number：並びの何枚目か（省略可）。done：自分の推理を書いたか
--}}
@props(['scene', 'number' => null, 'done' => false])
<li {{ $attributes }}>
  <a href="{{ route('guide.scenes.show', $scene['key']) }}" class="flex min-h-16 items-start gap-3 py-3 no-underline hover:bg-mark">
    {{-- 番号があれば、カードの左に小さく出す --}}
    @if ($number)
      <span class="w-11 shrink-0 pt-1 text-sm font-bold tabular-nums text-muted">{{ sprintf('No.%02d', $number) }}</span>
    @endif
    <span class="min-w-0 flex-1">
      <span class="block text-lg font-bold leading-snug">{{ $scene['title'] }}</span>
      <x-guide.work :scene="$scene" class="block text-sm text-muted" />
    </span>
    <span class="flex shrink-0 flex-col items-end gap-1.5 pt-1">
      {{-- ホームズが外した場面なら、赤いはんこで知らせる --}}
      @if ($scene['is_mistake'])
        <x-guide.stamp tone="accent">{{ config('suiri.guide.scenes.mistake') }}</x-guide.stamp>
      @endif
      {{-- 自分の推理を書いたカードなら、緑のはんこを押す --}}
      @if ($done)
        <x-guide.stamp tone="ok">{{ config('suiri.guide.scenes.done') }}</x-guide.stamp>
      @endif
    </span>
  </a>
</li>
