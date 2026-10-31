{{--
  今日の画面の「きょうの予定とやること」のいちばん下：これから・いつか・済み の3つのたたんだ枠（<x-today.fold>）。
  前のタスクの一覧のタブの代わり。ふだんは名前と件数だけを見せ、押すとその場で開く。?open= の値の枠だけ最初から開く。
  - これから：明日から先のまだ済んでいないやることを、日付ごとの小さな見出しの下に並べる（明日の日には「明日」を付ける）
  - いつか：期日のないやること（作った順）
  - 済み：この数日（config('suiri.today_tasks.done_days')）に済んだやること（新しい順。丸を押すと、まだに戻せる）
  使い方：<x-today.later-tasks :upcoming="$upcoming" :someday="$someday" :done="$done" :counts="$counts" :open="$open" />
  upcoming：['Y-m-d' => [Task, …], …]（日付ごとにまとめたもの）。someday・done：Task の並び。
  counts：['upcoming' => int, 'someday' => int, 'done' => int]（なければ渡された並びの数）。open：最初から開く枠の名前（なければ null）
--}}
@props(['upcoming' => [], 'someday' => [], 'done' => [], 'counts' => [], 'open' => null])
@php($words = config('suiri.today.later'))
@php($upcoming = collect($upcoming))
@php($someday = collect($someday))
@php($done = collect($done))
@php($counts = ['upcoming' => $counts['upcoming'] ?? $upcoming->flatten(1)->count(), 'someday' => $counts['someday'] ?? $someday->count(), 'done' => $counts['done'] ?? $done->count()])
<div {{ $attributes->class(['divide-y divide-rule']) }}>
  <x-today.fold name="upcoming" :label="$words['upcoming']['label']" :lead="$words['upcoming']['lead']" :count="$counts['upcoming']" :open="$open === 'upcoming'">
    {{-- これからのやることがあれば日付ごとに並べ、なければ一言だけ出す --}}
    @if ($upcoming->isNotEmpty())
      <ul class="divide-y divide-rule">
        {{-- 日付を1つずつ取り出し、小さな見出し（明日なら「明日」つき）のあとに、その日のやることを並べる --}}
        @foreach ($upcoming as $date => $tasks)
          @php($day = \Carbon\Carbon::parse($date))
          <li class="bg-paper px-4 py-1.5 text-sm font-bold text-muted">{{ $day->isTomorrow() ? $words['tomorrow'].'・' : '' }}{{ $day->isoFormat(config('suiri.today.date_format')) }}</li>
          {{-- その日のやることを1件ずつ取り出して1行にする（日にちは見出しにあるので出さない） --}}
          @foreach ($tasks as $task)
            <li class="pr-2 pl-3"><x-task.item :task="$task" :date="false" /></li>
          @endforeach
        @endforeach
      </ul>
    @else
      <p class="px-4 py-4 text-sm text-muted">{{ $words['upcoming']['empty'] }}</p>
    @endif
  </x-today.fold>

  <x-today.fold name="someday" :label="$words['someday']['label']" :lead="$words['someday']['lead']" :count="$counts['someday']" :open="$open === 'someday'">
    <x-today.task-rows :tasks="$someday" :empty="$words['someday']['empty']" />
  </x-today.fold>

  <x-today.fold name="done" :label="$words['done']['label']" :lead="__($words['done']['lead'], ['days' => config('suiri.today_tasks.done_days')])" :count="$counts['done']" :open="$open === 'done'">
    <x-today.task-rows :tasks="$done" :empty="$words['done']['empty']" />
  </x-today.fold>
</div>
