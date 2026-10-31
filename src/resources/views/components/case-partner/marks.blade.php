{{--
  見せ合いの荷札に付ける「1人分の印」：だれのものかの丸い印と、○（合う）×（合わない）を付けた見立ての番号（例「○1・2 ×3」）。
  印がなければ「印なし」。その人の見立てが1つだけなら番号を省いて ○・× だけにする。
  使い方：<x-case-partner.marks who="partner" name="長男さん" :marks="['fits' => [1], 'contradicts' => []]" :numbered="false" />
--}}
@props(['who', 'name', 'marks', 'numbered' => true])
@php($none = $marks['fits'] === [] && $marks['contradicts'] === [])
<span class="inline-flex min-h-8 items-center gap-1.5 rounded-full bg-sheet py-1 pr-3 pl-1 text-base">
  <x-case-partner.who :who="$who" :name="$name" size="sm" />
  <span class="sr-only">{{ $name }}</span>
  {{-- ○ も × もなければ「印なし」、あれば ○ を緑・× を赤で、付けた見立ての番号と一緒に出す --}}
  @if ($none)
    <span class="text-sm text-muted">{{ config('suiri.case_partner.reveal.no_mark') }}</span>
  @else
    {{-- ○ を付けた見立てがあれば出す --}}
    @if ($marks['fits'] !== [])
      <span class="font-bold text-ok">○{{ $numbered ? implode('・', $marks['fits']) : '' }}</span>
    @endif
    {{-- × を付けた見立てがあれば出す --}}
    @if ($marks['contradicts'] !== [])
      <span class="font-bold text-accent">×{{ $numbered ? implode('・', $marks['contradicts']) : '' }}</span>
    @endif
  @endif
</span>
