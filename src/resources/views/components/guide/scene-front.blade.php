{{--
  名推理カードの表：大きな問い「この手がかりから、何がわかる？」とひとこと（親子の人には「まずは声に出して言ってみよう」）→ カード（題・作品名・手がかり）
  → 大きな「裏返す」→ ヒント（親子の人には開いておく）→ たたんだ「考えをメモしておく」→ 小さく一覧へもどるリンク。
  メモは書かなくても裏返せる（空なら保存せずに裏へ進む）。メモを開いた人のために、メモの下にも「メモして裏返す」を置く（同じ送信）。
  ホームズの推理はここには出さない（裏は別のページ）。前に書いたメモがあれば、欄に入れて開いておく（保存すると書きかわる）。
  使い方：<x-guide.scene-front :scene="$scene" :attempt="$attempt" :kid="$kid" :number="7" :total="23" />
  scene：scenes.json の1件（表の欄だけ）。attempt：自分が前に書いた推理（SceneAttempt か null）。
  kid：ふだんの場面が親子の人か（「声に出して」のひとことと、ヒントを開いておく）。number・total：何枚目か・全部で何枚か
--}}
@props(['scene', 'attempt' => null, 'kid' => false, 'number' => null, 'total' => null])
@php($words = config('suiri.guide.front'))
<form method="POST" action="{{ route('guide.scenes.attempt', $scene['key']) }}" {{ $attributes->class(['space-y-3']) }}>
  @csrf
  <div>
    <h2 class="text-xl leading-snug tracking-normal sm:text-2xl">{{ $words['question'] }}</h2>
    <p class="text-base text-muted">{{ $kid ? $words['say_aloud'] : $words['think_first'] }}</p>
  </div>

  <x-guide.card-face :number="$number" :total="$total">
    {{-- ホームズが外した場面なら、右上に赤いはんこを押す（どこで外したかは裏で読む） --}}
    @if ($scene['is_mistake'])
      <x-slot:corner><x-guide.stamp tone="accent">{{ config('suiri.guide.scenes.mistake') }}</x-guide.stamp></x-slot:corner>
    @endif
    <p class="text-xl font-bold leading-snug">{{ $scene['title'] }}</p>
    <x-guide.work :scene="$scene" class="block text-sm text-muted" />
    <p class="mt-2 border-t border-dashed border-rule pt-2 text-base leading-relaxed">{{ $scene['front'] }}</p>
  </x-guide.card-face>

  <x-ui.big-button tone="clue" icon="sparkle">{{ $words['flip'] }}</x-ui.big-button>

  {{-- ヒントがあれば、たたんで出す（親子の人には初めから開いておく） --}}
  @if (filled($scene['kid_hint'] ?? null))
    <details class="rounded-xl border border-hypothesis/40 bg-hypothesis-soft px-4" @if ($kid) open @endif>
      <summary class="flex min-h-12 cursor-pointer items-center gap-2 font-semibold text-hypothesis">
        <x-ui.step-icon name="question" class="size-5 shrink-0" />{{ $words['hint_open'] }}
      </summary>
      <p class="pb-3">{{ $scene['kid_hint'] }}</p>
    </details>
  @endif

  <details class="rounded-xl border border-rule bg-sheet px-4" @if ($attempt || $errors->has('answer')) open @endif>
    <summary class="flex min-h-12 cursor-pointer items-center gap-2 font-semibold">
      <x-ui.icon name="plus" class="size-5 shrink-0" />{{ $words['memo_open'] }}
    </summary>
    <div class="space-y-3 pb-4">
      <x-ui.textarea name="answer" :label="$words['memo_label']" :value="$attempt?->answer" rows="3"
        :maxlength="config('suiri.limits.scene_answer')" :placeholder="$words['memo_placeholder']"
        :hint="$attempt ? __($words['memo_saved'], ['date' => $attempt->updated_at->format('n月j日')]) : $words['memo_help']" />
      <x-ui.button variant="secondary" block class="min-h-12">{{ $words['flip_with_memo'] }}</x-ui.button>
      <x-ui.privacy-note />
    </div>
  </details>

  <p class="text-center text-sm">
    <a href="{{ route('guide.scenes.index') }}" class="inline-flex min-h-11 items-center font-semibold">{{ $words['back_to_list'] }}</a>
  </p>
</form>
