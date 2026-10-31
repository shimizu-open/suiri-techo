{{--
  公開ページ（プライバシーポリシー・利用規約・このアプリについて）のいちばん上の「かんたんに言うと」。
  硬い本文を読む前に、何のページかの一言と要点を、やさしい説明の吹き出し（<x-ui.hint>）で先に見せる（中身は本文と同じ。文は config('legal.plain')）。
  使い方：<x-legal.plain page="privacy" />。page：privacy・terms・about
--}}
@props(['page'])
@php($plain = config('legal.plain'))
<x-ui.hint :label="$plain['title']" icon="book" {{ $attributes->class(['my-5']) }}>
  <p class="font-bold">{{ $plain[$page]['lead'] }}</p>
  <ul class="mt-1.5 space-y-1">
    {{-- 要点を1つずつ取り出し、点つきの行で並べる --}}
    @foreach ($plain[$page]['points'] as $point)
      <li class="flex gap-2">
        <span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-ink"></span>
        <span>{{ strtr($point, [':backup_days' => config('legal.backup_days')]) }}</span>
      </li>
    @endforeach
  </ul>
</x-ui.hint>
