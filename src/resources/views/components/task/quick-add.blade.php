{{--
  やることをすばやく書く欄（D-3）：「何をする？」の1つだけ。Enter か「書く」で作る（期日は渡された日。空なら「いつか」）。
  日にちや時間も決めたいときは「日にちや時間も決める」で、書いた題名を持ってくわしく書く画面へ。
  使い方：<x-task.quick-add :due="$today" back="today" />
  due：期日（'Y-m-d'。null なら空＝いつか）。back：'today' を渡すと、作ったあと今日の画面へ戻す
--}}
@props(['due' => null, 'back' => null])
@php($words = config('suiri.tasks.words'))
<form method="POST" action="{{ route('tasks.store') }}" {{ $attributes->class(['space-y-2']) }}>
  @csrf
  {{-- 戻り先が決まっていれば、一緒に送る --}}
  @if ($back)
    <input type="hidden" name="back" value="{{ $back }}">
  @endif
  <input type="hidden" name="due_on" value="{{ old('due_on', $due) }}">
  <label for="quick-title" class="block text-base font-semibold">{{ $words['title'] }}</label>
  <div class="flex gap-2">
    <input id="quick-title" name="title" value="{{ old('title') }}" required maxlength="{{ config('suiri.limits.task_title') }}"
      placeholder="{{ $words['title_example'] }}" autocomplete="off" enterkeyhint="done" @error('title') aria-invalid="true" @enderror
      @class([
        'block min-h-12 min-w-0 flex-1 rounded-xl border-2 bg-sheet px-4 text-lg text-ink placeholder:text-muted focus:border-ink focus:outline-none',
        'border-accent' => $errors->has('title'),
        'border-rule' => ! $errors->has('title'),
      ])>
    <button type="submit" class="inline-flex min-h-12 shrink-0 cursor-pointer items-center gap-1 rounded-xl border-2 border-ink bg-ink px-5 text-lg font-bold text-on-ink hover:opacity-90">
      <x-ui.icon name="plus" class="size-5" />{{ $words['add'] }}
    </button>
  </div>
  <button type="submit" formaction="{{ route('tasks.draft') }}" formnovalidate
    class="inline-flex min-h-11 cursor-pointer items-center gap-1 rounded-lg px-1 text-sm font-semibold text-muted hover:text-ink">{{ $words['more'] }}<span aria-hidden="true">›</span></button>
  @error('title')
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
  @error('due_on')
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
</form>
