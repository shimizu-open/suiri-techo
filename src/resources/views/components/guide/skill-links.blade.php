{{--
  技能の小さな札を並べる（押すと技能の画面へ）。名推理カード・練習メニューの「練習できる技能」に使う。
  使い方：<x-guide.skill-links :keys="$scene['skills']" :names="$skillNames" />
  keys：技能のキーの一覧（S1〜S10）。names：キーと名前の対応（GuideLibrary::skillNames()）
--}}
@props(['keys' => [], 'names' => []])
<ul {{ $attributes->class(['flex flex-wrap gap-1.5']) }}>
  {{-- 技能を1つずつ、キーと名前の札にする --}}
  @foreach ($keys as $key)
    <li>
      <a href="{{ route('guide.skills.show', $key) }}" class="inline-flex min-h-9 items-center gap-1 rounded-full bg-mark px-3 text-sm font-semibold text-ink no-underline hover:underline">
        <span class="text-muted">{{ $key }}</span>{{ $names[$key] ?? '' }}
      </a>
    </li>
  @endforeach
</ul>
