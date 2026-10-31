{{--
  いまやることのカード「〇月〇日に確かめよう」（答え合わせを待つ段）。カレンダーの紙のような大きな日付と、立てた予想（文と自信）。
  カレンダーとつないでいれば「カレンダーに入れたよ」、なければ「やることに入れたよ」と知らせる。
  下に小さく「先に答え合わせをする」（predictions.show）と「取り下げる」（predictions.withdraw。確かめるやることも消える）。
  使い方：<x-case-step.wait-card :case :prediction :kid :number :total :note :calendar-connected="false" />
--}}
@props(['case', 'prediction', 'kid' => false, 'number' => null, 'total' => null, 'note' => null, 'calendarConnected' => false])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
@php($day = \Illuminate\Support\Carbon::parse($prediction->check_on))
<x-ui.step-card :step="$number" :total="$total" icon="calendar" tone="check" :title="$t('wait.title', ['date' => $day->isoFormat('M月D日（ddd）')])"
  :hint="$t($calendarConnected ? 'wait.calendar' : 'wait.no_calendar')">
  <div class="flex items-start gap-4">
    <p aria-hidden="true" class="w-20 shrink-0 overflow-hidden rounded-xl border-2 border-prediction bg-sheet text-center leading-tight">
      <span class="block bg-prediction py-0.5 text-sm font-bold text-on-ink">{{ $day->isoFormat('M月') }}</span>
      <span class="block pt-1 text-2xl font-bold">{{ $day->day }}</span>
      <span class="block pb-1 text-xs text-muted">{{ $day->isoFormat('ddd') }}{{ $prediction->check_at ? ' '.$prediction->check_at : '' }}</span>
    </p>
    <blockquote class="min-w-0 flex-1">
      <p class="text-sm font-bold text-prediction">{{ $kid ? \App\Support\Label::kidWord('prediction') : \App\Support\Label::word('prediction', $case->scene) }}</p>
      <p class="leading-relaxed break-words">{{ $prediction->statement }}</p>
      <p class="mt-1 text-sm text-muted">{{ $t('wait.confidence', ['value' => $prediction->confidence]) }}</p>
    </blockquote>
  </div>
  <div class="flex flex-wrap items-center gap-x-4">
    <a href="{{ route('predictions.show', $prediction) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $t('wait.early') }}</a>
    <x-ui.confirm :action="route('predictions.withdraw', $prediction)" method="POST" :label="$t('wait.withdraw')" :confirm-label="$t('wait.withdraw')"
      :message="$t('wait.withdraw_message')" />
  </div>
  <x-case-step.bubble :note="$note" :case="$case" :explain="$t('wait.explain')" />
</x-ui.step-card>
