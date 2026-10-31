{{--
  ノーベリ帳の1件（外れた予想）：事件のなぞ・予想の文（書いたときのまま）・何を見落とした？の札・書いた日と確かさ・一言と、
  右に「外れ」のはんこ（<x-ui.stamp>。結果を形でも見せる）。一覧の1件なので枠で囲まず、一覧の側の罫線で区切る。
  使い方：<x-review.miss-item :prediction="$prediction" :kid="$kid" />。prediction：外れた Prediction（caseFile を読み込み済み）
--}}
@props(['prediction', 'kid' => false])
<li {{ $attributes->class(['flex items-start gap-3 py-4']) }}>
  <div class="min-w-0 flex-1">
    <p class="text-sm text-muted"><x-review.case-link :case="$prediction->caseFile" class="text-muted" /></p>
    <p class="mt-1 text-base leading-snug font-bold">「{{ $prediction->statement }}」</p>
    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm text-muted">
      {{-- 見落としの種類があればその札、なければ「選ばなかった」の札 --}}
      @if ($prediction->miss_reason)
        <x-ui.badge tone="ink">{{ $kid ? $prediction->miss_reason->kidLabel() : $prediction->miss_reason->label() }}</x-ui.badge>
      @else
        <x-ui.badge>見落としは選ばなかった</x-ui.badge>
      @endif
      <span>{{ $prediction->created_at->format('n月j日') }}に書いた</span>
      <span>{{ $kid ? '自信' : '確かさ' }} {{ $prediction->confidence }}％</span>
    </div>
    {{-- 答え合わせの一言があれば出す --}}
    @if (filled($prediction->result_note))
      <p class="mt-2 text-base"><span class="mr-2 text-sm text-muted">ひとこと</span>{{ $prediction->result_note }}</p>
    @endif
  </div>
  <x-ui.stamp result="miss" size="sm" :label="$kid ? \App\Enums\PredictionResult::Miss->kidLabel() : null" />
</li>
