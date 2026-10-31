{{--
  横いっぱいの主なボタン（高さ 56px。子どもの指でも押しやすい）。1画面に1つ、いまやることの決め手に使う。
  使い方：<x-ui.big-button tone="clue">これでOK</x-ui.big-button>（フォームの送信）／<x-ui.big-button :href="route('cases.create')" icon="plus">事件を作る</x-ui.big-button>（リンク）
  href：あればリンク（<a>）、なければボタン。type：submit（既定）・button。
  icon：<x-ui.step-icon> の名前（eye・question・arrow・calendar・check など）か、<x-ui.icon> の名前（plus など）。省略可。
  tone：primary（紺・既定）・clue・hypothesis・prediction・check・answer（段の色で塗る）・secondary（枠だけ・控えめな2つ目）
  name・value・disabled などほかの属性は、そのままボタンに付く
--}}
@props(['href' => null, 'type' => 'submit', 'icon' => null, 'tone' => 'primary'])
{{-- 段の種類ごとの塗りの組み合わせ。段の色の上の文字は on-ink（昼も夜も読める濃さ）。check は予想と同じ緑 --}}
@php($tones = [
  'primary' => 'border-ink bg-ink text-on-ink hover:opacity-90',
  'clue' => 'border-clue bg-clue text-on-ink hover:opacity-90',
  'hypothesis' => 'border-hypothesis bg-hypothesis text-on-ink hover:opacity-90',
  'prediction' => 'border-prediction bg-prediction text-on-ink hover:opacity-90',
  'check' => 'border-prediction bg-prediction text-on-ink hover:opacity-90',
  'answer' => 'border-answer bg-answer text-on-ink hover:opacity-90',
  'secondary' => 'border-ink bg-sheet text-ink hover:bg-mark',
])
@php($stepIcons = ['eye', 'question', 'arrow', 'calendar', 'check', 'book', 'attic', 'norbury', 'partner', 'sparkle'])
<{{ $href ? 'a' : 'button' }} @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif {{ $attributes->class([
  'flex min-h-14 w-full items-center justify-center gap-2 rounded-xl border-2 px-5 py-3 text-center text-lg leading-snug font-bold no-underline cursor-pointer disabled:cursor-not-allowed disabled:opacity-50',
  $tones[$tone] ?? $tones['primary'],
]) }}>
  {{-- 絵が渡されていれば、段の絵か、ふつうの絵（plus など）を文字の前に出す --}}
  @if ($icon)
    {{-- 段の絵の名前なら段の絵を、そうでなければふつうの絵（<x-ui.icon>）を使う --}}
    @if (in_array($icon, $stepIcons, true))
      <x-ui.step-icon :name="$icon" class="size-6" />
    @else
      <x-ui.icon :name="$icon" class="size-6" />
    @endif
  @endif
  <span>{{ $slot }}</span>
</{{ $href ? 'a' : 'button' }}>
