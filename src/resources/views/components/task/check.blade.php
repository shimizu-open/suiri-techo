{{--
  やることの行の左の丸（済んだ・まだの切り替え）。丸を1回押すだけで、その場でフォームを送る（JavaScript なし）。
  押せる所は 48px 四方（子どもの指でも押しやすい）。
  使い方：<x-task.check :task="$task" />
  予想から作った確かめるタスク（もとの予想があるもの）は、答え合わせで済むので、押せない印（点線の丸と虫めがね）だけを出す
--}}
@props(['task'])
@php($done = $task->isDone())
@php($words = config('suiri.tasks.words'))
{{-- 答え合わせで済むタスクは押せない印、ほかは押すと済んだ・まだが切り替わるボタン --}}
@if ($task->answeredByPrediction())
  <span class="grid size-12 shrink-0 place-items-center" title="{{ $words['by_prediction'] }}">
    <span @class(['grid size-8 place-items-center rounded-full border-2', 'border-ok bg-ok text-on-ink' => $done, 'border-dashed border-answer text-answer' => ! $done])>
      {{-- 済んでいればチェックの印、まだなら「確かめる」の虫めがね --}}
      @if ($done)
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
      @else
        <x-ui.icon name="search" class="size-4" />
      @endif
    </span>
  </span>
@else
  <form method="POST" action="{{ route($done ? 'tasks.undone' : 'tasks.done', $task) }}" class="shrink-0">
    @csrf
    {{-- 済んだものをまだに戻すときは DELETE で送る --}}
    @if ($done)
      @method('DELETE')
    @endif
    <button type="submit" class="group grid size-12 cursor-pointer place-items-center rounded-full hover:bg-mark"
            aria-label="{{ __($done ? $words['undo_label'] : $words['done_label'], ['title' => $task->title]) }}">
      <span @class(['grid size-8 place-items-center rounded-full border-2', 'border-ok bg-ok text-on-ink' => $done, 'border-ink/50 bg-sheet text-transparent group-hover:border-ok group-hover:text-ok' => ! $done])>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
      </span>
    </button>
  </form>
@endif
