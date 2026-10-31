{{--
  やること（タスク）の1行。左の丸を1回押すと済み（<x-task.check>）。行の本文（題名）を押すと、その場で開いて（JavaScript なしの <details>）
  「直す」（直す画面へ）・「消す」（確かめてから消す）・「気になる」（そのやることから事件を作る）が出る。
  予想から作った確かめるタスク（もとの予想があるもの）は、左に点線の丸、まだなら右に「答え合わせ」のボタンを出す。
  開いた中には「直す」だけ（題名は直せない。答え合わせ待ちのものは消せないので、やめるときは直す画面で予想を取り下げる）。
  開いた中の操作の並びは左の丸の下まで広げる（スマホの狭い幅でも3つが1行に入るように）。開け閉めの印は名前つきの group/row で、
  外のたたんだ枠（<x-today.fold> の group/fold）が開いても回らないようにしてある。
  使い方：<x-task.item :task="$task" />（今日の画面の予定とやることでは :date="false" :time="false"）
  task：Task。date：日にちを出すか（今日のものは出さない。過ぎたものは赤で「◯/◯までだったよ」）。time：何時からを出すか
--}}
@props(['task', 'date' => true, 'time' => true])
@php($done = $task->isDone())
@php($byPrediction = $task->answeredByPrediction())
@php($words = config('suiri.tasks.words'))
@php($row = config('suiri.tasks.row'))
@php($due = $task->due_on ? \Carbon\Carbon::parse($task->due_on) : null)
@php($late = $due && ! $done && $due->lt(today()))
@php($showDate = $date && $due && ! $due->isToday())
@php($showTime = $time && $task->hasTime())
@php($showMinutes = $task->duration_minutes || $task->actual_minutes)
<div {{ $attributes->class(['flex items-start gap-1']) }}>
  <div class="shrink-0 pt-2"><x-task.check :task="$task" /></div>

  <details class="group/row min-w-0 flex-1">
    <summary class="flex min-h-16 cursor-pointer items-center gap-2 py-3">
      <span class="min-w-0 flex-1">
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
      </span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 shrink-0 text-muted group-open/row:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
    </summary>

    <div role="group" aria-label="{{ __($row['actions_label'], ['title' => $task->title]) }}" class="-ml-13 flex flex-wrap items-start gap-2 pb-3">
      <a href="{{ route('tasks.edit', $task) }}" class="inline-flex min-h-12 items-center rounded-full border-2 border-ink/60 px-4 text-base font-semibold text-ink no-underline hover:border-ink">{{ $row['edit'] }}</a>
      {{-- ふつうのやることなら「気になる」も出す（確かめるタスクは、もとの事件があるので出さない） --}}
      @unless ($byPrediction)
        <x-task.curious origin="task" :label="$task->title" pill />
      @endunless
      {{-- 答え合わせ待ちの予想を確かめるタスクは消せないので、「消す」を出さない（やめるときは直す画面で予想を取り下げる） --}}
      @unless ($task->checksPendingPrediction())
        <x-ui.confirm :action="route('tasks.destroy', $task)" :label="$row['delete']" :confirm-label="$words['delete_confirm']"
          :message="$words['delete_message']" class="ml-auto open:basis-full" />
      @endunless
    </div>
  </details>

  {{-- まだ済んでいない確かめるタスクは、予想の画面の「答え合わせ」へ進むボタンを右に出す --}}
  @if ($byPrediction && ! $done && Route::has('predictions.show'))
    <a href="{{ route('predictions.show', $task->prediction_id) }}" class="mt-2 inline-flex min-h-12 shrink-0 items-center rounded-full border-2 border-answer px-3 text-sm font-bold text-answer no-underline hover:bg-answer-soft">{{ $words['resolve'] }}</a>
  @endif
</div>
