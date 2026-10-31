{{--
  事件の画面の「AI に相談する用に写す」。押すと、なぞ・手がかり・見立て・予想と頼み方の文（正誤は判定せず、ほかの見立てと確かめ方の案を
  出して。名前などは書かないで）を端末のクリップボードに写し、「写したよ。ChatGPT に貼ってね」と出す。どのプラン・どの AI でも使える。
  アプリからは何も送らない（貼るのは本人）。仕事の場面の事件では出さない。相棒モードで見せ合う前は見立てを写さない。
  見立ての段の下（「ほかの説明は？」の近く）に小さく置く。文の組み立ては App\Support\Ai\ConsultText、言葉は config('ai_connect.copy')、
  動きは resources/js/parts/ai-copy.js（JS が動かないときはボタンを隠し、「写す文を見る」を開いて手で写す）
  使い方：<x-case.ai-copy :case="$case" :board="$board" />
  case：事件（手がかり・見立ての ○× と予想を読み込み済みのもの）。board：見せ合いの表（いまは使わない。事件の画面とそろえるために受け取る）
--}}
@props(['case', 'board' => null])
{{-- 仕事の場面の事件では、何も出さない（体のことが混ざりうる記録を、外の AI に貼る流れを作らない） --}}
@unless ($case->scene === \App\Enums\Scene::Work)
  @php($t = config('ai_connect.copy'))
  @php($textId = 'ai-copy-text-'.$case->id)
  <div data-ai-copy {{ $attributes }}>
    <x-ui.button variant="secondary" type="button" data-ai-copy-button data-target="{{ $textId }}" hidden>
      <x-ui.step-icon name="sparkle" class="size-5" />{{ $t['button'] }}
    </x-ui.button>
    <p class="mt-1 text-sm leading-relaxed text-muted">{{ $t['hint'] }}</p>
    <p data-ai-copy-status role="status" aria-live="polite" data-done="{{ $t['done'] }}" data-failed="{{ $t['failed'] }}" class="mt-1 text-base font-bold text-prediction" hidden></p>
    <details data-ai-copy-details>
      <summary class="inline-flex min-h-12 cursor-pointer items-center text-sm text-muted underline">{{ $t['show'] }}</summary>
      <label for="{{ $textId }}" class="sr-only">{{ $t['text_label'] }}</label>
      <textarea id="{{ $textId }}" readonly rows="10" class="w-full rounded-xl border border-rule bg-sheet p-3 text-sm leading-relaxed">{{ \App\Support\Ai\ConsultText::for($case) }}</textarea>
    </details>
  </div>
@endunless
