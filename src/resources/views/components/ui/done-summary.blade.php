{{--
  済んだことを小さくたたんだ要約。押すと一覧が開く（JavaScript なしで動く <details>）。いまやることのカードの上に置き、済んだ段を短く見せる。
  カードで囲まず、下に細い線を引いた1行にする（目立つ枠は「いまやること」のカードだけに使う）。何行か重ねると、済んだ段の目次になる
  使い方：<x-ui.done-summary title="見たこと 3つ" icon="eye" tone="clue" :items="['体操着を2回わすれた', '…']" :edit-href="route('cases.show', $case)" />
  title：たたんだときの一行。items：開いたときの一覧（文字の配列。['text' => …] の形でもよい）。
  edit-href：「直す」のリンク先（省略可）。edit-label：そのリンクの文字（既定「直す」）。
  icon：<x-ui.step-icon> の名前（既定 check）。tone：段の色（clue など。既定 plain）。:open="true" で開いて出す
--}}
@props(['title', 'items' => [], 'editHref' => null, 'editLabel' => null, 'icon' => 'check', 'tone' => 'plain', 'open' => false])
{{-- 段の種類ごとの色の組み合わせ（face：絵の丸／dot：一覧の点）。check は予想と同じ緑 --}}
@php($tones = [
  'clue' => ['face' => 'bg-clue-soft text-clue', 'dot' => 'bg-clue'],
  'hypothesis' => ['face' => 'bg-hypothesis-soft text-hypothesis', 'dot' => 'bg-hypothesis'],
  'prediction' => ['face' => 'bg-prediction-soft text-prediction', 'dot' => 'bg-prediction'],
  'check' => ['face' => 'bg-prediction-soft text-prediction', 'dot' => 'bg-prediction'],
  'answer' => ['face' => 'bg-answer-soft text-answer', 'dot' => 'bg-answer'],
  'plain' => ['face' => 'bg-mark text-ink', 'dot' => 'bg-ink'],
])
@php($c = $tones[$tone] ?? $tones['plain'])
<details @if ($open) open @endif {{ $attributes->class(['group border-b border-rule']) }}>
  <summary class="flex min-h-12 cursor-pointer items-center gap-3 py-2">
    <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $c['face'] }}">
      <x-ui.step-icon :name="$icon" class="size-5" />
    </span>
    <span class="min-w-0 flex-1 truncate font-semibold">{{ $title }}</span>
    <span class="flex shrink-0 items-center gap-1 text-sm text-muted">
      <span class="sr-only">{{ config('suiri.ui.summary_open') }}</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
    </span>
  </summary>
  <div class="pb-3 pl-11">
    <ul class="space-y-1.5">
      {{-- 済んだことを1つずつ取り出して、点つきの行で並べる --}}
      @foreach ($items as $item)
        <li class="flex gap-2 text-base leading-relaxed">
          <span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full {{ $c['dot'] }}"></span>
          <span class="min-w-0">{{ is_array($item) ? ($item['text'] ?? $item['label'] ?? '') : $item }}</span>
        </li>
      @endforeach
    </ul>
    {{-- 直す画面のリンク先が渡されていれば、一覧の下に出す --}}
    @if ($editHref)
      <a href="{{ $editHref }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-semibold">{{ $editLabel ?? config('suiri.ui.summary_edit') }}</a>
    @endif
  </div>
</details>
