{{--
  週のふりかえりの上の帯（1行）：前の週へ・いまの週の期間（今週なら「今週」の札）・次の週へ。今週より先へは進めない。
  使い方：<x-review.week-nav :week="$week" :range="$range" />。week：App\Services\Review\ReviewWeek。range：確かさの期間（引き継ぐ）
--}}
@props(['week', 'range' => 'all'])
@php($keep = $range === 'week' ? ['range' => 'week'] : [])
<div {{ $attributes->class(['mb-3 grid grid-cols-[auto_1fr_auto] items-center gap-1']) }}>
  <x-ui.button variant="ghost" class="justify-self-start px-2" :href="route('review.show', ['week' => $week->previous()->param()] + $keep)">‹ 前の週</x-ui.button>
  <p class="flex flex-wrap items-center justify-center gap-x-2 text-center">
    <span class="text-base font-bold">{{ $week->label() }}</span>
    {{-- 今週なら札を付ける --}}
    @if ($week->isCurrent())
      <x-ui.badge tone="ink">今週</x-ui.badge>
    @endif
  </p>
  {{-- 次の週があれば（今週でなければ）進むリンク、なければ何も置かない（期間はまん中のまま） --}}
  @if ($week->next())
    <x-ui.button variant="ghost" class="justify-self-end px-2" :href="route('review.show', ['week' => $week->next()->param()] + $keep)">次の週 ›</x-ui.button>
  @endif
</div>
