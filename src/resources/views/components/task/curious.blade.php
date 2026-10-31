{{--
  「気になる」：予定・タスクの行から、それを出発点にした事件を作る画面へ進むボタン（D-4・T-5）。
  使い方：<x-task.curious origin="task" :label="$task->title" />
  origin：task（タスク）・event（予定）。label：事件のきっかけとして写す題名（と時刻）。
  strong：目立たせる（さっき終わった予定への声かけ S-6。深い赤の枠にする）。
  pill：やることの行を開いたときの操作の並び（直す・消すと同じ横長の形。絵と文字を横に並べる）。
  題名を URL に載せない（アクセスの記録に残さない）よう、POST /cases/draft に送ってから作る画面へ移る（JavaScript なしのフォーム）。
  事件を作る画面（cases.draft）がまだないときは何も出さない
--}}
@props(['origin', 'label', 'strong' => false, 'pill' => false])
{{-- 事件を作る画面へ持っていく受付があるときだけ、ボタンを出す --}}
@if (Route::has('cases.draft'))
  <form method="POST" action="{{ route('cases.draft') }}" {{ $attributes->class(['shrink-0']) }}>
    @csrf
    <input type="hidden" name="origin" value="{{ $origin }}">
    <input type="hidden" name="label" value="{{ Str::substr($label, 0, config('suiri.limits.origin_label')) }}">
    <button type="submit" aria-label="{{ __(config('suiri.today.curious_label'), ['label' => $label]) }}" title="{{ config('suiri.today.curious_hint') }}"
            @class([
              'grid min-h-12 min-w-12 cursor-pointer place-items-center content-center gap-0.5 rounded-xl px-1' => ! $pill,
              'inline-flex min-h-12 cursor-pointer items-center gap-1 rounded-full border-2 border-clue/50 px-4 text-base font-semibold text-clue hover:bg-clue-soft' => $pill,
              'border border-accent bg-accent-soft text-accent' => $strong,
              'text-muted hover:bg-clue-soft hover:text-clue' => ! $strong && ! $pill,
            ])>
      <x-ui.icon name="search" class="size-5" />
      <span @class(['text-xs leading-none font-semibold' => ! $pill])>{{ config('suiri.today.curious') }}</span>
    </button>
  </form>
@endif
