{{--
  3つの考え方（アブダクション・演繹・帰納）の1つ。見出しのつまみ（段の色と絵・順番・名前）の下に、子ども向けの呼び名と手帳の段、
  説明（大人向けか子ども向け）、どのくらい確か？・原作では・この手帳では、を並べる。言葉は config('suiri.guide.reasoning')。
  使い方：<x-guide.reasoning-item :item="$item" :number="1" :kid="$kid" :scene="$scenes[$item['scene']] ?? null" />
  item：reasoning.json の items の1件（icon・tone も使う）。number：流れの中の順番。kid：子ども向けを出すか。scene：原作の場面のカード（なければ null）
--}}
@props(['item', 'number', 'kid' => false, 'scene' => null])
@php($words = config('suiri.guide.reasoning'))
<x-guide.section id="{{ $item['key'] }}" :icon="$item['icon']" :tone="$item['tone']" :title="$number.'. '.$item['name']" :kicker="$item['kid_name']" {{ $attributes->class(['scroll-mt-20']) }}>
  <x-ui.badge tone="ink">{{ $item['step'] }}</x-ui.badge>
  <p class="mt-2 leading-relaxed">{{ $kid ? $item['kid'] : $item['adult'] }}</p>
  <dl class="mt-3 space-y-2.5 border-t border-rule pt-3">
    <div class="grid gap-0.5 sm:grid-cols-[8rem_1fr]">
      <dt class="text-sm font-bold">{{ $words['certainty'] }}</dt>
      <dd>{{ $kid ? $item['kid_certainty'] : $item['certainty'] }}</dd>
    </div>
    <div class="grid gap-0.5 sm:grid-cols-[8rem_1fr]">
      <dt class="text-sm font-bold">{{ $words['holmes'] }}</dt>
      <dd>
        {{ $item['holmes_scene'] }}
        {{-- 場面のカードがあれば、カードへのリンクを添える --}}
        @if ($scene)
          <a href="{{ route('guide.scenes.show', $scene['key']) }}" class="inline-flex min-h-11 items-center font-semibold whitespace-nowrap">{{ $words['holmes_link'] }}</a>
        @endif
      </dd>
    </div>
    <div class="grid gap-0.5 sm:grid-cols-[8rem_1fr]">
      <dt class="text-sm font-bold">{{ $words['in_app'] }}</dt>
      <dd>{{ $item['in_app'] }}</dd>
    </div>
  </dl>
</x-guide.section>
