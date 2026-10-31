{{--
  ノーベリ帳（R-4）。上から：切り替え → 短い説明（何をする所か・名前の由来の一言。お話は開くと読める）→
  何を見落とした？ごとの数 → 外れた予想の一覧（新しい順・1件ずつ「外れ」のはんこつき。罫線で区切る）。外れがまだなければ、絵と一言だけを出す。
  親子の場面では、子ども向けの呼び名「まちがいノート」と言い方で出す
--}}
@php($n = config('suiri.review.norbury'))
<x-layouts.app :title="\App\Support\Label::word('norbury')">
  <x-review.tabs current="norbury" :kid="$kid" />
  <x-review.story icon="norbury" :lead="$kid ? $n['kid_lead'] : $n['lead']" :origin="$n['origin']" :summary="config('suiri.review.origin_story')" :story="$n['story']"
    :kid-name="$kid ? \App\Support\Label::kidWord('norbury') : null" :kid-tagline="$kid ? $n['kid_tagline'] : null" />

  {{-- 外れた予想があれば見落としの数と一覧、なければ絵と一言だけを出す --}}
  @if ($misses->isNotEmpty())
    <section aria-labelledby="miss-reasons" class="border-t border-rule pt-4">
      <div class="mb-3 flex items-baseline justify-between gap-3">
        <h2 id="miss-reasons" class="text-lg">{{ $kid ? \App\Support\Label::kidWord('miss_reason') : \App\Support\Label::word('miss_reason') }}</h2>
        <span class="text-sm text-muted">外れ <span class="text-base font-bold text-ink tabular-nums">{{ $counts['total'] }}</span>件</span>
      </div>
      <x-review.reason-counts :items="$counts['reasons']" :unknown="$counts['unknown']" :kid="$kid" />
    </section>

    <section aria-labelledby="miss-list" class="mt-6 border-t border-rule pt-4">
      <h2 id="miss-list" class="mb-3 text-lg">{{ $n['list'] }}</h2>
      <ul class="divide-y divide-rule border-y border-rule">
        {{-- 外れた予想を1件ずつ取り出して札にする --}}
        @foreach ($misses as $prediction)
          <x-review.miss-item :prediction="$prediction" :kid="$kid" />
        @endforeach
      </ul>
      <x-review.pager :paginator="$misses" />
    </section>
  @else
    <x-ui.empty-state icon="norbury" :title="$n['empty']['title']">{{ $n['empty']['note'] }}</x-ui.empty-state>
  @endif
</x-layouts.app>
