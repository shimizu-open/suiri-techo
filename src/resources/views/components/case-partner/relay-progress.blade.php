{{--
  相棒の渡し合いの進み具合（丸3つ：相棒の番 → 自分の番 → 見せ合う）。いまだれの番で、次にだれへ渡すかを見せる。
  使い方：<x-case-partner.relay-progress at="self" :names="$names" :partner-done="true" />
  at：いまの段（partner・self・reveal）。names：2人の呼び名。partner-done：相棒がもう書いたか（自分の番の画面で、1つ目の丸を塗るか）
--}}
@props(['at', 'names', 'partnerDone' => false])
<x-ui.progress tone="hypothesis" :count="false" :steps="[
  ['label' => $names['partner'], 'current' => $at === 'partner', 'done' => $at !== 'partner' && $partnerDone],
  ['label' => $names['self'], 'current' => $at === 'self', 'done' => $at === 'reveal'],
  ['label' => config('suiri.case_partner.reveal_step'), 'current' => $at === 'reveal'],
]" {{ $attributes }} />
