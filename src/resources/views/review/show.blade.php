{{--
  週のふりかえり（R-1〜R-3）。上から：切り替え（ふりかえり・ノーベリ帳・屋根裏の棚）→ 週の帯（前の週・次の週）→
  今週のまとめ（大きな一言と3つの数。ほかの数はたたむ）→ 週の問い（1問ずつ）→ 確かさの答え合わせ。
  この画面でいまやることは週の問いなので、まとめを1枚に詰めて、問いのカードと主なボタンがスマホの最初の画面に入るようにする。
  続けた日数・点数・順位は出さない。数が 0 でも責める言葉は出さない（R-6）
--}}
<x-layouts.app :title="\App\Support\Label::word('review')">
  <x-review.tabs current="review" :kid="$kid" />
  <x-review.week-nav :week="$week" :range="$range" />
  <x-review.week-summary :stats="$stats" :week="$week" :kid="$kid" />
  <x-review.reflection-steps :week="$week" :reflection="$reflection" :case-options="$caseOptions" class="mt-5" />
  <x-review.calibration :calibration="$calibration" :week="$week" :range="$range" :kid="$kid" class="mt-6" />
</x-layouts.app>
