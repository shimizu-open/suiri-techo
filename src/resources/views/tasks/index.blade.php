{{--
  やること（タスク）の一覧（GET /tasks?view=today|upcoming|someday|done）。この画面ですることは「書く」と「済ませる」：
  上に「何をする？」の1行（すばやく書く欄）、その下にタブ（きょう／これから／いつか／済んだ）と大きな行の一覧。
  行の左の丸を1回押すと済み、題名を押すと直す画面へ。済ませた直後は、いちばん上に「実際は何分だった？」をさそう枠を出す
--}}
<x-layouts.app :title="config('suiri.tasks.words.list_title')">
  <div class="space-y-5">
    <x-task.done-followup :task="$justDone" />

    <x-task.quick-add :due="$view === 'someday' ? null : $today" />

    <section class="overflow-hidden rounded-card border border-rule bg-sheet">
      <x-task.tabs :current="$view" :counts="$counts" />
      {{-- その一覧にやることがあれば大きな行で並べ、なければ空のときの一言を出す --}}
      @if ($tasks->isNotEmpty())
        <ul class="divide-y divide-rule">
          {{-- やることを1件ずつ取り出して1行にする --}}
          @foreach ($tasks as $task)
            <li class="px-2"><x-task.item :task="$task" /></li>
          @endforeach
        </ul>
        {{-- 済んだものの一覧は新しいものから決まった件数だけなので、そのことを添える --}}
        @if ($view === 'done')
          <p class="border-t border-rule px-4 py-2 text-sm text-muted">{{ __(config('suiri.tasks.done_limit_note'), ['count' => config('suiri.tasks.done_limit')]) }}</p>
        @endif
      @else
        <div class="px-4 py-8">
          <p class="text-lg font-bold">{{ config('suiri.tasks.empty.'.$view.'.title') }}</p>
          <p class="mt-1 text-base text-muted">{{ config('suiri.tasks.empty.'.$view.'.hint') }}</p>
        </div>
      @endif
    </section>
  </div>
</x-layouts.app>
