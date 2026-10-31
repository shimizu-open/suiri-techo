{{--
  題名の似た前のやることの実績を、一言で見せる（T-8 見積もりの答え合わせ）。「何分かかりそう？」のすぐ下に置き、楽観しすぎる見積もりを防ぐ。
  例：前の『買い物』は 30分の予定で、45分かかったよ（15分長かった）
  使い方：<x-task.similar-history :tasks="$similar" />
  tasks：Queries/Tasks/SimilarTasksQuery が返す、済んだタスク（かかった時間あり。先頭がいちばん新しい＝「前の」）。空なら何も出さない
--}}
@props(['tasks'])
@php($s = config('suiri.tasks.similar'))
{{-- 似たやることがあるときだけ出す --}}
@if (count($tasks) > 0)
  <x-ui.hint icon="sparkle" tone="prediction" {{ $attributes }}>
    <ul class="space-y-1">
      {{-- 似たやることを1つずつ取り出し、予定とかかった時間を一言にする --}}
      @foreach ($tasks as $past)
        @php($diff = $past->duration_minutes ? $past->actual_minutes - $past->duration_minutes : null)
        <li class="break-words">
          {{ $loop->first ? $s['first'] : $past->done_at->isoFormat($s['date_format']) }}『{{ $past->title }}』{{ $s['topic'] }}
          {{-- 予想時間を書いていたら「◯分の予定で、」と比べる。書いていなければ、かかった時間だけ --}}
          @if ($diff === null)
            <x-task.minutes :value="$past->actual_minutes" />{{ $s['took'] }}
          @else
            <x-task.minutes :value="$past->duration_minutes" />{{ $s['plan'] }}
            {{-- 長くかかった・早く済んだ・予定どおり、で言い方を変える --}}
            @if ($diff > 0)
              <x-task.minutes :value="$past->actual_minutes" />{{ $s['took'] }}<span class="font-semibold text-answer">{{ __($s['longer'], ['diff' => $diff]) }}</span>
            @elseif ($diff < 0)
              <x-task.minutes :value="$past->actual_minutes" />{{ $s['finished'] }}<span class="font-semibold text-ok">{{ __($s['shorter'], ['diff' => abs($diff)]) }}</span>
            @else
              {{ $s['same'] }}
            @endif
          @endif
        </li>
      @endforeach
    </ul>
  </x-ui.hint>
@endif
