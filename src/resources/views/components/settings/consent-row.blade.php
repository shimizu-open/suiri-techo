{{--
  カレンダーとつなぐ前の説明の1行（見出しと説明）。<dl> の中に並べる。見出しを太字で文の頭に置き、説明を同じ行から続けて書く
  （横に2列で並べるより行が少なくなり、「同意して Google へ進む」がスマホでもスクロールせずに見える高さに収まる）。
  使い方：<x-settings.consent-row label="送るもの">タスクの題名と日時と…</x-settings.consent-row>
  label：頭の見出し。中身（slot）：説明の文
--}}
@props(['label'])
<div {{ $attributes->class(['py-2']) }}>
  <dt class="inline font-bold text-ink">{{ $label }}：</dt>
  <dd class="inline text-muted">{{ $slot }}</dd>
</div>
