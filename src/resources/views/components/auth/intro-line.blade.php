{{--
  トップ（ログイン前）で、手帳でできることを1行ずつ見せる部品：段の色の丸に段の絵、その横に文。
  3行の要点（見たことを事件に → どうして？を考える → 確かめる）と、下の「手帳の使い方」の手順の両方で使う。
  使い方：<x-auth.intro-line icon="eye" tone="clue">気になったことを「事件」にして、</x-auth.intro-line>
          <x-auth.intro-line icon="eye" tone="clue" :number="1" title="見たことを書く">手がかり＝…</x-auth.intro-line>
  icon：<x-ui.step-icon> の名前。tone：clue・hypothesis・prediction・answer・plain。number：手順の番号（順番があるときだけ）。title：太字の見出し（省略可）
--}}
@props(['icon', 'tone' => 'plain', 'number' => null, 'title' => null])
{{-- 段の種類ごとの丸の色の組み合わせ --}}
@php($faces = [
  'clue' => 'bg-clue-soft text-clue',
  'hypothesis' => 'bg-hypothesis-soft text-hypothesis',
  'prediction' => 'bg-prediction-soft text-prediction',
  'answer' => 'bg-answer-soft text-answer',
  'plain' => 'bg-mark text-ink',
])
<li {{ $attributes->class(['flex items-start gap-3']) }}>
  <span class="relative grid size-10 shrink-0 place-items-center rounded-full {{ $faces[$tone] ?? $faces['plain'] }}">
    <x-ui.step-icon :name="$icon" class="size-6" />
    {{-- 手順の番号があれば、丸の右下に小さく添える --}}
    @if ($number)
      <span class="absolute -right-1 -bottom-1 grid size-5 place-items-center rounded-full bg-ink text-xs font-bold text-on-ink tabular-nums">{{ $number }}</span>
    @endif
  </span>
  <div class="min-w-0 pt-1.5">
    {{-- 見出しがあれば太字で出し、文はその下に小さめに出す。なければ文だけを大きめに出す --}}
    @if ($title)
      <p class="text-base leading-snug font-bold">{{ $title }}</p>
      <p class="mt-0.5 text-sm leading-relaxed text-muted">{{ $slot }}</p>
    @else
      <p class="text-lg leading-snug font-bold">{{ $slot }}</p>
    @endif
  </div>
</li>
