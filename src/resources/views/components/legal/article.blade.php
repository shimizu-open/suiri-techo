{{--
  文章のページ（プライバシーポリシー・利用規約・このアプリについて）の本文の組み方。見出し（h2・h3）・段落・箇条書き・リンクを
  読みやすい間隔にする（Tailwind の子要素向けのクラスで書く。CSS の独自のクラスは使わない）。
  使い方：<x-legal.article> <p>…</p><h2>…</h2><ul>…</ul> </x-legal.article>
--}}
<article {{ $attributes->class([
  'text-sm leading-relaxed',
  '[&_h2]:mt-8 [&_h2]:mb-2 [&_h2]:border-b [&_h2]:border-rule [&_h2]:pb-1 [&_h2]:text-lg',
  '[&_h3]:mt-5 [&_h3]:mb-1 [&_h3]:text-base',
  '[&_p]:mt-2 [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:mt-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_li+li]:mt-1 [&_a]:underline',
]) }}>
  {{ $slot }}
</article>
