{{--
  技能の「ふだんの練習の例」（大人・子ども・施術者の3つの場面）と「やりがちなこと」をひとまとめにしたファイル（見出しのつまみ付き）。
  子ども向けを見ているときは、子どもの例を先に出す。言葉は config('suiri.guide.skill')。
  使い方：<x-guide.skill-practice :practice="$skill['practice']" :pitfall="$skill['pitfall']" :kid="$kid" />
  practice：skills.json の practice（adult・kid・work）。pitfall：やりがちなことの文。kid：子ども向けを見ているか
--}}
@props(['practice', 'pitfall', 'kid' => false])
@php($words = config('suiri.guide.skill'))
@php($order = $kid ? ['kid', 'adult', 'work'] : ['adult', 'kid', 'work'])
<x-guide.section icon="arrow" tone="prediction" :title="$words['practice']" {{ $attributes }}>
  <dl class="divide-y divide-rule">
    {{-- 3つの場面を1つずつ、場面の名前と練習の例の行にする --}}
    @foreach ($order as $key)
      <div class="grid gap-0.5 py-2.5 first:pt-0 sm:grid-cols-[6rem_1fr]">
        <dt class="text-sm font-bold text-prediction">{{ $words['practice_labels'][$key] }}</dt>
        <dd>{{ $practice[$key] }}</dd>
      </div>
    @endforeach
  </dl>
  <div class="mt-3 rounded-lg bg-accent-soft px-3 py-2.5">
    <p class="text-sm font-bold text-accent">{{ $words['pitfall'] }}</p>
    <p>{{ $pitfall }}</p>
  </div>
</x-guide.section>
