{{--
  「何を見落とした？」の種類ごとの数を、横の棒で並べる（棒の長さは一番多い種類を基準にする。数は必ず文字でも出す）。
  1行目に種類の名前と数、その下に棒（スマホでも名前が切れないように）。パソコンでは2列に並べて、縦に長くしない。
  使い方：<x-review.reason-counts :items="$items" :kid="$kid" :unknown="2" />
  items：[['reason' => MissReason か null, 'count' => 数], …]。unknown：見落としを選ばなかった外れの数（0 なら出さない）。kid：子ども向けの言葉にする
--}}
@props(['items' => [], 'unknown' => 0, 'kid' => false])
@php($max = max(1, $unknown, ...array_column($items, 'count')))
@php($rows = array_merge($items, $unknown > 0 ? [['reason' => null, 'count' => $unknown]] : []))
<ul {{ $attributes->class(['space-y-2.5 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-3 md:space-y-0']) }}>
  {{-- 種類を1つずつ取り出し、名前と数・棒を出す。種類のない行は「選ばなかった」として薄く出す --}}
  @foreach ($rows as $row)
    <li class="text-sm">
      <div class="flex items-baseline justify-between gap-3">
        <span @class(['text-muted' => ! $row['reason']])>{{ $row['reason'] ? ($kid ? $row['reason']->kidLabel() : $row['reason']->label()) : '選ばなかった' }}</span>
        <span class="shrink-0 font-semibold tabular-nums">{{ $row['count'] }}件</span>
      </div>
      <span class="mt-1 block h-2 rounded-full bg-mark" aria-hidden="true">
        <span @class(['block h-full rounded-full', 'bg-ink' => $row['reason'], 'bg-muted' => ! $row['reason']]) style="width: {{ round($row['count'] / $max * 100) }}%"></span>
      </span>
    </li>
  @endforeach
</ul>
