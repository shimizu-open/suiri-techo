{{--
  相棒の渡し合いで、書いたあとに出すカード：いま書いた見立てを見せ、手がかりの荷札ごとに ○（合う）×（合わない）を付けてもらう。
  ○× は見せ合いの画面で「見ていたところの違い」を出すのに使う（付けなくても進める）。主なボタン（渡す・よぶ）は中身（slot）で受け取る。
  使い方：<x-case-partner.mark-card :case :who :names :word :kid :step :hypotheses="$mine" more="#relay-partner-write"><x-ui.big-button …/></x-case-partner.mark-card>
  hypotheses：その人の見立て（書いた順。○× を付けるのは最後に書いたもの）。more：「ほかの理由も書く」の行き先
--}}
@props(['case', 'who', 'names', 'word', 'kid' => false, 'step' => 1, 'hypotheses', 'more'])
@php($t = config('suiri.case_partner.mark'))
@php($latest = $hypotheses->last())
@php($hasClues = $case->clues->isNotEmpty())
{{-- 2つ以上書いていれば、書いた見立ての一覧を小さくたたんで上に置く --}}
@if ($hypotheses->count() > 1)
  <x-ui.done-summary :title="__($t['written'], ['word' => $word, 'n' => $hypotheses->count()])" icon="question" tone="hypothesis"
    :items="$hypotheses->pluck('body')->all()" />
@endif
<x-ui.step-card id="hyp-{{ $latest->id }}" class="scroll-mt-16" :step="$step" :total="3" icon="question" tone="hypothesis"
  :title="$hasClues ? $t['title'] : $t['title_no_clues']" :hint="$hasClues ? $t['hint'] : $t['hint_no_clues']">
  <div>
    <p class="text-sm font-bold text-hypothesis">{{ __($t['mine'], ['name' => $names[$who], 'word' => $word]) }}</p>
    <p class="mt-1 text-lg leading-snug font-bold break-words">{{ $latest->body }}</p>
  </div>
  {{-- 手がかりがあれば、荷札ごとに ○× のボタンを付ける --}}
  @if ($hasClues)
    <ul class="space-y-2">
      {{-- 手がかりを1つずつ荷札にし、いま書いた見立てへの印のボタンを右に置く --}}
      @foreach ($case->clues as $clue)
        <li>
          <x-ui.evidence-tag :no="$loop->iteration" :label="$kid ? $clue->kind->kidLabel() : $clue->kind->label($case->scene)">
            <span class="break-words">{{ $clue->body }}</span>
            <x-slot:actions>
              <x-case-partner.fit-buttons :hypothesis="$latest" :clue="$clue" />
            </x-slot:actions>
          </x-ui.evidence-tag>
        </li>
      @endforeach
    </ul>
  @endif
  <x-slot:action>{{ $slot }}</x-slot:action>
  <x-slot:later>
    <a href="{{ $more }}" class="inline-flex min-h-11 items-center font-semibold">{{ $t['more'] }}</a>
  </x-slot:later>
</x-ui.step-card>
