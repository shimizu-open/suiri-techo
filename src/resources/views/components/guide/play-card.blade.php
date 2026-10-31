{{--
  手引きの入口でいちばん大きく出す「名推理カードで遊ぼう」：見出しと遊び方の一言、次に遊ぶカード（まだ推理を書いていない最初の1枚）の見本、
  そのカードの表へ進む大きなボタン、下に小さく「ほかのカードを選ぶ」。
  使い方：<x-guide.play-card :play="$overview['play']" :scene="$nextScene" :number="3" :total="23" :fresh="true" />
  play：overview.json の play（見出し・一言・ボタンの言葉）。scene：次に遊ぶカード（なければ null）。number：そのカードが何枚目か。
  total：カードの枚数。fresh：まだ推理を書いていないカードか（false なら全部書き終えて最初のカードに戻ったとき）
--}}
@props(['play', 'scene' => null, 'number' => null, 'total' => 0, 'fresh' => true])
<section aria-labelledby="play-title" {{ $attributes->class(['space-y-3']) }}>
  <div class="flex items-start gap-3">
    <span class="grid size-12 shrink-0 place-items-center rounded-full bg-answer text-on-ink">
      <x-ui.step-icon name="sparkle" class="size-7" />
    </span>
    <div class="min-w-0">
      <h2 id="play-title" class="text-2xl leading-snug">{{ $play['title'] }}</h2>
      <p class="text-base text-muted">{{ $play['hint'] }}</p>
    </div>
  </div>

  {{-- 遊べるカードがあれば、そのカードの見本（題・作品名・表の書き出し）を本物のカードの形で見せる --}}
  @if ($scene)
    <x-guide.card-face :number="$number">
      <x-slot:corner><span class="text-answer">{{ $fresh ? $play['label_next'] : $play['label_again'] }}</span></x-slot:corner>
      <p class="text-xl font-bold leading-snug">{{ $scene['title'] }}</p>
      <x-guide.work :scene="$scene" class="block text-sm text-muted" />
      <p class="mt-2 line-clamp-3 border-t border-dashed border-rule pt-2 text-base">{{ $scene['front'] }}</p>
    </x-guide.card-face>
  @endif

  <x-ui.big-button :href="$scene ? route('guide.scenes.show', $scene['key']) : route('guide.scenes.index')" tone="answer" icon="sparkle">{{ $play['button'] }}</x-ui.big-button>
  <p class="text-center text-sm">
    <a href="{{ route('guide.scenes.index') }}" class="inline-flex min-h-11 items-center font-semibold">{{ __($play['more'], ['count' => $total]) }}</a>
  </p>
</section>
