{{--
  ログイン後の画面の骨組み。使い方：<x-layouts.app title="今日"> 中身 </x-layouts.app>
  - 上の帯（軽く低い帯）：画面の名前（title）と、右に設定のアイコン。<x-slot:actions> を渡すと、設定の左にボタンを置ける
  - スマホ：下に固定のナビ（今日・タスク・事件・手引き・ふりかえり）。パソコン（md 以上）：左に縦のナビ
  - 中身の上にお知らせ（session の status・error）を出し、下にフッターを置く
  - wide：パソコン（lg 以上）で中身の幅を広げる（<x-layouts.app title="…" wide>）。左右2列に分ける事件の画面で、
    左の列（いまやることのカード）が狭くならないように使う。スマホ・タブレットの幅とほかの画面は変えない
--}}
@props(['title', 'wide' => false])
@php($width = $wide ? 'max-w-3xl lg:max-w-5xl' : 'max-w-3xl')
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title }}｜{{ config('app.name') }}</title>
  <meta name="theme-color" content="#f3f5f9" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#0f1524" media="(prefers-color-scheme: dark)">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink">
  <div class="md:flex">
    <x-layouts.nav variant="side" />

    <div class="min-w-0 flex-1">
      <header class="sticky top-0 z-30 border-b border-rule/70 bg-paper">
        <div class="mx-auto flex h-12 {{ $width }} items-center gap-2 px-4">
          <h1 class="min-w-0 flex-1 truncate text-base text-muted">{{ $title }}</h1>
          {{ $actions ?? '' }}
          <a href="{{ route('settings.show') }}" aria-label="設定" title="設定"
             @class(['-mr-2 grid size-11 place-items-center rounded-xl text-muted hover:bg-mark hover:text-ink', 'bg-mark text-ink' => request()->routeIs('settings.*')])>
            <x-ui.icon name="settings" class="size-5" />
          </a>
        </div>
      </header>

      <main class="mx-auto {{ $width }} px-4 pt-4 pb-32 md:pt-6 md:pb-12">
        <x-ui.flash />
        {{ $slot }}
        <x-layouts.footer class="mt-12" />
      </main>
    </div>
  </div>

  <x-layouts.nav variant="bottom" />
  @stack('scripts')
</body>
</html>
