{{--
  名推理カードの裏。上から：たたんだ「表の手がかりをもう一度読む」→ 自分の考え（付箋）→ カードの裏「ホームズはこう考えた」（見えたこと・考えたこと・だから を段の絵つきで）
  → 外した場面のひとこと → 「どうやって確かめた？」→「この場面で練習できるわざ」→ 原作の英語の引用。
  使い方：<x-guide.scene-back :scene="$scene" :attempt="$attempt" :steps="$steps" :skill-names="$skillNames" :mistake-note="$mistakeNote" :number="7" :total="23" />
  scene：scenes.json の1件（全部の欄）。attempt：自分の推理（なければ null）。steps：ホームズの推理を段に分けたもの（ReasoningSteps::split）。
  skillNames：技能の名前の対応。mistakeNote：ホームズが外した場面に添えるひとこと（overview.json）。number・total：何枚目か・全部で何枚か
--}}
@props(['scene', 'attempt' => null, 'steps' => [], 'skillNames' => [], 'mistakeNote' => '', 'number' => null, 'total' => null])
@php($words = config('suiri.guide.back'))
{{-- 推理の段の見出し（事実・推理・結論）ごとの、やわらかい言葉・絵・色 --}}
@php($looks = ['事実' => ['eye', 'text-clue'], '推理' => ['question', 'text-hypothesis'], '結論' => ['arrow', 'text-prediction']])
<div {{ $attributes->class(['space-y-5']) }}>
  <details class="rounded-xl border border-rule bg-sheet px-4">
    <summary class="flex min-h-12 cursor-pointer items-center font-semibold text-muted">{{ $words['clue_again'] }}</summary>
    <p class="pb-3 leading-relaxed">{{ $scene['front'] }}</p>
  </details>

  <div class="rounded-lg bg-hypothesis-soft px-4 py-3">
    <p class="text-sm font-bold text-hypothesis">{{ $words['mine'] }}</p>
    {{-- 書いた考えがあれば書いた日と一緒に、なければ表にもどって書けることを出す --}}
    @if ($attempt)
      <p class="mt-1 whitespace-pre-line">{{ $attempt->answer }}</p>
      <p class="mt-1 flex flex-wrap items-center justify-between gap-x-3 text-sm text-muted">
        <span>{{ __($words['mine_date'], ['date' => $attempt->updated_at->format('n月j日')]) }}</span>
        <a href="{{ route('guide.scenes.show', $scene['key']) }}" class="inline-flex min-h-11 items-center font-semibold text-ink">{{ $words['mine_edit'] }}</a>
      </p>
    @else
      <p class="mt-1">{{ $words['mine_none'] }}</p>
      <a href="{{ route('guide.scenes.show', $scene['key']) }}" class="inline-flex min-h-11 items-center text-sm font-semibold">{{ $words['mine_write'] }}</a>
    @endif
  </div>

  <x-guide.card-face :number="$number" :total="$total" side="back">
    {{-- ホームズが外した場面なら、右上に赤いはんこを押す --}}
    @if ($scene['is_mistake'])
      <x-slot:corner><x-guide.stamp tone="accent">{{ config('suiri.guide.scenes.mistake') }}</x-guide.stamp></x-slot:corner>
    @endif
    <h3 class="text-xl">{{ $words['holmes'] }}</h3>
    <ol class="mt-3 space-y-3">
      {{-- 推理を段（見えたこと・考えたこと・だから）ごとに、絵と見出しを付けて並べる --}}
      @foreach ($steps as $step)
        @php([$icon, $color] = $looks[$step['label']] ?? ['sparkle', 'text-ink'])
        <li class="flex gap-3">
          <x-ui.step-icon :name="$icon" :class="'mt-0.5 size-6 shrink-0 '.$color" />
          <div class="min-w-0">
            {{-- 段の見出しがあれば、文の上に小さく出す --}}
            @if ($step['label'])
              <p @class(['text-sm font-bold', $color])>{{ $words['step_labels'][$step['label']] ?? $step['label'] }}</p>
            @endif
            <p class="leading-relaxed">{{ $step['text'] }}</p>
          </div>
        </li>
      @endforeach
    </ol>
    {{-- ホームズが外した場面なら、外れから直すことを伝えるひとことを添える --}}
    @if ($scene['is_mistake'])
      <p class="mt-4 flex items-start gap-2 rounded-lg bg-accent-soft px-3 py-2 font-bold text-accent">
        <x-ui.step-icon name="norbury" class="mt-0.5 size-6 shrink-0" /><span>{{ $mistakeNote }}</span>
      </p>
    @endif
  </x-guide.card-face>

  <section>
    <h3 class="flex items-center gap-2 text-lg"><x-ui.step-icon name="check" class="size-6 shrink-0 text-answer" />{{ $words['verify'] }}</h3>
    <p class="mt-1 leading-relaxed">{{ $scene['verification'] }}</p>
  </section>

  <section>
    <h3 class="flex items-center gap-2 text-lg"><x-ui.step-icon name="book" class="size-6 shrink-0 text-clue" />{{ $words['skills'] }}</h3>
    <x-guide.skill-links :keys="$scene['skills']" :names="$skillNames" class="mt-2" />
  </section>

  {{-- 原作の英語の引用があれば、作品名と章を添えて出す --}}
  @if (filled($scene['quote_en'] ?? null))
    <x-guide.quote :text="$scene['quote_en']"><x-guide.work :scene="$scene" /></x-guide.quote>
  @endif
</div>
