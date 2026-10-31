{{--
  いまやることのカード「見立てを見直そう」（済んだ段の「見直す・○× をつける」から来たとき）。見立てごとに、状態を選ぶ大きなボタン・
  ○× をつける・もしそうならを立てる・本文を直す／消す（components/case-step/hyp-row）。下に戻るリンク。
  使い方：<x-case-step.edit-hyps-card :case :hypotheses :partner-name :kid />（hypotheses はいま見えている見立て）
--}}
@props(['case', 'hypotheses', 'partnerName' => null, 'kid' => false])
@php($w = config('suiri.case_board.cards'))
<x-ui.step-card icon="question" tone="hypothesis" :title="$w['edit_hyps']['title']" :hint="$w['edit_hyps']['hint']">
  <ul class="space-y-4">
    {{-- 見立てを書いた順に1つずつ、見直す行にする --}}
    @foreach ($hypotheses as $hypothesis)
      <x-case-step.hyp-row :hypothesis="$hypothesis" :case="$case" :partner-name="$partnerName" :kid="$kid" />
    @endforeach
  </ul>
  <x-slot:later><a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $w['back'] }}</a></x-slot:later>
</x-ui.step-card>
