{{--
  答え合わせを済ませたあと（はんこは上の <x-case.prediction-original> に押してある）。確かめた日・どこで見落としたか・メモを短く出し、
  外れならノーベリ帳に入ったことを知らせる。その下に次の一手を1つだけ大きく出す：
  当たり→「この見立てを『当たり』にする？」、外れ→「『消えた』にする？」（押すと考えを変えた記録が残る）。
  どちらとも言えない・前提が崩れた→「もう一度、予想してみる？」。どれも当てはまらなければ「事件にもどる」。
  使い方：<x-case.resolve-result :prediction="$prediction" :case="$case" :kid="$kid" />（prediction は hypothesis を読み込んだもの）
--}}
@props(['prediction', 'case', 'kid' => false])
@php($w = config('suiri.resolve'))
@php($result = $prediction->result)
@php($suggest = match ($result) { \App\Enums\PredictionResult::Hit => \App\Enums\HypothesisStatus::Confirmed, \App\Enums\PredictionResult::Miss => \App\Enums\HypothesisStatus::Eliminated, default => null })
@php($hypothesis = $prediction->hypothesis)
@php($book = $kid ? \App\Support\Label::kidWord('norbury') : \App\Support\Label::word('norbury'))
<section aria-label="{{ $w['title'] }}" {{ $attributes->class(['space-y-6']) }}>
  <div class="space-y-2 px-1 text-base">
    <p class="text-sm font-semibold text-answer">{{ __($w['resolved_on'], ['date' => $prediction->resolved_at?->isoFormat('M月D日（ddd）')]) }}</p>
    {{-- 外れの原因が選んであれば出す --}}
    @if ($prediction->miss_reason)
      <p><span class="text-sm text-muted">{{ $kid ? \App\Support\Label::kidWord('miss_reason') : $w['miss_label'] }}</span>　<span class="font-semibold">{{ $kid ? $prediction->miss_reason->kidLabel() : $prediction->miss_reason->label($case->scene) }}</span></p>
    @endif
    {{-- メモがあれば出す --}}
    @if (filled($prediction->result_note))
      <p class="break-words"><span class="text-sm text-muted">{{ $w['note'] }}</span>　{{ $prediction->result_note }}</p>
    @endif
    {{-- 外れなら、ノーベリ帳に入ったことを知らせる（画面があれば開くリンクも） --}}
    @if ($result->isMiss())
      <x-ui.hint icon="norbury" tone="clue">
        {{ __($w['norbury'], ['book' => $book]) }}
        {{-- ノーベリ帳の画面があれば、開くリンクを置く --}}
        @if (Route::has('norbury.index'))
          <a href="{{ route('norbury.index') }}" class="font-semibold">{{ __($w['norbury_open'], ['book' => $book]) }}</a>
        @endif
      </x-ui.hint>
    @endif
  </div>

  {{-- 結果に合う見立ての状態があり、まだその状態でなければ見立ての直しを、外れ以外で続けられるなら新しい予想を、それ以外は事件に戻るボタンを出す --}}
  @if ($suggest !== null && $hypothesis->status !== $suggest && $case->isOpen())
    @php($status = $kid ? $suggest->kidLabel() : $suggest->label())
    <form method="POST" action="{{ route('hypotheses.update', $hypothesis) }}">
      @csrf
      @method('PATCH')
      <x-ui.step-card icon="question" tone="hypothesis" :title="__($w[$kid ? 'suggest_title_kid' : 'suggest_title'], ['status' => $status])" :hint="$w['suggest_hint']">
        <p class="rounded-xl bg-hypothesis-soft px-4 py-3 text-lg leading-snug font-bold break-words">{{ $hypothesis->body }}</p>
        <p class="text-sm text-muted">{{ $w['suggest_body'] }}</p>
        <x-slot:action>
          <x-ui.big-button tone="hypothesis" name="status" :value="$suggest->value">{{ __($w['suggest_button'], ['status' => $status]) }}</x-ui.big-button>
        </x-slot:action>
        <x-slot:later><a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-11 items-center">{{ $w['suggest_keep'] }}</a></x-slot:later>
      </x-ui.step-card>
    </form>
  @elseif ($suggest === null && $case->isOpen() && ! $hypothesis->isEliminated())
    <x-ui.step-card icon="arrow" tone="prediction" :title="$w['again_title']" :hint="$w['again_hint']">
      <x-slot:action>
        <x-ui.big-button tone="prediction" icon="arrow" :href="route('cases.show', ['case' => $case, 'open' => 'predict-'.$hypothesis->id])">{{ $w['again_button'] }}</x-ui.big-button>
      </x-slot:action>
      <x-slot:later><a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-11 items-center">{{ $w['back_button'] }}</a></x-slot:later>
    </x-ui.step-card>
  @else
    <x-ui.big-button tone="secondary" :href="route('cases.show', $case)">{{ $w['back_button'] }}</x-ui.big-button>
  @endif
</section>
