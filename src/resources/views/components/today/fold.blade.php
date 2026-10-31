{{--
  今日の画面のいちばん下の、たたんだ枠1つ（これから・いつか・済み）。名前・件数・一言の説明を1行に出し、押すとその場で開く（JavaScript なしの <details>）。
  ?open= の値の枠だけ、最初から開いて出す（前のタスクの一覧のタブから来た人・作ったあとに戻ってきた人が、すぐ見られるように）。
  使い方：<x-today.fold name="upcoming" :label="…" :lead="…" :count="3" :open="true"> 中身 </x-today.fold>
  name：枠の名前（id="later-upcoming" になる）。label：名前。lead：何が入る枠かの一言。count：件数。open：最初から開くか
--}}
@props(['name', 'label', 'lead', 'count' => 0, 'open' => false])
<details id="later-{{ $name }}" {{ $attributes->class(['group/fold']) }} @if ($open) open @endif>
  <summary class="flex min-h-16 cursor-pointer items-center gap-3 px-4 py-2 hover:bg-mark/50">
    <span class="min-w-0 flex-1">
      <span class="flex items-center gap-2 text-base font-bold">
        {{ $label }}
        <span @class(['inline-grid min-w-7 place-items-center rounded-full px-2 text-sm leading-6 tabular-nums', 'bg-ink text-on-ink' => $count > 0, 'bg-mark text-muted' => ! ($count > 0)])>{{ __(config('suiri.today.later.count'), ['count' => $count]) }}</span>
      </span>
      <span class="block text-sm text-muted">{{ $lead }}</span>
    </span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-6 shrink-0 text-muted group-open/fold:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
  </summary>
  <div class="border-t border-rule">{{ $slot }}</div>
</details>
