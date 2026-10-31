{{--
  今日の画面：きょうの予定とやること（D-1・D-3・D-4）。やることは全部この1枚にまとめる：
  1. 見出しのすぐ下に、1行で書く欄（中身 slot。<x-task.quick-add>）。開け閉めなしでいつも出しておく（書くまでの手数を減らす）
  2. 期日を過ぎた、まだ済んでいないやること（前からのやり残し）。並びのいちばん上に、赤い一言と、左に赤いもとの期日を付けて出す
  3. Google カレンダーの予定と今日が期日のやることを1つの並びにし、左に時刻（終日・いつでもは上）
  4. いちばん下に、これから・いつか・済み のたたんだ枠（名前つきの slot later。<x-today.later-tasks>）
  行は大きく、やることは左の丸を1回押すだけで済ませられる。行の本文を押すと、その場で開いて直す・消す・気になる。
  使い方：<x-today.agenda :items="$items" :overdue="$overdue" :skip="$shown"> 1行で書く欄 <x-slot:later>…</x-slot:later> </x-today.agenda>
  items：Queries/Today/AgendaQuery の並び（1件：type・title・starts_at・ends_at・all_day・task・just_ended）。
  overdue：期日を過ぎたまだ済んでいない Task（古い順）。skip：いちばん上の答え合わせのカードに出ている予想の id
  （その確かめるタスクは、ここに二重に出さない）
--}}
@props(['items' => [], 'overdue' => [], 'skip' => []])
@php($words = config('suiri.today.agenda'))
@php($overdue = collect($overdue)->reject(fn ($task) => $task->prediction_id && in_array($task->prediction_id, $skip, true))->values())
<section aria-labelledby="agenda-title" {{ $attributes->class(['overflow-hidden rounded-card border border-rule bg-sheet']) }}>
  <div class="space-y-3 px-4 pt-4 pb-4">
    <h2 id="agenda-title" class="text-lg">{{ $words['title'] }}</h2>
    {{ $slot }}
  </div>

  {{-- 前からのやり残しも、きょうの予定もやることもなければ一言だけ。どれかあれば、やり残し → 時刻順の並びにする --}}
  @if ($overdue->isEmpty() && count($items) === 0)
    <div class="border-t border-rule px-4 py-6">
      <p class="font-semibold">{{ $words['empty'] }}</p>
      <p class="mt-1 text-sm text-muted">{{ $words['empty_hint'] }}</p>
    </div>
  @else
    <ul class="divide-y divide-rule border-t border-rule">
      {{-- 前からのやり残しがあれば、赤い一言のあとに、左にもとの期日を赤で付けて並べる --}}
      @if ($overdue->isNotEmpty())
        <li class="flex items-center gap-2 bg-answer-soft px-4 py-2 text-sm font-bold text-answer">
          <x-ui.step-icon name="calendar" class="size-5" />{{ __($words['overdue'], ['count' => $overdue->count()]) }}
        </li>
        {{-- やり残しを古い順に1件ずつ取り出し、左に赤いもとの期日、右にやることの行を出す --}}
        @foreach ($overdue as $task)
          <li class="flex items-start gap-1 pr-2 pl-3">
            <span class="flex h-16 w-14 shrink-0 items-center text-xs font-bold whitespace-nowrap tabular-nums text-answer">{{ \Carbon\Carbon::parse($task->due_on)->isoFormat($words['overdue_date']) }}</span>
            <x-task.item :task="$task" :date="false" class="min-w-0 flex-1" />
          </li>
        @endforeach
      @endif
      {{-- 予定とやることを1件ずつ取り出し、左に時刻、右に中身を出す --}}
      @foreach ($items as $item)
        <li class="flex items-start gap-1 pr-2 pl-3">
          <span class="flex h-16 w-14 shrink-0 items-center text-xs font-bold whitespace-nowrap tabular-nums text-muted">{{ $item['all_day'] ? ($item['type'] === 'task' ? $words['anytime'] : $words['all_day']) : $item['starts_at'] }}</span>
          {{-- やることならタスクの行（済ませる丸と、開くと直す・消す・気になる）、予定なら予定の行（読むだけ） --}}
          @if ($item['type'] === 'task')
            <x-task.item :task="$item['task']" :date="false" :time="false" class="min-w-0 flex-1" />
          @else
            <x-today.agenda-event :item="$item" class="min-w-0 flex-1" />
          @endif
        </li>
      @endforeach
    </ul>
  @endif

  {{-- これから・いつか・済み のたたんだ枠が渡されていれば、いちばん下に出す --}}
  @isset($later)
    <div class="border-t border-rule">{{ $later }}</div>
  @endisset
</section>
