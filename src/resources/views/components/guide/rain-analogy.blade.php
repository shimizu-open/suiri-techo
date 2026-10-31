{{--
  3つの考え方の入口に置く、身近なたとえ（朝、道が濡れていたら？）。雨雲と水たまりの絵と、同じ場面での3つの考え方を1行ずつ。
  父（大人）にも子どもにも同じたとえで伝わるよう、向きの切り替えに関係なく出す。
  使い方：<x-guide.rain-analogy :analogy="$reasoning['analogy']" :items="$reasoning['items']" />
  analogy：reasoning.json の analogy（title・fact・steps・note）。items：reasoning.json の items（名前・絵・色をたとえの行に使う）
--}}
@props(['analogy', 'items'])
{{-- 段の色ごとの、絵の丸の色 --}}
@php($dots = ['hypothesis' => 'bg-hypothesis', 'prediction' => 'bg-prediction', 'answer' => 'bg-answer', 'clue' => 'bg-clue'])
<section aria-labelledby="analogy-title" {{ $attributes->class(['rounded-2xl border-2 border-clue/40 bg-clue-soft px-4 py-4']) }}>
  <div class="flex items-center gap-3">
    {{-- 雨雲と雨つぶと水たまりの絵（手で描いた線の絵） --}}
    <svg viewBox="0 0 64 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="h-14 w-18 shrink-0 text-clue">
      <path d="M17 24h28a8 8 0 0 0 0-16 11 11 0 0 0-21-2 9 9 0 0 0-7 18z"/>
      <path d="M22 30l-2 5M32 30l-2 5M42 30l-2 5"/>
      <path d="M10 43c6-3 12-3 18 0s12 3 18 0 8-2 10-1"/>
    </svg>
    <h2 id="analogy-title" class="text-xl leading-snug">{{ $analogy['title'] }}</h2>
  </div>
  <p class="mt-2 flex items-start gap-2 font-semibold">
    <x-ui.step-icon name="eye" class="mt-0.5 size-6 shrink-0 text-clue" /><span>{{ $analogy['fact'] }}</span>
  </p>
  <ol class="mt-3 space-y-3">
    {{-- 3つの考え方を流れの順に1つずつ、絵・名前・たとえの1行にする --}}
    @foreach ($items as $item)
      <li class="flex items-start gap-3">
        <span @class(['grid size-9 shrink-0 place-items-center rounded-full text-on-ink', $dots[$item['tone']] ?? 'bg-ink'])>
          <x-ui.step-icon :name="$item['icon']" class="size-5" />
        </span>
        <div class="min-w-0">
          <a href="#{{ $item['key'] }}" class="text-sm font-bold">{{ $item['name'] }}</a>
          <p>{{ $analogy['steps'][$item['key']] ?? '' }}</p>
        </div>
      </li>
    @endforeach
  </ol>
  <p class="mt-3 border-t border-clue/30 pt-3 text-base">{{ $analogy['note'] }}</p>
</section>
