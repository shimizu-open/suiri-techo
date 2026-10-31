{{--
  ふりかえり・ノーベリ帳・屋根裏の棚を行き来する切り替え（3つの画面の上に置く）。ナビの「ふりかえり」から入った先の中の目次。
  捜査ファイルの見出しのつまみの形：下の罫線の上につまみを並べ、いまの画面のつまみだけ紙の色で手前に出す（罫線を切って前に見える）。
  絵と名前を横に並べ、高さを抑える。子ども向けの呼び名（まちがいノート）は長いので、スマホでは絵の下に少し小さな字で名前を置く。親子の場面では、子ども向けの呼び名（まちがいノート・図鑑）で出す。
  使い方：<x-review.tabs current="norbury" :kid="$kid" />。current：review・norbury・attic（いまの画面に印を付ける）
--}}
@props(['current' => 'review', 'kid' => false])
@php($tabs = [
  'review' => [route('review.show'), \App\Support\Label::word('review'), 'sparkle'],
  'norbury' => [route('norbury.index'), $kid ? \App\Support\Label::kidWord('norbury') : \App\Support\Label::word('norbury'), 'norbury'],
  'attic' => [route('attic.index'), $kid ? config('suiri.review.attic.kid_short') : \App\Support\Label::word('attic'), 'attic'],
])
<nav aria-label="ふりかえりの中の切り替え" {{ $attributes->class(['mb-4 grid grid-cols-3 items-end gap-1.5 border-b-2 border-ink']) }}>
  {{-- 3つの画面を1つずつ取り出してつまみにする。いまの画面は紺の太い枠で手前に、ほかは奥に引っこめて薄く出す --}}
  @foreach ($tabs as $key => [$href, $label, $icon])
    <a href="{{ $href }}" @if ($key === $current) aria-current="page" @endif @class([
      'flex items-center justify-center gap-1 rounded-t-xl border-2 border-b-0 px-1 text-center leading-tight font-bold no-underline',
      'text-sm' => ! $kid,
      'flex-col text-xs sm:flex-row sm:text-sm' => $kid,
      '-mb-0.5 min-h-12 border-ink bg-paper py-2 text-ink' => $key === $current,
      'min-h-11 border-rule bg-mark py-1.5 text-muted hover:text-ink' => $key !== $current,
    ])><x-ui.step-icon :name="$icon" class="size-5" /><span>{{ $label }}</span></a>
  @endforeach
</nav>
