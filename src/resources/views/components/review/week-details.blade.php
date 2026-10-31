{{--
  週のふりかえりの内訳（R-1）をたたむ。たたんだときに見える行（<summary>）は大きな3つの数の列（中身 slot）と「くわしく」の印で、
  押すと、ほかの数が開く：
  見立てを2つ以上考えた事件の割合・答え合わせの結果の内訳・外れの見落とし・屋根裏の棚に増えた決まり・見積もりの答え合わせ。
  数が 0 でも責める言葉は出さない。使い方：<x-review.week-details :stats="$stats" :kid="$kid">3つの数（<x-review.stat>）</x-review.week-details>
  stats：App\Services\Review\WeeklyStats::for() の結果。kid：子ども向けの言葉にする
--}}
@props(['stats', 'kid' => false])
@php($d = config('suiri.review.details'))
@php($est = $stats['estimates'])
@php($results = $stats['resolved']['by_result'])
@php($resultLabel = fn (string $value): string => $kid ? \App\Enums\PredictionResult::from($value)->kidLabel() : \App\Enums\PredictionResult::from($value)->label())
<details {{ $attributes->class(['group']) }}>
  <summary class="grid cursor-pointer grid-cols-[1fr_1fr_1fr_auto] items-center divide-x divide-rule border-y border-rule hover:bg-mark">
    {{ $slot }}
    <span class="flex w-12 flex-col items-center justify-center self-stretch text-xs font-bold text-muted">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
      {{ $d['summary'] }}
    </span>
  </summary>
  <dl class="divide-y divide-rule border-b border-rule text-sm">
    <div class="flex items-baseline justify-between gap-3 py-3">
      <dt>{{ $d['multi'] }}</dt>
      <dd class="shrink-0 text-right">
        <span class="font-bold tabular-nums">{{ $stats['multi_hypothesis']['rate'] ?? '—' }}</span>{{ $stats['multi_hypothesis']['rate'] !== null ? '％' : '' }}
        <span class="block text-xs text-muted">{{ $stats['multi_hypothesis']['total'] > 0 ? $stats['multi_hypothesis']['total'].'件中'.$stats['multi_hypothesis']['count'].'件' : $d['multi_none'] }}</span>
      </dd>
    </div>
    <div class="flex items-baseline justify-between gap-3 py-3">
      <dt>{{ $d['results'] }}</dt>
      <dd class="text-right">
        {{-- 結果の種類を1つずつ取り出し、数を並べる（0 件の種類も出して、内訳がそろって見えるようにする） --}}
        @foreach ($results as $value => $n)
          <span class="ml-2 inline-block whitespace-nowrap">{{ $resultLabel($value) }} <span class="font-bold tabular-nums">{{ $n }}</span></span>
        @endforeach
      </dd>
    </div>
    <div class="py-3">
      <dt class="flex items-center justify-between gap-2">
        <span>{{ $kid ? \App\Support\Label::kidWord('miss_reason') : \App\Support\Label::word('miss_reason') }}</span>
        <a href="{{ route('norbury.index') }}" class="shrink-0 text-sm">{{ $kid ? \App\Support\Label::kidWord('norbury') : \App\Support\Label::word('norbury') }}へ</a>
      </dt>
      <dd class="mt-2">
        {{-- この週に外れがあれば種類ごとの数、なければ短い一言 --}}
        @if ($stats['misses'])
          <x-review.reason-counts :items="$stats['misses']" :kid="$kid" />
        @else
          <p class="text-muted">{{ $d['no_miss'] }}</p>
        @endif
      </dd>
    </div>
    <div class="py-3">
      <dt class="flex items-center justify-between gap-2">
        <span>{{ $d['attic'] }}<span class="ml-2 font-bold tabular-nums">{{ $stats['attic']['count'] }}</span>個</span>
        <a href="{{ route('attic.index') }}" class="shrink-0 text-sm">{{ \App\Support\Label::word('attic') }}へ</a>
      </dt>
      {{-- 増えた決まりがあれば、新しいものから数件の「つまり？」を出す --}}
      @if ($stats['attic']['recent']->isNotEmpty())
        <dd class="mt-2">
          <ul class="list-disc space-y-1 pl-5">
            {{-- 増えた決まりを1つずつ取り出して並べる --}}
            @foreach ($stats['attic']['recent'] as $note)
              <li>{{ $note->rule }}</li>
            @endforeach
          </ul>
        </dd>
      @endif
    </div>
    <div class="py-3">
      <dt>{{ $d['estimates'] }}</dt>
      <dd class="mt-1">
        {{-- 予想時間とかかった時間の両方があるタスクがあれば数字を、なければ短い一言を出す --}}
        @if ($est['count'] > 0)
          <p class="flex flex-wrap gap-x-4 gap-y-1">
            <span><span class="font-bold tabular-nums">{{ $est['count'] }}</span>件</span>
            <span>予想より長かった <span class="font-bold tabular-nums">{{ $est['longer_rate'] }}％</span>（{{ $est['longer'] }}件）</span>
            <span>平均のずれ <span class="font-bold tabular-nums">{{ $est['average_diff'] > 0 ? '+' : ($est['average_diff'] < 0 ? '−' : '±') }}{{ abs($est['average_diff']) }}分</span></span>
          </p>
          <p class="mt-1 text-xs text-muted">{{ $d['estimates_note'] }}</p>
        @else
          <p class="text-muted">{{ $d['estimates_none'] }}</p>
        @endif
      </dd>
    </div>
  </dl>
</details>
