{{--
  前と次へのリンク（わざ S1→S10、名推理カードの並び）。端では片方だけ出す。言葉は config('suiri.guide.pager')。
  使い方：<x-guide.pager :neighbors="$neighbors" route="guide.skills.show" label="name" />
  neighbors：['prev' => 1件か null, 'next' => 1件か null]（GuideLibrary::neighbors()）。route：行き先のルート名。label：見出しに使う欄の名前
--}}
@props(['neighbors', 'route', 'label' => 'name'])
@php($words = config('suiri.guide.pager'))
<nav aria-label="{{ $words['label'] }}" {{ $attributes->class(['grid grid-cols-2 gap-3 border-t border-rule pt-4']) }}>
  <div>
    {{-- 前の1件があれば、左に出す --}}
    @if ($neighbors['prev'])
      <a href="{{ route($route, $neighbors['prev']['key']) }}" class="flex min-h-12 flex-col justify-center font-semibold no-underline hover:underline">
        <span class="text-sm text-muted">{{ $words['prev'] }}</span>{{ $neighbors['prev'][$label] }}
      </a>
    @endif
  </div>
  <div class="text-right">
    {{-- 次の1件があれば、右に出す --}}
    @if ($neighbors['next'])
      <a href="{{ route($route, $neighbors['next']['key']) }}" class="flex min-h-12 flex-col justify-center font-semibold no-underline hover:underline">
        <span class="text-sm text-muted">{{ $words['next'] }}</span>{{ $neighbors['next'][$label] }}
      </a>
    @endif
  </div>
</nav>
