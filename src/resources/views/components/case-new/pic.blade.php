{{--
  事件を作る画面の、選ぶボタンに描く絵（手で描いた線の絵。外の画像やアイコンの道具は使わない）。
  使い方：<x-case-new.pic name="family" class="size-8" />（class を省くと size-8）
  name：daily（日常：家）・family（親子：大人と子ども）・work（仕事：かばん）・
        seen（見た：目）・heard（聞いた：耳）・absent（なかった：点線の箱と「空っぽ」の印）
  色は文字の色（currentColor）
--}}
@props(['name'])
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->class(['size-8 shrink-0' => ! $attributes->has('class')]) }}>
  {{-- 名前で絵を選ぶ。知らない名前なら小さな丸を描く --}}
  @switch($name)
    @case('daily')
      <path d="M3.2 11.2 12 3.8l8.8 7.4"/><path d="M5.8 9.4V20h12.4V9.4"/><path d="M10.2 20v-5.2h3.6V20"/><path d="M16.4 5.2v2.4"/>
      @break
    @case('family')
      <circle cx="8.4" cy="6.4" r="2.6"/><path d="M3.8 20.2v-4.4c0-2.7 2-4.8 4.6-4.8s4.6 2.1 4.6 4.8v4.4"/><circle cx="17.2" cy="10.6" r="2"/><path d="M14.4 20.2v-2.6c0-1.8 1.2-3.3 2.8-3.3s2.8 1.5 2.8 3.3v2.6"/>
      @break
    @case('work')
      <rect x="3.4" y="7.6" width="17.2" height="12" rx="2.2"/><path d="M9 7.6V5.9c0-.9.7-1.6 1.6-1.6h2.8c.9 0 1.6.7 1.6 1.6v1.7"/><path d="M3.4 12.6h17.2"/><path d="M12 11.4v2.4"/>
      @break
    @case('seen')
      <path d="M2.5 12.2C4.6 8.1 8 6 12 6s7.4 2.1 9.5 6.2c-2.1 3.9-5.5 5.9-9.5 5.9s-7.4-2-9.5-5.9z"/><circle cx="12" cy="12" r="3.2"/><path d="M11.2 10.9h.01"/>
      @break
    @case('heard')
      <path d="M7.2 9.6a5 5 0 0 1 10 0c0 2.3-1.2 3.4-2.3 4.3-1 .9-1.7 1.7-1.7 3.2a2.5 2.5 0 0 1-4.7 1.2"/><path d="M10.2 9.8a2 2 0 0 1 4 0c0 1-.6 1.5-1.2 2"/><path d="M19.6 6.4c.8 1 1.2 2.1 1.2 3.4s-.4 2.4-1.2 3.4"/>
      @break
    @case('absent')
      <rect x="3.8" y="3.8" width="16.4" height="16.4" rx="2.6" stroke-dasharray="3.2 2.6"/><circle cx="12" cy="12" r="3.6"/><path d="m9.2 14.8 5.6-5.6"/>
      @break
    @default
      <circle cx="12" cy="12" r="3"/>
  @endswitch
</svg>
