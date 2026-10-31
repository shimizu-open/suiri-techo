{{--
  まだ何もないときの画面。大きな絵・一言・主なボタン1つだけを出し、「まず何をすればいいか」をひと目でわかるようにする。
  枠で囲まず、左にそろえて置く（ページの中身そのものが「次にすること」になる）
  使い方：<x-ui.empty-state icon="eye" title="まだ事件がないよ" :action-href="route('cases.create')" action-label="最初の事件を作ってみよう">気になることを1つ書くだけでOK</x-ui.empty-state>
  icon：<x-ui.step-icon> の名前（既定 sparkle）。title：大きめの一言。中身（slot）：短い説明（省略可）。
  action-href・action-label：主なボタンのリンク先と文字（どちらか省くとボタンを出さない）。action-icon：ボタンの絵（既定 plus）。
  tone：絵とボタンの色（clue など。既定 plain）。ボタンの代わりにフォームを置きたいときは <x-slot:action> を渡す
--}}
@props(['icon' => 'sparkle', 'title', 'actionHref' => null, 'actionLabel' => null, 'actionIcon' => 'plus', 'tone' => 'plain'])
{{-- 段の種類ごとの絵の丸の色。check は予想と同じ緑 --}}
@php($faces = [
  'clue' => 'bg-clue-soft text-clue',
  'hypothesis' => 'bg-hypothesis-soft text-hypothesis',
  'prediction' => 'bg-prediction-soft text-prediction',
  'check' => 'bg-prediction-soft text-prediction',
  'answer' => 'bg-answer-soft text-answer',
  'plain' => 'bg-mark text-ink',
])
@php($tone = array_key_exists($tone, $faces) ? $tone : 'plain')
<section {{ $attributes->class(['py-6']) }}>
  <span class="grid size-20 place-items-center rounded-full {{ $faces[$tone] }}">
    <x-ui.step-icon :name="$icon" class="size-11" />
  </span>
  <h2 class="mt-4 text-2xl">{{ $title }}</h2>
  {{-- 短い説明が渡されていれば、見出しの下に出す --}}
  @if (trim($slot) !== '')
    <p class="mt-2 max-w-md text-base text-muted">{{ $slot }}</p>
  @endif
  {{-- ボタンの代わりのフォームが渡されていればそれを、なければリンク先と文字があるときだけ主なボタンを出す --}}
  @isset($action)
    <div class="mt-6 max-w-md">{{ $action }}</div>
  @elseif ($actionHref && $actionLabel)
    <div class="mt-6 max-w-md">
      <x-ui.big-button :href="$actionHref" :icon="$actionIcon" :tone="$tone === 'plain' ? 'primary' : $tone">{{ $actionLabel }}</x-ui.big-button>
    </div>
  @endisset
</section>
