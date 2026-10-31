{{--
  練習メニュー1件（名前・わざ・もとの場面・説明・子ども向けの説明・例のなぞ）と「この練習を始める」。
  子ども向けを見ている人（kid）には、子ども向けの説明を先に出す。始めると、事件を作る画面へ問いかけ入りで進む（cases.create がまだなければ「準備中」）。
  言葉は config('suiri.guide.practices')。
  使い方：<x-guide.practice-item :practice="$practice" :skill-names="$skillNames" :kid="$kid" />
  practice：practices.json の1件。skillNames：技能のキーと名前の対応。kid：子ども向けを先に出すか。id は practice-{key}（今日の画面の「くわしく」の飛び先）
--}}
@props(['practice', 'skillNames' => [], 'kid' => false])
@php($words = config('suiri.guide.practices'))
<article id="practice-{{ $practice['key'] }}" {{ $attributes->class(['scroll-mt-20 border-t-2 border-ink pt-3']) }}>
  <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
    <h3 class="text-xl">{{ $practice['name'] }}</h3>
    <x-guide.skill-links :keys="[$practice['skill']]" :names="$skillNames" />
  </div>
  <p class="text-sm text-muted">{{ __($words['origin'], ['origin' => $practice['origin']]) }}</p>
  <div @class(['mt-2 flex gap-2', 'flex-col-reverse' => $kid, 'flex-col' => ! $kid])>
    <p>{{ $practice['description'] }}</p>
    <p class="rounded-lg bg-hypothesis-soft px-3 py-2"><span class="mr-1 text-sm font-bold text-hypothesis">{{ $words['kid'] }}</span>{{ $practice['kid_description'] }}</p>
  </div>
  <p class="mt-2 text-sm text-muted">{{ __($words['example'], ['title' => $practice['prompts']['title']]) }}</p>
  {{-- 事件を作る画面があれば始めるボタン、まだなければ準備中の札を出す --}}
  @if (Route::has('cases.create'))
    <x-ui.big-button :href="route('cases.create', ['origin' => 'practice', 'practice' => $practice['key']])" tone="secondary" icon="plus" class="mt-3">{{ $words['start'] }}</x-ui.big-button>
  @else
    <x-ui.badge class="mt-3">{{ $words['soon'] }}</x-ui.badge>
  @endif
</article>
