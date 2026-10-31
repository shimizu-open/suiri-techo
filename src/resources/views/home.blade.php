{{--
  トップ（ログイン前）。何ができるアプリかを、絵（親子で手帳を使う場面）と3行で伝え、すぐ下に「Google ではじめる」の大きなボタンを置く
  （スマホでもスクロールせずに押せる高さに収める。パソコンでは絵の高さを抑えて、ボタンを上の方に保つ）。その下に、手帳の使い方（4つの手順・番号つき）とお約束を置く。
  文は config('suiri.home')。文字は左ぞろえ（読む順に目が流れるように）
--}}
@php($h = config('suiri.home'))
<x-layouts.guest :brand="false">
  <section class="pt-1">
    <p class="text-xs font-bold tracking-[0.2em] text-muted">DEDUCTION NOTEBOOK</p>
    <h1 class="text-2xl leading-tight">{{ config('app.name') }}</h1>
    <p class="mt-1 text-base font-bold text-muted">{{ $h['lead'] }}</p>

    <img src="{{ asset('images/birdseye.webp') }}" alt="{{ $h['image_alt'] }}" width="800" height="500"
      class="mt-4 aspect-[8/5] w-full rounded-2xl border border-rule object-cover md:aspect-auto md:h-64">

    <ul class="mt-4 space-y-2.5">
      {{-- できることの3行を1つずつ取り出し、段の絵つきで並べる --}}
      @foreach ($h['points'] as $point)
        <x-auth.intro-line :icon="$point['icon']" :tone="$point['tone']">{{ $point['text'] }}</x-auth.intro-line>
      @endforeach
    </ul>

    <div class="mt-5 space-y-2">
      <x-auth.google-button :ready="$googleReady" :label="$h['button']" />
      <p class="text-xs text-muted">{{ config('suiri.invite_only') ? $h['invite'] : $h['open'] }}</p>
    </div>
  </section>

  <section aria-labelledby="home-steps" class="mt-10 border-t border-rule pt-5">
    <h2 id="home-steps" class="text-lg">{{ $h['steps_title'] }}</h2>
    <ol class="mt-4 space-y-4">
      {{-- 手順を1つずつ取り出し、番号と段の絵つきで並べる（順番のある手順なので番号を付ける） --}}
      @foreach ($h['steps'] as $i => $step)
        <x-auth.intro-line :icon="$step['icon']" :tone="$step['tone']" :number="$i + 1" :title="$step['title']">{{ $step['text'] }}</x-auth.intro-line>
      @endforeach
    </ol>
  </section>

  <section aria-labelledby="home-promise" class="mt-8 border-t border-rule pt-5 text-sm leading-relaxed">
    <h2 id="home-promise" class="text-lg">{{ $h['promise_title'] }}</h2>
    <p class="mt-2">{{ config('suiri.notes.effect') }}</p>
    <p class="mt-1">{{ $h['promise'] }}</p>
    <p class="mt-2"><a href="{{ route('about') }}">{{ $h['about'] }}</a></p>
  </section>
</x-layouts.guest>
