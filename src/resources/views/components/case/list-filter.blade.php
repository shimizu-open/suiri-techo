{{--
  事件の一覧の絞り込みの枠（C-12）。形は捜査ファイルの引き出し：上に状態のつまみ（捜査中・解決・おあずけ。件数つき）があり、
  選んだつまみの下の紙に、その状態の一言と事件の並び（slot）を入れる。場面（日常・親子・仕事）でしぼるのは、ふだんはたたんで一番下に置く。
  つまみも場面もリンクで、もう片方の選び方は残す。場面でしぼっているときは、紙の上にそのことと「しぼるのをやめる」を出す。
  使い方：<x-case.list-filter :status="$status" :scene="$scene" :counts="$counts" :kid="$kid">…<ul>事件の並び</ul>…</x-case.list-filter>
  kid：子ども向けの言葉（調べ中・わかった・おあずけ、ふだん・おやこ・しごと）を出すか
--}}
@props(['status', 'scene' => null, 'counts' => [], 'kid' => false])
@php($w = config('suiri.case_list'))
@php($tabs = $kid ? $w['tabs_kid'] : $w['tabs'])
@php($scenes = ['' => $w['scene_all']] + ($kid ? config('suiri.labels.kid.scene') : \App\Enums\Scene::options()))
<div {{ $attributes }}>
  <nav aria-label="{{ config('suiri.labels.words.case') }}" class="grid grid-cols-3 gap-1.5">
    {{-- 状態を1つずつつまみにする。いまの状態のつまみは下の紙とつなげて白く、ほかは淡い紺にする --}}
    @foreach (\App\Enums\CaseStatus::cases() as $option)
      <a href="{{ route('cases.index', array_filter(['status' => $option->value, 'scene' => $scene?->value])) }}"
        aria-current="{{ $option === $status ? 'page' : 'false' }}"
        @class([
          'relative flex min-h-12 items-center justify-center gap-1.5 rounded-t-xl border border-b-0 px-1 text-base font-bold no-underline',
          'z-10 -mb-px border-rule bg-sheet pb-px text-ink' => $option === $status,
          'border-transparent bg-mark text-muted hover:text-ink' => $option !== $status,
        ])>
        <span class="truncate">{{ $tabs[$option->value] }}</span>
        <span class="text-sm font-semibold tabular-nums">{{ $counts[$option->value] ?? 0 }}</span>
      </a>
    @endforeach
  </nav>
  <section class="rounded-b-2xl border border-rule bg-sheet">
    <p class="px-4 pt-3 text-sm text-muted">{{ $w['leads'][$status->value] }}</p>
    {{-- 場面でしぼっているときは、そのことと、やめるリンクを出す --}}
    @if ($scene)
      <p class="mx-4 mt-2 flex flex-wrap items-center justify-between gap-x-3 rounded-xl bg-mark px-3 py-1.5 text-sm">
        <span class="font-semibold">{{ __($w['scene_now'], ['scene' => $scenes[$scene->value]]) }}</span>
        <a href="{{ route('cases.index', ['status' => $status->value]) }}" class="inline-flex min-h-11 items-center font-semibold">{{ $w['scene_clear'] }}</a>
      </p>
    @endif

    {{ $slot }}

    <details class="group border-t border-rule px-4" @if ($scene) open @endif>
      <summary class="flex min-h-12 cursor-pointer items-center gap-2 text-sm font-semibold text-muted">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
        {{ $w['scene_filter'] }}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="ml-auto size-5 group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
      </summary>
      <nav aria-label="{{ $w['scene_filter'] }}" class="flex flex-wrap gap-2 pb-4">
        {{-- 「すべて」と場面を1つずつ札にする。いまの場面は塗る --}}
        @foreach ($scenes as $value => $label)
          <a href="{{ route('cases.index', array_filter(['status' => $status->value, 'scene' => $value])) }}"
            aria-current="{{ (string) $scene?->value === (string) $value ? 'true' : 'false' }}"
            @class([
              'inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold no-underline',
              'border-ink bg-ink text-on-ink' => (string) $scene?->value === (string) $value,
              'border-rule bg-sheet text-muted hover:border-ink' => (string) $scene?->value !== (string) $value,
            ])>{{ $label }}</a>
        @endforeach
      </nav>
    </details>
  </section>
</div>
