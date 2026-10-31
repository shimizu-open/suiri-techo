{{--
  ボタン。使い方：<x-ui.button>保存する</x-ui.button>（フォームの送信）／<x-ui.button href="..." variant="secondary">戻る</x-ui.button>（リンク）
  variant：primary（紺で塗る・主な操作）・secondary（枠だけ）・ghost（枠なし）・danger（深い赤・消す）
  size：md（高さ48px・子どもの指でも押しやすい）・sm（高さ40px）。block：横いっぱいにする。type：button・submit（既定）
--}}
@props(['variant' => 'primary', 'href' => null, 'type' => 'submit', 'size' => 'md', 'block' => false])
{{-- href があればリンク（<a>）、なければボタン（<button>）として出す --}}
<{{ $href ? 'a' : 'button' }} @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif {{ $attributes->class([
  'inline-flex items-center justify-center gap-2 rounded-xl border font-semibold no-underline cursor-pointer disabled:cursor-not-allowed disabled:opacity-50',
  'min-h-12 px-5 text-base' => $size === 'md',
  'min-h-10 px-3 text-sm' => $size === 'sm',
  'w-full' => $block,
  'border-ink bg-ink text-on-ink hover:opacity-90' => $variant === 'primary',
  'border-rule bg-sheet text-ink hover:border-ink' => $variant === 'secondary',
  'border-transparent bg-transparent text-ink hover:bg-mark' => $variant === 'ghost',
  'border-accent bg-accent text-on-ink hover:opacity-90' => $variant === 'danger',
]) }}>{{ $slot }}</{{ $href ? 'a' : 'button' }}>
