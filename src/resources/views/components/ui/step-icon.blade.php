{{--
  段ごとの絵（手で描いた線の絵。外の画像やアイコンの道具は使わない）。使い方：<x-ui.step-icon name="eye" class="size-6" />（class を省くと size-6）
  name：eye（見たこと）・question（どうして？）・arrow（もしそうなら）・calendar（確かめる日）・check（答え合わせ）・
        book（手引き）・attic（屋根裏の棚）・norbury（ノーベリ帳）・partner（相棒）・sparkle（ひらめき・ワトソンのひとこと）
  色は文字の色（currentColor）。text-clue などで段の色にする
--}}
@props(['name'])
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->class(['size-6 shrink-0' => ! $attributes->has('class')]) }}>
  {{-- 名前で絵を選ぶ。知らない名前なら小さな丸を描く --}}
  @switch($name)
    @case('eye')
      <path d="M2.5 12.2C4.6 8.1 8 6 12 6s7.4 2.1 9.5 6.2c-2.1 3.9-5.5 5.9-9.5 5.9s-7.4-2-9.5-5.9z"/><circle cx="12" cy="12" r="3.2"/><path d="M11.2 10.9h.01"/>
      @break
    @case('question')
      <circle cx="12" cy="12" r="9.3"/><path d="M9.2 9.4c.3-1.7 1.5-2.7 3-2.7 1.7 0 3 1.1 3 2.6 0 1.4-.9 2-1.9 2.6-.8.5-1.3 1.1-1.3 2.1v.5"/><path d="M12 17.4h.01"/>
      @break
    @case('arrow')
      <path d="M3.5 17.5c2.2-5.6 6.4-8.3 13.6-8.6"/><path d="M13.6 5.2 17.6 8.9l-3.6 4"/><circle cx="4" cy="18.6" r="1.1"/>
      @break
    @case('calendar')
      <rect x="3.5" y="5" width="17" height="15.5" rx="2.4"/><path d="M3.5 10h17M8 3v4M16 3v4"/><path d="m9 15 2 2 4-4.2"/>
      @break
    @case('check')
      <circle cx="12" cy="12" r="9.3"/><path d="m7.6 12.4 3 3 5.9-6.4"/>
      @break
    @case('book')
      <path d="M12 6.3C9.8 4.9 7 4.4 3.5 4.8v13.6c3.5-.4 6.3.1 8.5 1.5 2.2-1.4 5-1.9 8.5-1.5V4.8c-3.5-.4-6.3.1-8.5 1.5zM12 6.3v13.6"/>
      @break
    @case('attic')
      <path d="M2.8 11.6 12 3.8l9.2 7.8"/><path d="M5.5 9.5v11h13v-11"/><path d="M8.5 14.2h7M8.5 17.6h7"/><circle cx="12" cy="10.2" r=".9"/>
      @break
    @case('norbury')
      <rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8.5 3v18"/><path d="M13 3v6l1.8-1.3L16.6 9V3"/><path d="m11.5 13.5 4 4M15.5 13.5l-4 4"/>
      @break
    @case('partner')
      <circle cx="8.8" cy="7.8" r="3.1"/><circle cx="16.8" cy="10.2" r="2.4"/><path d="M3 20.5c.3-3.6 2.6-6 5.8-6s5.5 2.4 5.8 6"/><path d="M14.6 16c.6-.6 1.4-.9 2.2-.9 2 0 3.5 1.9 3.7 5.4"/>
      @break
    @case('sparkle')
      <path d="M11 3.2c.6 4 2.1 5.6 6.2 6.2-4.1.7-5.6 2.2-6.2 6.2-.7-4-2.2-5.5-6.2-6.2 4-.6 5.5-2.2 6.2-6.2z"/><path d="M18.3 14.6c.3 1.8.9 2.4 2.7 2.7-1.8.3-2.4.9-2.7 2.7-.3-1.8-.9-2.4-2.7-2.7 1.8-.3 2.4-.9 2.7-2.7z"/>
      @break
    @default
      <circle cx="12" cy="12" r="3"/>
  @endswitch
</svg>
