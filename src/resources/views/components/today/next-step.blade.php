{{--
  今日の画面のいちばん上：「今日やること」を1枚だけ大きく出すカード。
  - 答え合わせの日が来た予想があれば「答え合わせしよう」（いちばん古いものを1件。ほかは下に「ほかにも◯件」でたたむ）
  - なければ、記録のきっかけ（いつ書くか）と「気になったことを書こう」の大きなボタン
  使い方：<x-today.next-step :predictions="$predictions" :cue="$user->cue" />
  predictions：Queries/Today/DuePredictionsQuery が返す予想（事件 caseFile を読み込み済み・古い順）。cue：記録のきっかけの一文（なければ null）
--}}
@props(['predictions' => [], 'cue' => null])
@php($first = collect($predictions)->first())
@php($rest = collect($predictions)->slice(1))
@php($words = config('suiri.today.answer'))
{{-- 答え合わせの日が来た予想があれば答え合わせのカードを、なければ気になったことを書くカードを出す --}}
@if ($first)
  <x-ui.step-card icon="check" tone="answer" :title="$words['title']" :hint="$words['hint']" {{ $attributes }}>
    <x-today.due-prediction :prediction="$first" />

    {{-- 予想の画面があれば、答え合わせへ進む大きなボタンを出す --}}
    @if (Route::has('predictions.show'))
      <x-slot:action>
        <x-ui.big-button :href="route('predictions.show', $first)" icon="check" tone="answer">{{ $words['button'] }}</x-ui.big-button>
      </x-slot:action>
    @endif

    {{-- ほかにも答え合わせを待っている予想があれば、カードの下にたたんで並べる --}}
    @if ($rest->isNotEmpty())
      <x-slot:later>
        <x-today.due-predictions :predictions="$rest" :label="__($words['more'], ['count' => $rest->count()])" />
      </x-slot:later>
    @endif
  </x-ui.step-card>
@else
  <x-ui.step-card icon="eye" tone="clue" :title="config('suiri.today.record.title')" :hint="config('suiri.today.record.hint')" {{ $attributes }}>
    {{-- 記録のきっかけを決めていれば、「いつ書くか」の一文をカードの中に出す --}}
    @if (filled($cue))
      <p class="flex flex-col gap-0.5 rounded-xl bg-clue-soft px-4 py-3">
        <span class="text-sm font-bold text-clue">{{ config('suiri.today.record.cue_label') }}</span>
        <span class="text-lg leading-snug font-semibold text-pretty break-words">{{ $cue }}</span>
      </p>
    @endif

    {{-- 事件を作る画面があれば、気になったことを書く大きなボタンを出す --}}
    @if (Route::has('cases.create'))
      <x-slot:action>
        <x-ui.big-button :href="route('cases.create')" icon="plus" tone="clue">{{ config('suiri.today.record.button') }}</x-ui.big-button>
      </x-slot:action>
    @endif

    {{-- きっかけをまだ決めていなければ、設定で決めるようにカードの下で小さくさそう --}}
    @if (blank($cue))
      <x-slot:later>
        {{ config('suiri.today.record.no_cue') }}<a href="{{ route('settings.show') }}" class="ml-1 inline-flex min-h-11 items-center font-semibold text-ink">{{ config('suiri.today.record.no_cue_link') }}</a>
      </x-slot:later>
    @endif
  </x-ui.step-card>
@endif
