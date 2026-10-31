{{--
  答え合わせの画面の一番上：前に書いた予想（C-7）。後知恵を防ぐため、書いた日時と予想の文をそのまま大きく見せ、
  「あとから変えられないよ」と小さく添える。自信（確かさ）・書いた日時（年まで）・確かめる日・見立て・使った屋根裏の知識は「くわしく」にたたむ。
  答え合わせが済んだら、右上に「確かめた」のはんこ（<x-ui.stamp>）を押した形にする（文ははんこを避けて回り込む）。
  使い方：<x-case.prediction-original :prediction="$prediction" :case="$case" :kid="$kid" />（prediction は hypothesis・atticNote を読み込んだもの）
  kid：子ども向けの言葉（はんこの「あたり」、自信の目安「けっこう 自信がある」など）を出すか
--}}
@props(['prediction', 'case', 'kid' => false])
@php($w = config('suiri.resolve'))
@php($choice = config('suiri.confidence.choices')[$prediction->confidence] ?? null)
<section aria-label="{{ $w['original'] }}" {{ $attributes->class(['rounded-2xl border border-rule bg-sheet px-4 pt-3 pb-2 sm:px-6']) }}>
  {{-- 答え合わせが済んでいれば、右上に「確かめた」のはんこを押す --}}
  @if ($prediction->isResolved())
    <x-ui.stamp class="float-right mt-1 mb-2 ml-3" :result="$prediction->result" :label="$kid ? $prediction->result->kidLabel() : $prediction->result->label($case->scene)" />
  @endif
  <p class="flex items-center gap-1.5 text-sm font-bold text-prediction">
    <x-ui.step-icon name="arrow" class="size-5 shrink-0" />
    <span>{{ __($w['written'], ['date' => $prediction->created_at->isoFormat($w['written_format'])]) }}</span>
  </p>
  <p class="mt-2 text-xl leading-snug font-bold break-words">「{{ $prediction->statement }}」</p>
  <details class="group clear-both mt-1">
    {{-- たたんだ行：変えられないことの一言と「くわしく」。開くと自信・書いた日時・確かめる日・見立てが出る --}}
    <summary class="flex min-h-11 cursor-pointer items-center gap-2 text-sm text-muted">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 shrink-0"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
      <span class="min-w-0 flex-1">{{ $w['frozen'] }}</span>
      <span class="inline-flex shrink-0 items-center gap-1 font-semibold">
        {{ $w['more'] }}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
      </span>
    </summary>
    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 pb-3 text-sm">
      <dt class="text-muted">{{ $w['detail']['confidence'] }}</dt>
      <dd>{{ __($w['confidence'], ['value' => $prediction->confidence]) }}{{ $choice ? '（'.($kid ? $choice['kid'] : $choice['hint']).'）' : '' }}</dd>
      <dt class="text-muted">{{ $w['detail']['written'] }}</dt>
      <dd>{{ $prediction->created_at->isoFormat($w['detail_format']) }}</dd>
      <dt class="text-muted">{{ $kid ? \App\Support\Label::kidWord('check_on') : \App\Support\Label::word('check_on', $case->scene) }}</dt>
      <dd>{{ \Illuminate\Support\Carbon::parse($prediction->check_on)->isoFormat('M月D日（ddd）') }}{{ $prediction->check_at ? ' '.$prediction->check_at : '' }}</dd>
      <dt class="text-muted">{{ $kid ? \App\Support\Label::kidWord('hypothesis') : \App\Support\Label::word('hypothesis', $case->scene) }}</dt>
      <dd class="break-words">{{ $prediction->hypothesis->body }}</dd>
      {{-- 使った屋根裏の知識があれば出す --}}
      @if ($prediction->atticNote)
        <dt class="text-muted">{{ $w['detail']['attic'] }}</dt>
        <dd class="break-words">{{ $prediction->atticNote->rule }}</dd>
      @endif
    </dl>
  </details>
</section>
