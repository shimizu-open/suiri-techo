{{--
  「ChatGPT・Claude とつなぐ」の枠の、つなぎ方の手順（たたんで置き、押すと開く。JavaScript なしで動く <details>）。
  手順は順番があるので番号を付ける。画面の項目名は英語のまま（Settings・Connectors など）。
  使い方：<x-settings.ai-steps :title="$p['chatgpt_title']" :steps="$p['chatgpt_steps']" :note="$p['chatgpt_plan']" />
  title：見出し。steps：手順の文の一覧。note：手順の下の一言（省略可）
--}}
@props(['title', 'steps', 'note' => null])
<details {{ $attributes->class(['group']) }}>
  <summary class="flex min-h-12 cursor-pointer items-center justify-between gap-2 text-base font-bold">
    <span>{{ $title }}</span>
    <span aria-hidden="true" class="text-muted group-open:rotate-180">▾</span>
  </summary>
  <ol class="mb-3 space-y-2 text-base">
    {{-- 手順を1つずつ、丸い番号つきで並べる --}}
    @foreach ($steps as $i => $step)
      <li class="flex items-start gap-3">
        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-mark text-sm font-bold">{{ $i + 1 }}</span>
        <span class="leading-relaxed">{{ $step }}</span>
      </li>
    @endforeach
  </ol>
  {{-- 手順の下の一言があれば出す（プランによってはつなげない、など） --}}
  @if ($note)
    <p class="mb-3 text-sm leading-relaxed text-muted">{{ $note }}</p>
  @endif
</details>
