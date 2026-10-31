{{--
  今日の画面の予定とやることの、Google カレンダーの予定の1行（読むだけ）。左にカレンダーの絵、右に「気になる」（その予定から事件を作る）。
  終わって少したった予定（just_ended）には、気になったことを書くよう一言そえ、「気になる」を目立たせる（提案 S-6）。
  使い方：<x-today.agenda-event :item="$item" />
  item：Queries/Today/AgendaQuery の並びの1件（type が event のもの：title・starts_at・ends_at・all_day・just_ended）
--}}
@props(['item'])
<div {{ $attributes->class(['flex min-h-16 items-center gap-1']) }}>
  <span class="grid size-12 shrink-0 place-items-center text-muted"><x-ui.icon name="calendar" class="size-6" /></span>
  <div class="min-w-0 flex-1 py-3">
    <p class="line-clamp-2 text-base leading-snug font-semibold break-words">{{ $item['title'] }}</p>
    <p class="mt-0.5 text-sm text-muted">{{ config('suiri.labels.words.agenda') }}{{ $item['ends_at'] ? '・〜'.$item['ends_at'] : '' }}</p>
    {{-- 終わって少したった予定なら、気になったことを書くよう一言そえる --}}
    @if ($item['just_ended'] ?? false)
      <p class="mt-1 text-sm font-semibold text-clue">{{ config('suiri.suggest.just_ended_label') }}</p>
    @endif
  </div>
  <x-task.curious origin="event" :label="$item['title'].($item['all_day'] ? '' : '（'.$item['starts_at'].'）')" :strong="$item['just_ended'] ?? false" />
</div>
