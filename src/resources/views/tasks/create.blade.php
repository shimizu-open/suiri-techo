{{--
  やることをくわしく書く画面（GET /tasks/create）。まず「何をする？」だけを大きく出し、日にちや時間は「くわしく決める」で開く。
  すばやく書く欄の「日にちや時間も決める」から来たときは、書いた題名・期日を引き継ぎ、「くわしく決める」を開いておく。
  種類はいつも「ふつう」（確かめるタスクは予想から自動で作る）
--}}
<x-layouts.app :title="config('suiri.tasks.words.create_title')">
  <x-task.form :task="$task" :similar="$similar" :action="route('tasks.store')" :submit="config('suiri.tasks.words.create')"
    :back="old('back')" :open="old('title') !== null" />
  <p class="mt-6"><a href="{{ route('tasks.index') }}" class="inline-flex min-h-11 items-center text-sm text-muted">{{ config('suiri.tasks.words.back') }}</a></p>
</x-layouts.app>
