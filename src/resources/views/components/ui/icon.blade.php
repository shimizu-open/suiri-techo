{{--
  線で描いた小さな絵（アイコン）。使い方：<x-ui.icon name="sun" class="size-5" />（class を省くと size-6）
  name：sun（今日）・check（タスク）・search（事件）・book（手引き）・loop（ふりかえり）・settings・plus・calendar・logout・folder
--}}
@props(['name'])
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->class(['size-6 shrink-0' => ! $attributes->has('class')]) }}>
  {{-- 名前で絵を選ぶ。知らない名前なら小さな丸を描く --}}
  @switch($name)
    @case('sun')
      <circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/>
      @break
    @case('check')
      <path d="M10 6h10M10 12h10M10 18h10M3.5 6l1.5 1.5L7.5 5M3.5 12l1.5 1.5 2.5-2.5M3.5 18l1.5 1.5 2.5-2.5"/>
      @break
    @case('search')
      <circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.3 15.3 21 21"/>
      @break
    @case('book')
      <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5zM4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/>
      @break
    @case('loop')
      <path d="M4 12a8 8 0 0 1 14-5.3L20 9M20 3.5V9h-5.5M20 12a8 8 0 0 1-14 5.3L4 15M4 20.5V15h5.5"/>
      @break
    @case('settings')
      <path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>
      @break
    @case('plus')
      <path d="M12 5v14M5 12h14"/>
      @break
    @case('calendar')
      <rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>
      @break
    @case('logout')
      <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16.5 5.5 12 10 7.5M5.5 12H16"/>
      @break
    @case('folder')
      <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
      @break
    @default
      <circle cx="12" cy="12" r="3"/>
  @endswitch
</svg>
