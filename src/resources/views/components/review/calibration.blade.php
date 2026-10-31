{{--
  確かさの答え合わせ（R-2）：見出しと期間の切り替え（これまで全部・この週）→ 一言の説明（「70%と思った予想が、7割くらい当たっていればぴったり」）
  → 図 → 区間ごとの表（たたむ）。読むための段なので枠で囲まず、上の罫線で区切る。
  数えるのは当たり・外れだけ。件数が少ない区間は「まだ判断できない」。親子の場面では見出しを「自信と結果くらべ」にする。
  使い方：<x-review.calibration :calibration="$calibration" :week="$week" :range="$range" :kid="$kid" />
  calibration：App\Services\Review\Calibration::for() の結果。week：ReviewWeek。range：'all' か 'week'
--}}
@props(['calibration', 'week', 'range' => 'all', 'kid' => false])
@php($c = config('suiri.review.calibration'))
<section id="calibration" aria-labelledby="calibration-title" {{ $attributes->class(['scroll-mt-20 border-t border-rule pt-5']) }}>
  <div class="flex flex-wrap items-center justify-between gap-2">
    <h2 id="calibration-title" class="text-lg">{{ $kid ? \App\Support\Label::kidWord('calibration') : \App\Support\Label::word('calibration') }}</h2>
    <div class="flex shrink-0 rounded-lg border border-rule p-0.5 text-sm font-bold" role="group" aria-label="数える期間">
      <a href="{{ route('review.show', $week->param()) }}#calibration" @if ($range === 'all') aria-current="true" @endif
        @class(['inline-flex min-h-10 items-center rounded-md px-3 no-underline', 'bg-ink text-on-ink' => $range === 'all', 'text-muted' => $range !== 'all'])>これまで全部</a>
      <a href="{{ route('review.show', ['week' => $week->param(), 'range' => 'week']) }}#calibration" @if ($range === 'week') aria-current="true" @endif
        @class(['inline-flex min-h-10 items-center rounded-md px-3 no-underline', 'bg-ink text-on-ink' => $range === 'week', 'text-muted' => $range !== 'week'])>この週</a>
    </div>
  </div>
  <p class="mt-3 text-base font-bold">{{ $c['lead'] }}</p>
  <p class="mt-0.5 text-sm text-muted">{{ $c['note'] }}</p>

  <figure class="mt-3 w-full max-w-md">
    <x-review.calibration-chart :buckets="$calibration['buckets']" />
    <figcaption class="mt-1 space-y-0.5 text-xs text-muted">
      <p>横：自信（確かさ）の区間と件数　縦：当たった割合</p>
      <p class="flex flex-wrap gap-x-4">
        <span><span class="inline-block size-2.5 rounded-full bg-ink align-middle"></span> 当たった割合（{{ $calibration['min'] }}件以上の区間）</span>
        <span><span class="inline-block w-5 border-t-2 border-dashed border-muted align-middle"></span> ぴったりの線</span>
      </p>
    </figcaption>
  </figure>
  <p class="mt-2 text-sm text-muted">
    数えたのは <span class="font-bold text-ink tabular-nums">{{ $calibration['counted'] }}件</span>（当たり・外れだけ。どちらとも言えない・前提が崩れたは数えないよ）。
  </p>

  <details class="group mt-2">
    <summary class="inline-flex min-h-11 cursor-pointer items-center gap-1 text-sm font-bold text-muted hover:text-ink">
      {{ $c['table'] }}
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <table class="mt-1 w-full max-w-md text-sm">
      <thead class="text-xs text-muted">
        <tr class="border-b border-rule"><th class="py-1.5 text-left font-bold">確かさ</th><th class="py-1.5 text-right font-bold">件数</th><th class="py-1.5 text-right font-bold">当たった割合</th></tr>
      </thead>
      <tbody>
        {{-- 区間を1つずつ取り出し、件数と当たった割合（足りなければ「まだ判断できない」）を並べる --}}
        @foreach ($calibration['buckets'] as $b)
          <tr class="border-b border-rule last:border-0">
            <td class="py-2">{{ $b['label'] }}</td>
            <td class="py-2 text-right tabular-nums">{{ $b['count'] }}件</td>
            {{-- 件数が足りれば割合と内訳、足りなければ「まだ判断できない」 --}}
            @if ($b['enough'])
              <td class="py-2 text-right"><span class="font-bold tabular-nums">{{ $b['rate'] }}％</span> <span class="text-xs text-muted">（{{ $b['hits'] }}件当たり）</span></td>
            @else
              <td class="py-2 text-right text-xs text-muted">まだ判断できない</td>
            @endif
          </tr>
        @endforeach
      </tbody>
    </table>
  </details>
</section>
