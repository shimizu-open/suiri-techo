{{--
  設定の画面の「ChatGPT・Claude とつなぐ」の枠。自分の ChatGPT・Claude から、推理手帳の記録を読んで相談できるようにする。
  上から：何ができるか（読むだけ・仕事の場面は見せない・AI に正誤を決めさせない）→ つないでいる AI（1つずつ「つなぐのをやめる」）→
  窓口の住所（写すボタンつき）と、ChatGPT・Claude のつなぎ方（たたんだ手順。画面の英語の項目名のまま）→ 渡った記録の扱いの注意。
  すぐ上のカレンダーの枠とそろえて、枠で囲まず上の罫線で区切る。日本語は文節で折り返す（1文字だけ次の行に残さない）。文は config('ai_connect.panel')
  使い方：<x-settings.ai-panel :apps="$aiApps" class="mt-8" />（$aiApps は App\Queries\Ai\ConnectedAppsQuery::for($user)）
  apps：つないでいる AI の一覧（id・name・host・last_at）。mcp-url：窓口の住所（省略すると このアプリの /mcp）
--}}
@props(['apps' => [], 'mcpUrl' => url('/mcp')])
@php($p = config('ai_connect.panel'))
<section aria-labelledby="ai-panel-title" {{ $attributes->class(['border-t border-rule pt-4 [word-break:auto-phrase]']) }}>
  <div class="flex items-start gap-3">
    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-hypothesis-soft text-hypothesis"><x-ui.step-icon name="sparkle" class="size-6" /></span>
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <h2 id="ai-panel-title" class="text-base">{{ $p['title'] }}</h2>
        {{-- つないでいる AI があれば、その数を緑の札で。なければ「つないでいません」 --}}
        @if ($apps !== [])
          <x-ui.badge tone="ok">{{ strtr($p['count'], [':n' => count($apps)]) }}</x-ui.badge>
        @else
          <x-ui.badge>{{ $p['not_connected'] }}</x-ui.badge>
        @endif
      </div>
      <p class="mt-1 text-base">{{ $p['lead'] }}</p>
    </div>
  </div>

  <ul class="mt-3 space-y-1 text-base">
    {{-- できることを1つずつ、チェックの絵つきで並べる --}}
    @foreach ($p['can'] as $item)
      <li class="flex items-start gap-2"><x-ui.step-icon name="check" class="mt-1 size-4 shrink-0 text-prediction" /><span>{{ $item }}</span></li>
    @endforeach
  </ul>
  <p class="mt-2 text-sm leading-relaxed text-muted">{{ $p['limits'] }}</p>

  {{-- つないでいる AI があるときだけ、一覧と「つなぐのをやめる」を出す --}}
  @if ($apps !== [])
    <h3 class="mt-4 text-base">{{ $p['connected_title'] }}</h3>
    <ul class="mt-1 divide-y divide-rule border-y border-rule">
      {{-- つないでいる AI を1つずつ、名前・戻る先・最後につながった日と「やめる」の行にする --}}
      @foreach ($apps as $app)
        <x-settings.ai-app-row :app="$app" />
      @endforeach
    </ul>
  @endif

  <div data-ai-copy class="mt-4">
    <label for="ai-mcp-url" class="text-sm font-bold">{{ $p['address_label'] }}</label>
    <div class="mt-1 flex flex-wrap items-center gap-2">
      <input id="ai-mcp-url" type="text" readonly value="{{ $mcpUrl }}" class="min-h-12 w-full min-w-0 rounded-xl border border-rule bg-paper px-3 font-mono text-base sm:w-auto sm:flex-1">
      <x-ui.button variant="secondary" type="button" data-ai-copy-button data-target="ai-mcp-url" hidden>{{ $p['copy'] }}</x-ui.button>
    </div>
    <p data-ai-copy-status role="status" aria-live="polite" data-done="{{ $p['copied'] }}" class="mt-1 text-sm font-bold text-prediction" hidden></p>
  </div>

  <div class="mt-3 divide-y divide-rule border-y border-rule">
    <x-settings.ai-steps :title="$p['chatgpt_title']" :steps="$p['chatgpt_steps']" :note="$p['chatgpt_plan']" />
    <x-settings.ai-steps :title="$p['claude_title']" :steps="$p['claude_steps']" />
  </div>
  <p class="mt-2 text-xs text-muted">{{ $p['names_change'] }}</p>
  <p class="mt-2 text-sm leading-relaxed text-muted">{{ $p['note'] }}</p>
</section>
