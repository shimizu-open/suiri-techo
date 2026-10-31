{{--
  相棒の渡し合いの最後の段：見せ合いの画面。2人の見立てを左右（スマホでは上下）に並べ、手がかりの荷札ごとに、
  それぞれが ○（合う）×（合わない）を付けたかを2人の印で見せる。付け方が分かれた荷札はからし色にして「見方が分かれたよ」と出す。
  勝ち負けではなく、見ていたところの違いを話すためのもの。id="reveal" は見せ合った直後に戻ってくる場所の印（RevealHypothesesController）。
  使い方：<x-case-partner.reveal :case :board :names :word :kid />（board は App\Queries\Cases\RevealBoard::build() の値：partner・self・rows）
--}}
@props(['case', 'board', 'names', 'word', 'kid' => false])
@php($t = config('suiri.case_partner.reveal'))
@php($sides = ['partner' => $board['partner'], 'self' => $board['self']])
@php($split = collect($board['rows'])->where('differs', true)->count())
<section id="reveal" class="scroll-mt-16">
  <x-ui.step-card icon="partner" tone="hypothesis" :title="__($t['title'], ['word' => $word])" :hint="$t['hint']">
    <div class="grid gap-3 sm:grid-cols-2">
      {{-- 相棒と自分の見立てを1つずつの欄にして並べる --}}
      @foreach ($sides as $who => $hypotheses)
        <x-case-partner.side :who="$who" :name="$names[$who]" :word="$word" :hypotheses="$hypotheses" />
      @endforeach
    </div>

    {{-- 手がかりがあれば、荷札ごとに2人の印を並べる。なければ、手がかりを足すと違いがわかることだけを出す --}}
    @if ($board['rows'] !== [])
      <div>
        <div class="flex flex-wrap items-baseline justify-between gap-x-3">
          <h3 class="text-base font-bold text-clue">{{ $t['clues'] }}</h3>
          <p class="text-sm font-bold text-hypothesis">{{ $split > 0 ? __($t['count'], ['n' => $split]) : $t['count_none'] }}</p>
        </div>
        <p class="mt-0.5 text-sm text-muted">{{ __($t['legend'], ['word' => $word]) }}</p>
        <ul class="mt-2 space-y-2">
          {{-- 手がかりを1つずつ荷札にし、2人の印を下に並べる。付け方が分かれた札はからし色にする --}}
          @foreach ($board['rows'] as $row)
            <li>
              <x-ui.evidence-tag :no="$loop->iteration" :tone="$row['differs'] ? 'hypothesis' : 'clue'"
                :label="$kid ? $row['clue']->kind->kidLabel() : $row['clue']->kind->label($case->scene)">
                {{-- 付け方が分かれた札には「見方が分かれたよ」の印を付ける --}}
                @if ($row['differs'])
                  <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-hypothesis px-2.5 py-0.5 text-xs font-bold text-on-ink">
                    <x-ui.step-icon name="question" class="size-4" />{{ $t['differs'] }}
                  </span>
                @endif
                <span class="block break-words">{{ $row['clue']->body }}</span>
                <span class="mt-2 flex flex-wrap gap-2">
                  {{-- 相棒と自分の印を1つずつ出す --}}
                  @foreach (['partner', 'self'] as $who)
                    <x-case-partner.marks :who="$who" :name="$names[$who]" :marks="$row[$who]" :numbered="$sides[$who]->count() > 1" />
                  @endforeach
                </span>
              </x-ui.evidence-tag>
            </li>
          @endforeach
        </ul>
      </div>
    @else
      <p class="text-base text-muted">{{ $t['no_clues'] }}</p>
    @endif

    <x-ui.hint icon="partner" tone="hypothesis">{{ $t['ask'] }}</x-ui.hint>
  </x-ui.step-card>
</section>
