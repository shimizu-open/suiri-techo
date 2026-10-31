{{--
  やることを直す画面（GET /tasks/{task}/edit）。まず「何をする？」を大きく出し、日にち・時間・何分・メモは「くわしく決める」で開く。
  予想から作った確かめるタスクは、上に「答え合わせをすると自動で済む」ことと、答え合わせへのボタンを出し、「くわしく決める」を開いておく。
  下にきょうの画面へ戻るリンク（そのタスクが入る枠を開いて）と、消すボタン（確かめてから消す）。答え合わせ待ちの予想のものは消せないので、
  消すボタンの代わりに予想を取り下げるボタンを出す（取り下げると、このやることも消える）
--}}
<x-layouts.app :title="config('suiri.tasks.words.edit_title')">
  <div class="space-y-5">
    {{-- もとの予想がある確かめるタスクなら、答え合わせで済むことを先に知らせる --}}
    @if ($task->answeredByPrediction())
      <x-ui.hint icon="check" tone="answer">
        {{ __(config('suiri.tasks.words.verify_note'), ['verify_task' => \App\Support\Label::word('verify_task')]) }}
        {{-- まだ済んでいなくて予想の画面があれば、答え合わせへのボタンを出す --}}
        @if (! $task->isDone() && Route::has('predictions.show'))
          <x-slot:actions>
            <x-ui.button size="sm" :href="route('predictions.show', $task->prediction_id)" class="min-h-11 bg-answer border-answer">{{ config('suiri.tasks.words.resolve') }}</x-ui.button>
          </x-slot:actions>
        @endif
      </x-ui.hint>
    @endif

    <x-task.form :task="$task" :similar="$similar" :action="route('tasks.update', $task)" method="PATCH"
      :submit="config('suiri.tasks.words.update')" :open="$task->answeredByPrediction()" />

    <div class="flex items-start justify-between gap-3 border-t border-rule pt-4">
      <a href="{{ $backUrl }}" class="inline-flex min-h-11 shrink-0 items-center text-sm whitespace-nowrap text-muted">{{ config('suiri.tasks.words.back') }}</a>
      {{-- 答え合わせ待ちの予想を確かめるタスクなら予想の取り下げを、ほかは消すボタンを出す --}}
      @if ($task->checksPendingPrediction())
        <x-ui.confirm :action="route('predictions.withdraw', $task->prediction_id)" method="POST" :label="config('suiri.tasks.words.withdraw')"
          :confirm-label="config('suiri.tasks.words.withdraw_confirm')" :message="config('suiri.tasks.words.withdraw_message')" class="text-right" />
      @else
        <x-ui.confirm :action="route('tasks.destroy', $task)" :label="config('suiri.tasks.words.delete')"
          :confirm-label="config('suiri.tasks.words.delete_confirm')" :message="config('suiri.tasks.words.delete_message')" class="text-right" />
      @endif
    </div>
  </div>
</x-layouts.app>
