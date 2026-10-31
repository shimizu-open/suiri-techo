{{--
  やることを済ませた直後に出す枠（T-5・T-8）。どちらも任意で、何もしなくてよい。
  - 「実際は何分だった？」：よく使う分のボタンは1回押すだけで書ける（ボタンごとの小さなフォーム）。ほかの分は欄に書く
  - 「気になる」：そのやることを出発点にした事件を作る画面へ（今日の画面の行と同じ <x-task.curious>）
  使い方：<x-task.done-followup :task="$justDone" />（task が null なら何も出さない）
--}}
@props(['task' => null])
@php($words = config('suiri.tasks.done_followup'))
{{-- 済ませた直後のやることがあるときだけ出す --}}
@if ($task)
  <section role="status" {{ $attributes->class(['rounded-step border-2 border-ok bg-ok-soft px-4 py-4']) }}>
    <p class="flex items-center gap-2 text-lg leading-snug font-bold text-ok">
      <x-ui.step-icon name="check" class="size-7" />
      <span class="min-w-0 break-words">{{ __($words['title'], ['title' => $task->title]) }}</span>
    </p>

    {{-- かかった時間がまだなら聞く。書いてあれば予定とくらべて見せる --}}
    @if ($task->actual_minutes === null)
      <p class="mt-3 text-base font-semibold">
        {{-- 予想時間を書いていれば、くらべやすいよう先に添える --}}
        @if ($task->duration_minutes)
          <x-task.minutes :value="$task->duration_minutes" />{{ $words['plan'] }}
        @endif
        {{ $words['ask'] }}
      </p>
      <div class="mt-2 flex flex-wrap items-center gap-2">
        {{-- よく使う分を1つずつ取り出し、押すとすぐ書けるボタンにする --}}
        @foreach (config('suiri.tasks.minute_choices') as $minutes)
          <form method="POST" action="{{ route('tasks.done', $task) }}">
            @csrf
            <button name="actual_minutes" value="{{ $minutes }}" class="inline-flex min-h-12 cursor-pointer items-center rounded-full border-2 border-ok bg-sheet px-4 text-base font-bold text-ink hover:bg-ok hover:text-on-ink">
              <x-task.minutes :value="$minutes" />
            </button>
          </form>
        @endforeach
        <form method="POST" action="{{ route('tasks.done', $task) }}" class="flex items-center gap-2">
          @csrf
          <label for="done-actual" class="sr-only">{{ $words['ask'] }}</label>
          <input id="done-actual" type="number" name="actual_minutes" min="1" max="{{ config('suiri.tasks.max_minutes') }}" inputmode="numeric"
            value="{{ old('actual_minutes') }}" placeholder="{{ config('suiri.tasks.words.minutes_other') }}"
            class="min-h-12 w-24 rounded-xl border-2 border-rule bg-sheet px-3 text-lg text-ink placeholder:text-sm placeholder:text-muted focus:border-ink focus:outline-none">
          <span>{{ config('suiri.tasks.words.minutes_unit') }}</span>
          <button class="inline-flex min-h-12 cursor-pointer items-center rounded-xl border-2 border-ink bg-ink px-4 font-bold text-on-ink hover:opacity-90">{{ $words['save'] }}</button>
        </form>
      </div>
    @else
      <p class="mt-3 text-base">
        {{-- 予想時間を書いていれば「30分の予定で、45分かかったね」、なければ「45分かかったね」 --}}
        @if ($task->duration_minutes)
          <x-task.minutes :value="$task->duration_minutes" />{{ $words['result_plan'] }}
        @endif
        <strong><x-task.minutes :value="$task->actual_minutes" /></strong>{{ $words['result_took'] }}
      </p>
    @endif
    @error('actual_minutes')
      <p class="mt-2 text-sm font-semibold text-accent">{{ $message }}</p>
    @enderror

    <div class="mt-3 flex items-center gap-2 border-t border-ok/30 pt-2">
      <p class="min-w-0 flex-1 text-sm">{{ $words['curious'] }}</p>
      <x-task.curious origin="task" :label="$task->title" />
    </div>
  </section>
@endif
