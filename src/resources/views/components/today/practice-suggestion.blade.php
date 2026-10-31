{{--
  今日の画面の下：きょうの練習。日替わりで1つ選んだ練習メニュー（App\Services\Guide\DailyPractice）の名前と短い説明と「やってみる」。
  やらなくてもよいことが伝わるよう、カードにせず小さく出す（連続記録や「まだやっていません」のような言葉は使わない）。
  使い方：<x-today.practice-suggestion :practice="$practice" :kid="$kid" />
  practice：日替わりで1つ提案する練習メニュー（practices.json の1件。なければ null）。
  kid：子ども向けの説明（kid_description）を出すか（ふだんの場面が親子の人）。ほかの人には大人向けの説明を2行まで
--}}
@props(['practice' => null, 'kid' => false])
@php($words = config('suiri.today.practice'))
{{-- 練習メニューが選ばれているときだけ出す --}}
@if ($practice)
  <section aria-labelledby="practice-title" {{ $attributes->class(['border-t border-rule pt-4']) }}>
    <h2 id="practice-title" class="flex items-center gap-2 text-sm font-bold text-hypothesis">
      <x-ui.step-icon name="sparkle" class="size-5" />{{ $words['title'] }}
    </h2>
    <p class="mt-2 text-base font-bold">{{ $practice['name'] }}</p>
    <p class="mt-0.5 line-clamp-2 text-sm text-muted">{{ $kid ? $practice['kid_description'] : $practice['description'] }}</p>
    <div class="mt-2 flex flex-wrap items-center gap-x-4">
      {{-- 事件を作る画面があれば、この練習の問いかけ入りで始めるリンクを出す --}}
      @if (Route::has('cases.create'))
        <a href="{{ route('cases.create', ['origin' => 'practice', 'practice' => $practice['key']]) }}" class="inline-flex min-h-11 items-center gap-1 font-bold text-ink">{{ $words['start'] }}<span aria-hidden="true">›</span></a>
      @endif
      {{-- 手引きの練習メニューの画面があれば、このメニューの説明へのリンクを出す --}}
      @if (Route::has('guide.practices.index'))
        <a href="{{ route('guide.practices.index').'#practice-'.$practice['key'] }}" class="inline-flex min-h-11 items-center text-sm text-muted">{{ $words['more'] }}</a>
      @endif
      <span class="text-sm text-muted">{{ $words['note'] }}</span>
    </div>
  </section>
@endif
