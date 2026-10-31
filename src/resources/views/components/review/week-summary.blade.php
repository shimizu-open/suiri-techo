{{--
  今週のまとめ（R-1）。枠で囲まず紙の上に直に書く（枠のあるカードは、いまやることの問いだけにする）：いちばん上に大きな一言（「今週は 3回、考えを変えたね」）、その下に大きな数を3つ
  （書いた事件・確かめた予想・考えを変えた）。ほかの数は、3つの数の列を押すと開く（<x-review.week-details> に数の列を渡す）。
  一言は config('suiri.review.headline') を上から見て、数が 1 以上の最初の種類のものを出す。
  記録のない週は責めない一言だけを出し、0 の数は並べない。前の週を開いたときは「今週」を「この週」と読み替える。
  使い方：<x-review.week-summary :stats="$stats" :week="$week" :kid="$kid" />
  stats：App\Services\Review\WeeklyStats::for() の結果。week：ReviewWeek。kid：子ども向けの言葉にする
--}}
@props(['stats', 'week', 'kid' => false])
@php($counts = ['mind_changes' => $stats['mind_changes'], 'resolved' => $stats['resolved']['total'], 'cases' => $stats['cases'], 'attic' => $stats['attic']['count'], 'estimates' => $stats['estimates']['count']])
@php($key = collect($counts)->search(fn (int $n): bool => $n > 0))
@php($line = $key === false ? config('suiri.review.empty_week') : config('suiri.review.headline.'.$key))
@php($title = strtr($line['title'], [':n' => $key === false ? 0 : $counts[$key]]))
<section aria-label="{{ $week->isCurrent() ? '今週' : 'この週' }}のまとめ" {{ $attributes }}>
  <p class="text-xl leading-snug font-bold sm:text-2xl">{{ $week->isCurrent() ? $title : str_replace('今週', 'この週', $title) }}</p>
  <p class="mt-0.5 text-sm text-muted">{{ $line['note'] }}</p>

  {{-- 記録のある週だけ、大きな数3つと、たたんだ内訳を出す（記録のない週に 0 を並べない） --}}
  @if ($key !== false)
    <x-review.week-details :stats="$stats" :kid="$kid" class="mt-3">
      {{-- 大きく出す3つの数を1つずつ取り出して並べる --}}
      @foreach (config('suiri.review.numbers') as $name => $number)
        <x-review.stat :label="$number['label']" :value="$counts[$name]" :unit="$number['unit']" />
      @endforeach
    </x-review.week-details>
  @endif
</section>
