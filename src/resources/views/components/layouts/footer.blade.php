{{--
  画面のフッター：このアプリについて・プライバシーポリシー・利用規約へのリンクと、「医療機器ではありません」の一文。
  使い方：<x-layouts.footer class="mt-12" />（レイアウトが置くので、ふだんは画面から使わない）
--}}
<footer {{ $attributes->class(['border-t border-rule pt-5 text-xs text-muted']) }}>
  <ul class="flex flex-wrap gap-x-4 gap-y-1">
    <li><a href="{{ route('about') }}">このアプリについて</a></li>
    <li><a href="{{ route('privacy') }}">プライバシーポリシー</a></li>
    <li><a href="{{ route('terms') }}">利用規約</a></li>
  </ul>
  <p class="mt-3 leading-relaxed">{{ config('suiri.notes.footer') }}</p>
  <p class="mt-2">{{ config('app.name') }} — Deduction Notebook</p>
</footer>
