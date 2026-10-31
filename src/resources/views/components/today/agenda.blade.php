{{--
  今日の画面：きょうの予定とやること（D-1・D-3・D-4）。Google カレンダーの予定と今日が期日のタスクを1つの並びにし、
  左に時刻（終日・いつでもは上）。行は大きく、タスクは左の丸を1回押すだけで済ませられる。予定と済んだやることの行からは「気になる」で事件を作れる。
  すばやく書く欄は最初は閉じておき、見出しの横の「＋ 書く」で開く（JavaScript なしの <details>。入力にまちがいがあれば開いたまま）。
  使い方：<x-today.agenda :items="$items" :overdue="$overdue"> すばやく書く欄 </x-today.agenda>
  items：Queries/Today/AgendaQuery の並び（1件：type・title・starts_at・ends_at・all_day・task・just_ended）。
  overdue：期日を過ぎたまだ済んでいないタスクの数（あれば一覧へさそう）。中身（slot）：「＋ 書く」で開く欄
--}}
@props(['items' => [], 'overdue' => 0])
@php($words = config('suiri.today.agenda'))
<section aria-labelledby="agenda-title" {{ $attributes->class(['overflow-hidden rounded-card border border-rule bg-sheet']) }}>
  <details class="group" @if ($errors->hasAny(['title', 'due_on'])) open @endif>
    <summary class="flex min-h-16 cursor-pointer items-center justify-between gap-3 px-4 py-2">
      <h2 id="agenda-title" class="text-lg">{{ $words['title'] }}</h2>
      <span class="inline-flex min-h-12 shrink-0 items-center gap-1 rounded-full border-2 border-ink px-4 font-bold text-ink group-open:hidden">
        <x-ui.icon name="plus" class="size-5" />{{ $words['add'] }}<span class="sr-only">（{{ $words['add_label'] }}）</span>
      </span>
      <span class="hidden min-h-12 shrink-0 items-center rounded-full border border-rule px-4 text-sm font-semibold text-muted group-open:inline-flex">{{ $words['close'] }}</span>
    </summary>
    <div class="border-t border-rule bg-paper px-4 py-4">{{ $slot }}</div>
  </details>

  {{-- 予定もやることもなければ一言だけ。あれば時刻順に大きな行で並べる --}}
  @if (count($items) === 0)
    <div class="border-t border-rule px-4 py-6 text-center">
      <p class="font-semibold">{{ $words['empty'] }}</p>
      <p class="mt-1 text-sm text-muted">{{ $words['empty_hint'] }}</p>
    </div>
  @else
    <ul class="divide-y divide-rule border-t border-rule">
      {{-- 予定とやることを1件ずつ取り出し、左に時刻、右に中身を出す --}}
      @foreach ($items as $item)
        <li class="flex items-center gap-1 pr-2 pl-3">
          <span class="w-14 shrink-0 text-xs font-bold whitespace-nowrap tabular-nums text-muted">{{ $item['all_day'] ? ($item['type'] === 'task' ? $words['anytime'] : $words['all_day']) : $item['starts_at'] }}</span>
          {{-- やることならタスクの行（済ませる丸つき）、予定なら予定の行（読むだけ） --}}
          @if ($item['type'] === 'task')
            <x-task.item :task="$item['task']" :date="false" :time="false" curious class="min-w-0 flex-1" />
          @else
            <x-today.agenda-event :item="$item" class="min-w-0 flex-1" />
          @endif
        </li>
      @endforeach
    </ul>
  @endif

  {{-- 期日を過ぎたやることがあれば、一覧（きょう）で見られるようにさそう --}}
  @if ($overdue > 0)
    <a href="{{ route('tasks.index', ['view' => 'today']) }}" class="flex min-h-14 items-center justify-between gap-2 border-t border-rule px-4 font-semibold text-answer no-underline hover:bg-answer-soft">
      <span>{{ __($words['overdue'], ['count' => $overdue]) }}</span>
      <span aria-hidden="true" class="text-xl">›</span>
    </a>
  @endif
</section>
