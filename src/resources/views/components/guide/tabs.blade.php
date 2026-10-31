{{--
  手引きの中の行き先（入口・名推理カード・練習メニュー・3つの考え方）。入口以外の手引きの画面のいちばん上に置き、ページを行き来できるようにする。
  言葉は config('suiri.guide.tabs')。幅が足りない端末では横にすべらせて見る。
  使い方：<x-guide.tabs current="guide.scenes.index" />
  current：いまいる行き先のルート名（技能の画面は guide.index を渡す）
--}}
@props(['current' => null])
<nav aria-label="手引きの中の行き先" {{ $attributes->class(['mb-4 overflow-x-auto border-b border-rule']) }}>
  <ul class="-mb-px flex min-w-max gap-1 text-base font-semibold">
    {{-- 行き先を1つずつ並べ、いまいる所には下線と印を付ける --}}
    @foreach (config('suiri.guide.tabs') as $route => $label)
      <li>
        <a href="{{ route($route) }}" @if ($route === $current) aria-current="page" @endif @class([
          'inline-flex min-h-12 items-center border-b-[3px] px-2.5 no-underline',
          'border-accent text-ink' => $route === $current,
          'border-transparent text-muted hover:text-ink' => $route !== $current,
        ])>{{ $label }}</a>
      </li>
    @endforeach
  </ul>
</nav>
