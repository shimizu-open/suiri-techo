{{--
  答え合わせの欄（C-7・C-8）：「実際はどうだった？」と4つの大きなボタン（当たり・外れ・どちらとも言えない・前提が崩れた）。
  ボタンは結果ごとに「確かめた」のはんこ（<x-ui.stamp>）と同じ色と輪の形（二重の輪・点線）にし、選ぶとその色で塗る。
  「外れ」を選んだときだけ「どこで見落とした？」を、どれかを選んだら「ひとことメモ」を出す（JavaScript なしで CSS の :has で切り替える。送り直したときも選んだまま）。
  保存すると確かめるタスクが済みになる。カードの下には個人情報の注意書き。
  使い方：<x-case.resolve-form :prediction="$prediction" :scene="$case->scene" :kid="$kid" />（フォームの id は resolve-form）
  kid：子ども向けの言葉（あたり・はずれ、どこでまちがえた？ など）を出すか
--}}
@props(['prediction', 'scene' => null, 'kid' => false])
@php($w = config('suiri.resolve'))
@php($picked = (string) old('result'))
{{-- 結果ごとの見た目（ring：はんこの外の輪／inner：内の輪／on：選んだときのボタンの枠と面） --}}
@php($looks = [
  'hit' => ['ring' => 'border-answer', 'inner' => 'border-answer group-has-checked/opt:bg-answer', 'on' => 'peer-checked:border-answer peer-checked:bg-answer-soft'],
  'miss' => ['ring' => 'border-clue', 'inner' => 'border-clue group-has-checked/opt:bg-clue', 'on' => 'peer-checked:border-clue peer-checked:bg-clue-soft'],
  'unclear' => ['ring' => 'border-hypothesis', 'inner' => 'border-hypothesis border-dashed group-has-checked/opt:bg-hypothesis', 'on' => 'peer-checked:border-hypothesis peer-checked:bg-hypothesis-soft'],
  'void' => ['ring' => 'border-muted border-dashed', 'inner' => 'border-transparent group-has-checked/opt:bg-muted', 'on' => 'peer-checked:border-muted peer-checked:bg-mark'],
])
<form id="resolve-form" method="POST" action="{{ route('predictions.resolve', $prediction) }}" {{ $attributes->class(['group/resolve']) }}>
  @csrf
  <x-ui.step-card icon="check" tone="answer" :title="$w['question']" :hint="$w['question_hint']">
    <fieldset>
      <legend class="sr-only">{{ $w['question'] }}</legend>
      <div class="grid grid-cols-2 gap-2.5">
        {{-- 結果を1つずつ取り出し、はんこの形の輪と言葉の大きなボタンにする --}}
        @foreach (\App\Enums\PredictionResult::cases() as $result)
          <label class="group/opt cursor-pointer">
            <input type="radio" name="result" value="{{ $result->value }}" class="peer sr-only" required @checked($picked === $result->value)>
            <span class="flex h-full min-h-16 items-center gap-2.5 rounded-xl border-2 border-rule bg-sheet px-2.5 py-2 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ink {{ $looks[$result->value]['on'] }}">
              <span aria-hidden="true" class="grid size-9 shrink-0 -rotate-6 place-items-center rounded-full border-[3px] p-[3px] {{ $looks[$result->value]['ring'] }}">
                <span class="size-full rounded-full border {{ $looks[$result->value]['inner'] }}"></span>
              </span>
              <span class="min-w-0 text-base leading-snug font-bold [word-break:auto-phrase]">{{ $kid ? $result->kidLabel() : $result->label($scene) }}</span>
            </span>
          </label>
        @endforeach
      </div>
      {{-- どちらとも言えない・前提が崩れた を選んだときだけ、当たり外れに数えないことを出す --}}
      <p class="mt-2 hidden text-sm text-muted group-has-[input[name=result][value=unclear]:checked]/resolve:block group-has-[input[name=result][value=void]:checked]/resolve:block">{{ $w['not_counted'] }}</p>
      @error('result')
        <p class="mt-1 text-sm text-accent">{{ $message }}</p>
      @enderror
    </fieldset>

    {{-- 「外れ」を選んだときだけ、どこで見落としたかを選ぶ欄を出す --}}
    <div class="hidden rounded-xl bg-clue-soft p-3 group-has-[input[name=result][value=miss]:checked]/resolve:block">
      <x-ui.choices name="miss_reason" :label="$kid ? \App\Support\Label::kidWord('miss_reason') : $w['miss_label']"
        :options="$kid ? config('suiri.labels.kid.miss_reason') : \App\Enums\MissReason::options($scene)" :hint="$w['miss_hint']" />
    </div>
    {{-- どれかの結果を選んだら、ひとことメモの欄を出す（書かなくても保存できる） --}}
    <div class="hidden group-has-[input[name=result]:checked]/resolve:block">
      <x-ui.textarea name="result_note" :label="$w['note_label']" rows="2" :maxlength="config('suiri.limits.result_note')" :placeholder="$w['note_placeholder']" />
    </div>

    <x-slot:action>
      <x-ui.big-button tone="answer" icon="check">{{ $w['save'] }}</x-ui.big-button>
    </x-slot:action>
  </x-ui.step-card>
  <x-ui.privacy-note class="mt-3" :scene="$scene" />
</form>
