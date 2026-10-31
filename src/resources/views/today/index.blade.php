{{--
  今日の画面（GET /today）。ログインした人の最初の画面。開いた瞬間に「今日やること」が1つだけ大きく見える並び：
  1. いまやることのカード（1枚だけ）：答え合わせの日が来た予想があれば「答え合わせしよう」（1件ずつ）、
     なければ記録のきっかけと「気になったことを書こう」（<x-today.next-step>）
  2. 今日の予定とやること（時刻順。見出しの横の「＋ 書く」で、すばやく書く欄が開く）
  3. 今日の練習・捜査中の事件（下に小さく）
  タスクを済ませた直後だけ、いちばん上に「実際は何分だった？」をさそう枠を出す（何もしなくてよい）
--}}
<x-layouts.app :title="__(config('suiri.today.heading'), ['date' => \Carbon\Carbon::parse($today)->isoFormat(config('suiri.today.date_format'))])">
  <div class="space-y-6">
    <x-task.done-followup :task="$justDone" />

    <x-today.next-step :predictions="$predictions" :cue="$user->cue" />

    <x-today.agenda :items="$items" :overdue="$overdue">
      <x-task.quick-add :due="$today" back="today" />
    </x-today.agenda>

    <div class="grid gap-x-8 gap-y-6 md:grid-cols-2">
      <x-today.practice-suggestion :practice="$practice" :kid="$user->prefersKidWords()" />
      <x-today.open-cases :cases="$cases" />
    </div>

    {{-- 答え合わせのカードを出している日は、気になったことを書く入口を下に小さく1行で残す --}}
    @if (count($predictions) > 0 && Route::has('cases.create'))
      <p class="border-t border-rule pt-2">
        <a href="{{ route('cases.create') }}" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-clue">
          <x-ui.step-icon name="eye" class="size-5" />{{ config('suiri.today.record.new') }}
        </a>
      </p>
    @endif
  </div>
</x-layouts.app>
