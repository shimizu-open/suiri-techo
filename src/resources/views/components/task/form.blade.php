{{--
  やることを書く・直す画面の入力のまとまり。まず「何をする？」（題名）だけを大きく出し、
  いつ・何時から・何分かかりそう？・メモは「くわしく決める」を開いたときに出す（JavaScript なしの <details>）。
  「何分かかりそう？」のすぐ下に、題名の似た前のやることの実績を一言で出す（T-8）。済んだものを直すときは「実際は何分だった？」も出す。
  予想から作った確かめるタスクは、題名（予想の文の写し）を直せない。答え合わせ待ちの予想のものは、期日が必須で、予想の確かめる日も一緒に変わる。
  使い方：<x-task.form :task="$task" :similar="$similar" :action="route('tasks.update', $task)" method="PATCH" :submit="…" />
  task：Task（新しく作るときは保存前の Task）。similar：似たタスク（SimilarTasksQuery）。action・method：送り先。
  submit：送るボタンの文字。back：'today' なら作ったあと今日の画面へ戻す。open：「くわしく決める」を最初から開くか
--}}
@props(['task', 'similar' => [], 'action', 'method' => 'POST', 'submit', 'back' => null, 'open' => false])
@php($words = config('suiri.tasks.words'))
@php($checksPrediction = $task->checksPendingPrediction())
@php($open = $open || $errors->hasAny(['due_on', 'starts_at', 'duration_minutes', 'actual_minutes', 'memo']))
@php($due = $task->due_on ? \Carbon\Carbon::parse($task->due_on) : null)
<form method="POST" action="{{ $action }}" {{ $attributes->class(['space-y-5']) }}>
  @csrf
  {{-- 直すときは PATCH で送る --}}
  @if ($method !== 'POST')
    @method($method)
  @endif
  {{-- 戻り先が決まっていれば、一緒に送る --}}
  @if ($back)
    <input type="hidden" name="back" value="{{ $back }}">
  @endif

  {{-- 予想から作った確かめるタスクなら、題名は文字で見せるだけ（予想の文の写しなので直せない）。ほかは大きな入力欄 --}}
  @if ($task->answeredByPrediction())
    <div class="space-y-1">
      <p class="text-base font-semibold text-muted">{{ $words['title'] }}</p>
      <p class="text-lg leading-snug font-bold break-words">{{ $task->title }}</p>
      <p class="text-sm text-muted">{{ $words['title_fixed'] }}</p>
    </div>
  @else
    <div class="space-y-2">
      <label for="f-title" class="block text-xl font-bold">{{ $words['title'] }}</label>
      <input id="f-title" name="title" value="{{ old('title', $task->title) }}" required maxlength="{{ config('suiri.limits.task_title') }}"
        placeholder="{{ $words['title_example'] }}" autocomplete="off" @error('title') aria-invalid="true" @enderror
        @class([
          'block min-h-14 w-full rounded-xl border-2 bg-sheet px-4 text-lg text-ink placeholder:text-muted focus:border-ink focus:outline-none',
          'border-accent' => $errors->has('title'),
          'border-rule' => ! $errors->has('title'),
        ])>
      @error('title')
        <p class="text-sm font-semibold text-accent">{{ $message }}</p>
      @enderror
    </div>
  @endif

  <details class="group rounded-xl border border-rule bg-sheet" @if ($open) open @endif>
    <summary class="flex min-h-14 cursor-pointer items-center gap-3 px-4 py-2">
      <span class="min-w-0 flex-1">
        <span class="block font-semibold">{{ $words['details'] }}</span>
        <span class="flex flex-wrap gap-x-3 text-sm text-muted">
          {{-- 期日・時刻・予想時間のどれかが決まっていれば、それを並べる。何もなければ、何を決められるかを出す --}}
          @if ($due || $task->hasTime() || $task->duration_minutes)
            {{-- 期日があれば出す --}}
            @if ($due)
              <span>{{ $due->isoFormat(config('suiri.today.date_format')) }}</span>
            @endif
            {{-- 時刻があれば出す --}}
            @if ($task->hasTime())
              <span class="tabular-nums">{{ $task->starts_at }}</span>
            @endif
            {{-- 予想時間があれば出す --}}
            @if ($task->duration_minutes)
              <span><x-task.minutes :value="$task->duration_minutes" />{{ $words['plan'] }}</span>
            @endif
          @else
            <span>{{ $words['details_hint'] }}</span>
          @endif
        </span>
      </span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-6 shrink-0 text-muted group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
    </summary>

    <div class="space-y-5 border-t border-rule px-4 pt-4 pb-5">
      <div class="grid grid-cols-2 gap-3">
        <x-ui.field type="date" name="due_on" :label="$words['due_on']" :value="$task->due_on" :required="$checksPrediction"
          :hint="$checksPrediction ? __($words['due_on_verify_hint'], ['check_on' => \App\Support\Label::word('check_on')]) : $words['due_on_hint']" />
        <x-ui.field type="time" name="starts_at" :label="$words['starts_at']" :value="$task->starts_at" :hint="$words['starts_at_hint']" />
      </div>

      <div class="space-y-3">
        <x-task.minutes-field name="duration_minutes" :label="$words['duration']" :value="$task->duration_minutes" />
        <x-task.similar-history :tasks="$similar" />
      </div>

      {{-- 済んだやることなら、実際にかかった時間も直せる --}}
      @if ($task->isDone())
        <x-task.minutes-field name="actual_minutes" :label="$words['actual']" :value="$task->actual_minutes" />
      @endif

      <x-ui.textarea name="memo" :label="$words['memo']" :value="$task->memo" rows="2" :maxlength="config('suiri.limits.task_memo')" />
    </div>
  </details>

  <x-ui.privacy-note />
  <x-ui.big-button icon="check">{{ $submit }}</x-ui.big-button>
</form>
