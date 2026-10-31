{{--
  「ChatGPT・Claude とつなぐ」の枠の、つないでいる AI の1行。名前（AI が名乗ったもの）・戻る先の住所・最後につながった日と、
  「つなぐのをやめる」（押すと確かめの文が開く <x-ui.confirm>。DELETE /settings/ai/{app}）。
  使い方：<x-settings.ai-app-row :app="$app" />（$app は App\Queries\Ai\ConnectedAppsQuery の1行：id・name・host・last_at）
--}}
@props(['app'])
@php($p = config('ai_connect.panel'))
<li class="py-3">
  <p class="text-base font-bold">{{ $app['name'] }}</p>
  <p class="font-mono text-sm text-muted">{{ $app['host'] }}</p>
  <p class="text-sm text-muted">{{ $p['last'] }}：{{ $app['last_at']->format('Y年n月j日') }}</p>
  <x-ui.confirm :action="route('settings.ai.revoke', $app['id'])" :label="$p['stop']" :confirmLabel="$p['stop_confirm']"
    :message="strtr($p['stop_message'], [':name' => $app['name']])" class="mt-1" />
</li>
