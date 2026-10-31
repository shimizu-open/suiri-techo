{{--
  答え合わせの画面（C-7・C-8）。この画面ですること：前に書いた予想を見て、実際はどうだったかを1つ選ぶ。
  上から：事件にもどるリンク → 前に書いた予想（書いた日時と文を必ず大きく見せる）→
  答え合わせ待ちなら「実際はどうだった？」の4つの大きなボタンと保存、済んだなら「確かめた」のはんこと見立ての直しのさそい、
  取り下げたならその知らせ。確かめるタスク・今日の画面からもこの画面に来る。
  kid：子ども向けの言葉を出すか（ふだんの場面が親子の人。受付 PredictionController@show が決めて渡す）
--}}
@php($w = config('suiri.resolve'))
<x-layouts.app :title="$w['title']">
  <a href="{{ route('cases.show', $case) }}" class="-mt-1 mb-3 flex min-h-11 min-w-0 items-center gap-1.5 text-sm text-muted no-underline">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 shrink-0"><path d="m15 6-6 6 6 6"/></svg>
    <span class="shrink-0 font-semibold text-ink">{{ __($w['back'], ['no' => $case->id]) }}</span>
    <span class="min-w-0 truncate">{{ $case->title }}</span>
  </a>

  <x-case.prediction-original :prediction="$prediction" :case="$case" :kid="$kid" />

  {{-- 答え合わせ待ちなら欄を、済んだなら結果と次の一手を、取り下げたならその知らせを出す --}}
  @if ($prediction->isPending())
    <x-case.resolve-form class="mt-6" :prediction="$prediction" :scene="$case->scene" :kid="$kid" />
  @elseif ($prediction->isResolved())
    <x-case.resolve-result class="mt-4" :prediction="$prediction" :case="$case" :kid="$kid" />
  @else
    <div class="mt-6 space-y-4">
      <p class="text-base">{{ __($w['withdrawn'], ['date' => $prediction->withdrawn_at->isoFormat('M月D日')]) }}</p>
      <x-ui.big-button tone="secondary" :href="route('cases.show', $case)">{{ $w['back_button'] }}</x-ui.big-button>
    </div>
  @endif
</x-layouts.app>
