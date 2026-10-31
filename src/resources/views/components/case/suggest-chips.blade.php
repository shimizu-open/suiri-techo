{{--
  提案のボタン（チップ）の並び（requirements.md の 4-10 S-1〜S-5。AI なし）。押すと同じフォームの欄に書き出しが入るだけで、保存は本人がする。
  動きは resources/js/parts/suggest-chips.js（JavaScript が動かなくても、フォームはふつうに書いて送れる）。候補は props で受け取るだけ。
  押しても何も起きないボタンを見せないよう、並びは初めは隠しておき（hidden・data-suggest-chips）、JavaScript が動いたら見せる。
  使い方：<x-case.suggest-chips :items="$questions" target="f-add-clue-body" caption="書き出し" />
  items の1件：label（ボタンの言葉）・hint（後ろに薄く出す問いかけ。任意）と、押したときにすること（どれも任意）：
    text（target の入力欄に足す書き出し）・kind（手がかりの種類を切り替える）・date と time（確かめる日と時刻）・attic（使う屋根裏の知識を選ぶ）。
  target：書き出しを入れる入力欄の id（なければ text は入れない）。folded：caption の1行にたたむ（押すと開く）。候補が空なら何も出さない
--}}
@props(['items' => [], 'target' => null, 'caption' => null, 'folded' => false])
{{-- 候補があるときだけ並べる --}}
@if (count($items) > 0)
  <details hidden data-suggest-chips @if (! $folded) open @endif data-suggest-target="{{ $target }}" {{ $attributes->class(['group/suggest text-xs']) }}>
    <summary @class(['flex min-h-8 cursor-pointer items-center gap-1 font-semibold text-muted', 'hidden' => ! $folded])>
      <span aria-hidden="true" class="group-open/suggest:rotate-90">›</span>{{ $caption }}
    </summary>
    <div @class(['flex flex-wrap items-center gap-1.5', 'mt-1' => $folded])>
      {{-- たたんでいないときは、見出しをボタンの頭に小さく置く --}}
      @if (! $folded && filled($caption))
        <span class="font-semibold text-muted">{{ $caption }}</span>
      @endif
      {{-- 候補を1つずつ、押せるボタンにする（押すと入力欄に入るだけで、送信はしない） --}}
      @foreach ($items as $item)
        <button type="button" data-suggest data-text="{{ $item['text'] ?? '' }}" data-kind="{{ $item['kind'] ?? '' }}"
          data-date="{{ $item['date'] ?? '' }}" data-time="{{ $item['time'] ?? '' }}" data-attic="{{ $item['attic'] ?? '' }}"
          class="inline-flex min-h-8 max-w-full cursor-pointer items-center gap-1 rounded-full border border-rule bg-sheet px-2.5 text-left font-semibold text-ink hover:bg-mark">
          <span class="truncate">{{ $item['label'] }}</span>
          {{-- 問いかけがあれば、言葉の後ろに薄く添える --}}
          @if (filled($item['hint'] ?? null))
            <span class="font-normal text-muted">{{ $item['hint'] }}</span>
          @endif
        </button>
      @endforeach
    </div>
  </details>
@endif
