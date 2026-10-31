{{--
  ログイン前の画面と公開ページの骨組み。使い方：<x-layouts.guest title="プライバシーポリシー"> 中身 </x-layouts.guest>
  上に題字（推理手帳）、中身の上にお知らせ、下にフッター。ログイン中の人が公開ページを見たときは、右上に「手帳に戻る」を出す。
  :brand="false" で上の題字の帯を出さない（トップのように、中身の一番上で題字を大きく見せる画面）
--}}
@props(['title' => null, 'brand' => true])
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ? $title.'｜' : '' }}{{ config('app.name') }}</title>
  <meta name="theme-color" content="#f5efe2" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#121829" media="(prefers-color-scheme: dark)">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink">
  {{-- 題字の帯を出す画面だけ、上に帯を置く --}}
  @if ($brand)
    <header class="border-b border-rule">
      <div class="mx-auto flex h-14 max-w-2xl items-center justify-between px-4">
        <a href="{{ route('home') }}" class="font-mincho text-lg font-bold no-underline">{{ config('app.name') }}</a>
        {{-- ログインして同意も済んだ人には、手帳（最初の画面）へ戻るリンクを出す --}}
        @if (auth()->user()?->hasAgreed())
          <a href="{{ \App\Support\Landing::url() }}" class="text-sm">手帳に戻る</a>
        @endif
      </div>
    </header>
  @endif

  <main class="mx-auto max-w-2xl px-4 pt-5 pb-12">
    <x-ui.flash />
    {{ $slot }}
    <x-layouts.footer class="mt-12" />
  </main>
</body>
</html>
