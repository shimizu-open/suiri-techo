{{--
  原作の英語の短い引用と出典。英語の文として読みやすいよう、斜体と左の線で本文と分ける。
  使い方：<x-guide.quote text="You see, but you do not observe.">『ボヘミアの醜聞』A Scandal in Bohemia 第1節</x-guide.quote>
  text：英語の引用（Project Gutenberg の本文のまま）。文の途中から・途中までの引用には、前後に「…」を添えて出す。
  中身（slot）：出典（作品名と章）。<x-guide.work :scene> を入れてもよい
--}}
@props(['text'])
<figure {{ $attributes->class(['border-l-2 border-accent pl-4']) }}>
  <blockquote lang="en" class="font-serif text-lg leading-relaxed italic">“{{ preg_match('/^[a-z]/', $text) ? '…' : '' }}{{ $text }}{{ preg_match('/[.!?]$/', $text) ? '' : '…' }}”</blockquote>
  <figcaption class="mt-1 text-sm text-muted">— {{ $slot }}</figcaption>
</figure>
