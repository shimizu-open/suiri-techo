{{--
  答え合わせの「確かめた」の丸いはんこ。結果ごとに色と線の形を変え、文字を読まなくてもひと目でわかるようにする。
  使い方：<x-ui.stamp result="hit" />／<x-ui.stamp :result="$prediction->result" :label="$prediction->result->label($case->scene)" size="sm" />
  result：hit（当たり・赤い二重の輪）・miss（外れ・紺）・unclear（どちらとも言えない・からし色）・void（前提が崩れた・灰色の点線）。列挙のままでもよい。
  label：はんこの中の言葉（省略すると config('suiri.labels.prediction_result') の言葉。子ども向けの言葉は画面から渡す）。
  size：md（直径 112px・既定。上に「確かめた」）・sm（直径 72px・言葉だけ。一覧の行に）
--}}
@props(['result', 'label' => null, 'size' => 'md'])
@php($r = $result instanceof \BackedEnum ? $result->value : (string) $result)
{{-- 結果ごとの色と線の形（outer：外の輪／inner：内の輪） --}}
@php($looks = [
  'hit' => ['outer' => 'border-answer text-answer', 'inner' => 'border-answer'],
  'miss' => ['outer' => 'border-clue text-clue', 'inner' => 'border-clue'],
  'unclear' => ['outer' => 'border-hypothesis text-hypothesis', 'inner' => 'border-hypothesis border-dashed'],
  'void' => ['outer' => 'border-muted border-dashed text-muted', 'inner' => 'border-transparent'],
])
@php($look = $looks[$r] ?? $looks['void'])
@php($word = $label ?? config('suiri.labels.prediction_result.'.$r, $r))
<span role="img" aria-label="{{ config('suiri.ui.stamp_top') }}：{{ $word }}" {{ $attributes->class([
  'inline-grid shrink-0 -rotate-6 place-items-center rounded-full border-[3px] p-[3px] font-bold',
  'size-28' => $size !== 'sm',
  'size-[4.5rem]' => $size === 'sm',
  $look['outer'],
]) }}>
  <span class="grid size-full min-w-0 place-items-center rounded-full border px-1 text-center leading-tight {{ $look['inner'] }}">
    <span>
      {{-- ふつうの大きさのときだけ、上に「確かめた」を小さく出す --}}
      @if ($size !== 'sm')
        <span class="block text-xs tracking-widest">{{ config('suiri.ui.stamp_top') }}</span>
      @endif
      <span @class([
        'block [word-break:auto-phrase]',
        'text-lg' => $size !== 'sm' && mb_strlen($word) <= 4,
        'text-sm' => $size !== 'sm' && mb_strlen($word) > 4,
        'text-xs' => $size === 'sm' && mb_strlen($word) <= 3,
        'text-[0.6875rem]' => $size === 'sm' && mb_strlen($word) > 3,
      ])>{{ $word }}</span>
    </span>
  </span>
</span>
