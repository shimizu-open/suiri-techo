{{--
  いまやることのカード「答え合わせをしよう」（確かめる日が来た予想）。前に書いた予想の文と、書いた日・自信をそのまま見せ、
  主なボタンで答え合わせの画面（predictions.show。結果を選ぶ所）へ進む。主なボタンの下に、言葉の説明かワトソンのひとことを1つ添える。
  使い方：<x-case-step.resolve-card :case :prediction :kid :number :total :note />
--}}
@props(['case', 'prediction', 'kid' => false, 'number' => null, 'total' => null, 'note' => null])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
<x-ui.step-card :step="$number" :total="$total" icon="check" tone="answer" :title="$t('resolve.title')" :hint="$t('resolve.hint')">
  <blockquote class="rounded-xl bg-answer-soft px-4 py-3">
    <p class="text-sm font-bold text-answer">{{ $kid ? \App\Support\Label::kidWord('prediction') : \App\Support\Label::word('prediction', $case->scene) }}</p>
    <p class="text-lg leading-relaxed font-semibold break-words">{{ $prediction->statement }}</p>
    <p class="mt-1 text-sm text-muted">
      {{ $t('resolve.written', ['date' => $prediction->created_at->isoFormat('M月D日')]) }}・{{ $t('resolve.confidence', ['value' => $prediction->confidence]) }}
    </p>
  </blockquote>
  <x-ui.big-button :href="route('predictions.show', $prediction)" tone="answer" icon="check">{{ $t('resolve.button') }}</x-ui.big-button>
  <x-case-step.bubble :note="$note" :case="$case" :explain="$t('resolve.explain')" />
</x-ui.step-card>
