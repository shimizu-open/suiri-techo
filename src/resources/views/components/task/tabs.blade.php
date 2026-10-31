{{--
  やることの一覧のタブ（きょう／これから／いつか／済んだ）。言葉と順番は config('suiri.tasks.views')。
  使い方：<x-task.tabs :current="$view" :counts="$counts" />
  current：いま開いている一覧の種類。counts：種類ごとのまだ済んでいない件数（済んだものは数えない）
--}}
@props(['current', 'counts' => []])
<nav aria-label="{{ config('suiri.tasks.words.list_title') }}" {{ $attributes->class(['grid grid-cols-4 border-b border-rule']) }}>
  {{-- 一覧の種類を1つずつ取り出してタブにする。いま開いているタブは太い下線と濃い文字にする --}}
  @foreach (config('suiri.tasks.views') as $key => $label)
    <a href="{{ route('tasks.index', ['view' => $key]) }}" @if ($key === $current) aria-current="page" @endif @class([
      '-mb-px inline-flex min-h-12 items-center justify-center gap-0.5 border-b-[3px] px-0.5 text-[15px] font-bold whitespace-nowrap no-underline sm:gap-1 sm:text-base',
      'border-ink text-ink' => $key === $current,
      'border-transparent text-muted hover:text-ink' => $key !== $current,
    ])>
      {{ $label }}
      {{-- 件数を数えている種類で、1件以上あれば小さな丸の札で添える --}}
      @if (! empty($counts[$key]))
        <span @class(['inline-grid min-w-5 place-items-center rounded-full px-1 text-xs leading-5 tabular-nums', 'bg-ink text-on-ink' => $key === $current, 'bg-mark text-ink' => $key !== $current])>{{ $counts[$key] }}</span>
      @endif
    </a>
  @endforeach
</nav>
