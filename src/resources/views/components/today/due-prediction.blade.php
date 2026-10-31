{{--
  答え合わせの日が来た予想の中身（1件）：事件のなぞ・書いた予想の文・書いた日と自信・確かめる日。
  今日の画面のいちばん上のカード（<x-today.next-step>）と、たたんだ「ほかにも◯件」の一覧（<x-today.due-predictions>）で使う。
  使い方：<x-today.due-prediction :prediction="$prediction" />（compact でたたんだ一覧向けの小さな形）
  prediction：Prediction（事件 caseFile を読み込み済み）
--}}
@props(['prediction', 'compact' => false])
@php($words = config('suiri.today.answer'))
@php($checkOn = \Carbon\Carbon::parse($prediction->check_on))
@php($dueToday = $checkOn->isSameDay(today()))
<div {{ $attributes->class(['min-w-0']) }}>
  <p class="text-sm text-muted break-words"><span class="font-semibold">{{ $words['case'] }}</span>：{{ $prediction->caseFile?->title }}</p>
  {{-- 大きな形のときだけ、「あなたの予想」の小さな見出しを出す --}}
  @unless ($compact)
    <p class="mt-3 text-sm font-bold text-answer">{{ $words['prediction'] }}</p>
  @endunless
  <p @class(['leading-snug font-bold break-words', 'font-mincho text-xl' => ! $compact, 'mt-1 text-base' => $compact])>「{{ $prediction->statement }}」</p>
  <p class="mt-1 flex flex-wrap gap-x-3 text-sm text-muted">
    <span>{{ __($words['written'], ['date' => $prediction->created_at?->isoFormat('M月D日')]) }}</span>
    <span>{{ __($words['confidence'], ['value' => $prediction->confidence]) }}</span>
  </p>
  <p @class(['mt-1 text-sm font-semibold', 'text-answer' => ! $dueToday, 'text-ink' => $dueToday])>
    {{-- 確かめる日が今日なら「きょうが確かめる日」、過ぎていれば「確かめる日は◯月◯日だったよ」 --}}
    @if ($dueToday)
      {{ $words['due_today'] }}
    @else
      {{ __($words['due_past'], ['date' => $checkOn->isoFormat(config('suiri.today.date_format'))]) }}
    @endif
    {{-- 確かめる時刻まで決めていれば、添える --}}
    @if ($prediction->check_at)
      <span class="tabular-nums">（{{ $prediction->check_at }}）</span>
    @endif
  </p>
</div>
