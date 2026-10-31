{{--
  たたんだ枠（いつか・済み）の中身：やることを大きな行で並べる。1件もなければ一言だけ。
  使い方：<x-today.task-rows :tasks="$someday" :empty="…" />
  tasks：Task の並び。empty：1件もないときの一言
--}}
@props(['tasks' => [], 'empty'])
{{-- やることがあれば行で並べ、なければ一言だけ出す --}}
@if (collect($tasks)->isNotEmpty())
  <ul {{ $attributes->class(['divide-y divide-rule']) }}>
    {{-- やることを1件ずつ取り出して1行にする --}}
    @foreach ($tasks as $task)
      <li class="pr-2 pl-3"><x-task.item :task="$task" /></li>
    @endforeach
  </ul>
@else
  <p class="px-4 py-4 text-sm text-muted">{{ $empty }}</p>
@endif
