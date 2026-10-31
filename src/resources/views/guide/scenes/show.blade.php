{{--
  名推理カード1枚。表（大きな問い・カード・大きな「裏返す」・ヒント・メモ）と裏（?side=back：自分の考えとホームズの推理）を別のページにする。
  表の HTML にはホームズの推理を入れない（受付が表の欄だけを渡す）。裏の下に大きな「次のカードへ」、いちばん下に前と次。
  手引きのタブや一覧へもどるリンクは上に置かない（カードと「裏返す」を最初の画面に収める。もどるリンクはカードの下に小さく）
--}}
<x-layouts.app :title="config('suiri.guide.titles.scenes')">
  {{-- 裏を開いたときはホームズの推理と次のカードへのボタンを、そうでなければ表の手がかりと「裏返す」を出す --}}
  @if ($back)
    <h2 class="text-2xl leading-snug">{{ $scene['title'] }}</h2>
    <x-guide.work :scene="$scene" class="mb-4 block text-sm text-muted" />
    <x-guide.scene-back :scene="$scene" :attempt="$attempt" :steps="$steps" :skill-names="$skillNames" :mistake-note="$mistakeNote" :number="$position" :total="$total" />

    <div class="mt-8">
      {{-- 次のカードがあれば大きなボタンで進めるようにし、最後のカードなら一覧へもどるボタンにする --}}
      @if ($neighbors['next'])
        <x-ui.big-button :href="route('guide.scenes.show', $neighbors['next']['key'])" tone="answer" icon="sparkle">{{ config('suiri.guide.back.next') }}</x-ui.big-button>
        <p class="mt-1 text-center text-sm">
          <a href="{{ route('guide.scenes.index') }}" class="inline-flex min-h-11 items-center font-semibold">{{ config('suiri.guide.back.list') }}</a>
        </p>
      @else
        <x-ui.big-button :href="route('guide.scenes.index')" tone="secondary">{{ config('suiri.guide.back.list') }}</x-ui.big-button>
      @endif
    </div>
  @else
    <x-guide.scene-front :scene="$scene" :attempt="$attempt" :kid="$kid" :number="$position" :total="$total" />
  @endif

  <x-guide.pager :neighbors="$neighbors" route="guide.scenes.show" label="title" class="mt-8" />
</x-layouts.app>
