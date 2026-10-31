{{--
  確かさの答え合わせの図（SVG。外部のグラフの部品は使わない）。横は確かさ（50〜100％）、縦は当たった割合（0〜100％）。
  点線は「確かさ＝当たった割合」のぴったりの線。点は件数が足りた区間だけに打ち（横の位置はその区間の確かさの平均）、
  となりどうしの区間がどちらも足りたときだけ線でつなぐ（足りない区間をまたいでつながない）。
  どの区間にも下に件数を添える。件数が足りない区間は点を打たず、件数を薄く出す。点に指を置くと中身が出る（<title>）。
  使い方：<x-review.calibration-chart :buckets="$calibration['buckets']" />。buckets：App\Services\Review\Calibration の結果の buckets
--}}
@props(['buckets' => []])
@php($L = 40) @php($R = 308) @php($T = 12) @php($B = 168)
@php($sx = fn (float $c): float => round($L + ($c - 50) / 50 * ($R - $L), 1))
@php($sy = fn (float $r): float => round($B - $r / 100 * ($B - $T), 1))
<svg viewBox="0 0 320 206" role="img" aria-label="確かさの区間ごとの、当たった割合の図。数字は下の表にもあります" {{ $attributes->class(['block h-auto w-full']) }}>
  {{-- 縦の目盛り（0・25・50・75・100％）を1本ずつ引く。0・50・100 だけ数字を添える --}}
  @foreach ([0, 25, 50, 75, 100] as $r)
    <line x1="{{ $L }}" x2="{{ $R }}" y1="{{ $sy($r) }}" y2="{{ $sy($r) }}" class="stroke-rule" stroke-width="1" />
    {{-- 目盛りの数字は 0・50・100 だけ --}}
    @if ($r % 50 === 0)
      <text x="{{ $L - 6 }}" y="{{ $sy($r) + 3.5 }}" text-anchor="end" class="fill-muted" font-size="10">{{ $r }}%</text>
    @endif
  @endforeach
  <line x1="{{ $sx(50) }}" y1="{{ $sy(50) }}" x2="{{ $sx(100) }}" y2="{{ $sy(100) }}" class="stroke-muted" stroke-width="1.5" stroke-dasharray="4 4" />
  {{-- となりの区間を順に見て、どちらも件数が足りていれば、2つの点を細い線でつなぐ --}}
  @foreach ($buckets as $i => $b)
    {{-- 1つ前の区間とこの区間がどちらも足りたときだけ線を引く --}}
    @if ($i > 0 && $b['enough'] && $buckets[$i - 1]['enough'])
      <line x1="{{ $sx($buckets[$i - 1]['mean']) }}" y1="{{ $sy($buckets[$i - 1]['rate']) }}" x2="{{ $sx($b['mean']) }}" y2="{{ $sy($b['rate']) }}"
        class="stroke-ink" stroke-width="2" stroke-linecap="round" />
    @endif
  @endforeach
  {{-- 区間を1つずつ取り出し、下に区間の名前と件数を書き、件数が足りていれば点を打つ --}}
  @foreach ($buckets as $b)
    @php($cx = $sx(($b['from'] + $b['to']) / 2))
    <text x="{{ $cx }}" y="{{ $B + 16 }}" text-anchor="middle" class="fill-muted" font-size="10">{{ $b['from'] }}〜{{ $b['to'] }}</text>
    <text x="{{ $cx }}" y="{{ $B + 32 }}" text-anchor="middle" font-size="10" font-weight="700" @class(['fill-ink' => $b['enough'], 'fill-muted' => ! $b['enough']])>{{ $b['count'] }}件</text>
    {{-- 件数が足りた区間だけ点を打つ（見えない大きめの丸で、指でも中身を出しやすくする） --}}
    @if ($b['enough'])
      <g>
        <title>確かさ {{ $b['label'] }}：{{ $b['count'] }}件中 {{ $b['hits'] }}件が当たり（{{ $b['rate'] }}％）</title>
        <circle cx="{{ $sx($b['mean']) }}" cy="{{ $sy($b['rate']) }}" r="14" fill="transparent" />
        <circle cx="{{ $sx($b['mean']) }}" cy="{{ $sy($b['rate']) }}" r="5" class="fill-ink stroke-sheet" stroke-width="2" />
      </g>
    @endif
  @endforeach
</svg>
