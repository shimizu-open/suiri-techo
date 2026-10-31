{{--
  やること（タスク）の1行。大きな行で、左の丸を1回押すと済み、題名を押すと直す画面へ。
  使い方：<x-task.item :task="$task" />（今日の画面の予定とやることでは :date="false" :time="false" curious）
  task：Task。date：日にちを出すか（今日のものは出さない。過ぎたものは赤で「◯/◯までだったよ」）。time：何時からを出すか。
  curious：済んだやることの右に「気になる」（そのやることから事件を作る）を出すか。まだのやることには出さない
  （行の操作は丸の1つだけにする。気になることが出てくるのは、やってみたあと）。
  確かめるタスク（もとの予想があるもの）は、左に点線の丸（<x-task.check>）、右に「答え合わせ」のボタンを出す
--}}
@props(['task', 'date' => true, 'time' => true, 'curious' => false])
@php($done = $task->isDone())
@php($byPrediction = $task->answeredByPrediction())
@php($words = config('suiri.tasks.words'))
@php($due = $task->due_on ? \Carbon\Carbon::parse($task->due_on) : null)
@php($late = $due && ! $done && $due->lt(today()))
@php($showDate = $date && $due && ! $due->isToday())
@php($showTime = $time && $task->hasTime())
@php($showMinutes = $task->duration_minutes || $task->actual_minutes)
<div {{ $attributes->class(['flex min-h-16 items-center gap-1']) }}>
  <x-task.check :task="$task" />

  <a href="{{ route('tasks.edit', $task) }}" class="min-w-0 flex-1 py-3 no-underline">
    <span @class(['block line-clamp-2 text-base leading-snug font-semibold break-words', 'text-muted line-through' => $done])>{{ $task->title }}</span>
    {{-- 日にち・時刻・何分のどれかがあれば、題名の下に小さく1行で出す --}}
    @if ($showDate || $showTime || $showMinutes)
      <span class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-muted">
        {{-- 日にちを出すとき：過ぎていれば赤で「◯/◯までだったよ」、明日なら「明日」、ほかは月日と曜日 --}}
        @if ($showDate)
          <span @class(['font-semibold text-answer' => $late])>{{ $late ? __($words['late'], ['date' => $due->isoFormat($words['date_format'])]) : ($due->isTomorrow() ? $words['tomorrow'] : $due->isoFormat($words['date_format'])) }}</span>
        @endif
        {{-- 何時からを出すとき、時刻があれば出す --}}
        @if ($showTime)
          <span class="tabular-nums">{{ $task->starts_at }}</span>
        @endif
        {{-- 予想時間か、かかった時間があれば出す（両方あれば「30分の予定 → 45分かかった」） --}}
        @if ($showMinutes)
          <span>
            {{-- 予想時間があれば先に出す --}}
            @if ($task->duration_minutes)
              <x-task.minutes :value="$task->duration_minutes" />{{ $words['plan'] }}
            @endif
            {{-- かかった時間があれば、矢印のあとに出す --}}
            @if ($task->actual_minutes)
              {{ $task->duration_minutes ? '→' : '' }} <x-task.minutes :value="$task->actual_minutes" />{{ $words['took'] }}
            @endif
          </span>
        @endif
      </span>
    @endif
  </a>

  {{-- まだ済んでいない確かめるタスクは、予想の画面の「答え合わせ」へ。ふつうのやることは、頼まれていて済んでいれば「気になる」を出す --}}
  @if ($byPrediction && ! $done && Route::has('predictions.show'))
    <a href="{{ route('predictions.show', $task->prediction_id) }}" class="inline-flex min-h-11 shrink-0 items-center rounded-full border-2 border-answer px-3 text-sm font-bold text-answer no-underline hover:bg-answer-soft">{{ $words['resolve'] }}</a>
  @elseif ($curious && $done && ! $byPrediction)
    <x-task.curious origin="task" :label="$task->title" />
  @endif
</div>
