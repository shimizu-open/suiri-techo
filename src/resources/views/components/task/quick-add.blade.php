{{--
  やることを1行で書く欄（今日の画面の「きょうの予定とやること」の見出しのすぐ下）。題名を書いて、いつやるかのボタンを押すだけで作れる。
  ボタンは3つともこのフォームの送信ボタンで、押したボタンの値が期日（due_on）になる：今日＝今日の日付・明日＝明日の日付・いつか＝空。
  題名の欄で Enter を押したときは、最初の送信ボタン「今日」で送られる（並びの順を変えない）。
  日にちや時間・何分かかりそうかも決めたいときは「くわしく」で、書いた題名を持って作る画面へ（POST /tasks/draft。まだ作らない）。
  作ったあとは、受付がきょうの画面（そのタスクが入る枠を ?open= で開いて）へ戻す。
  使い方：<x-task.quick-add :dates="$dates" />
  dates：['today' => 'Y-m-d', 'tomorrow' => 'Y-m-d']（受付が渡す。なければその場の今日と明日）
--}}
@props(['dates' => null])
@php($words = config('suiri.tasks.quick'))
@php($dates = $dates ?? ['today' => today()->toDateString(), 'tomorrow' => today()->addDay()->toDateString()])
@php($buttons = ['today' => $dates['today'], 'tomorrow' => $dates['tomorrow'], 'someday' => ''])
<form method="POST" action="{{ route('tasks.store') }}" aria-label="{{ config('suiri.today.agenda.add_label') }}" {{ $attributes->class(['space-y-2']) }}>
  @csrf
  <label for="quick-title" class="block text-base font-semibold">{{ $words['label'] }}</label>
  <input id="quick-title" name="title" value="{{ old('title') }}" required maxlength="{{ config('suiri.limits.task_title') }}"
    placeholder="{{ $words['placeholder'] }}" autocomplete="off" enterkeyhint="done" @error('title') aria-invalid="true" @enderror
    @class([
      'block min-h-12 w-full rounded-xl border-2 bg-sheet px-4 text-lg text-ink placeholder:text-muted focus:border-ink focus:outline-none',
      'border-accent' => $errors->has('title'),
      'border-rule' => ! $errors->has('title'),
    ])>
  <div class="grid grid-cols-[1fr_1fr_1fr_auto] items-center gap-2 sm:grid-cols-[repeat(3,9rem)_auto]">
    {{-- 今日・明日・いつか を1つずつ取り出し、押すとその期日で作る送信ボタンにする（今日だけ塗って、いちばん使うものを目立たせる） --}}
    @foreach ($buttons as $key => $value)
      <button type="submit" name="due_on" value="{{ $value }}" @class([
        'inline-flex min-h-12 min-w-0 cursor-pointer items-center justify-center gap-0.5 rounded-xl border-2 px-1 text-base font-bold whitespace-nowrap',
        'border-ink bg-ink text-on-ink hover:opacity-90' => $key === 'today',
        'border-ink/60 bg-sheet text-ink hover:border-ink' => $key !== 'today',
      ])>
        <x-ui.icon name="plus" class="size-5 shrink-0" />{{ $words[$key] }}<span class="sr-only">{{ $words['add_to'] }}</span>
      </button>
    @endforeach
    <button type="submit" formaction="{{ route('tasks.draft') }}" formnovalidate
      class="inline-flex min-h-12 cursor-pointer items-center gap-0.5 rounded-xl px-1 text-sm font-semibold whitespace-nowrap text-muted hover:text-ink">{{ $words['more'] }}<span aria-hidden="true">›</span></button>
  </div>
  @error('title')
    <p class="text-sm font-semibold text-accent">{{ $message }}</p>
  @enderror
  @error('due_on')
    <p class="text-sm font-semibold text-accent">{{ $message }}</p>
  @enderror
</form>
