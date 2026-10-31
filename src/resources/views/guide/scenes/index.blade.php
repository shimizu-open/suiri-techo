{{--
  名推理カードの一覧。上から：遊び方の一言 → 大きな「まだのカードで遊ぶ」（まだ推理を書いていない最初のカードへ）と書いた枚数
  → カードの一覧（番号・題・作品名と章・ホームズが外した場面と推理を書いたカードのはんこ）。
  中身は scenes.json（GuideLibrary::scenes()）。自分の推理は scene_attempts（自分の行だけ）
--}}
<x-layouts.app :title="config('suiri.guide.titles.scenes')">
  <x-guide.tabs current="guide.scenes.index" />

  <p class="text-base leading-relaxed">{{ config('suiri.guide.scenes.lead') }}</p>

  {{-- 遊べるカードがあれば、まだのカード（全部書いたら最初のカード）へ進む大きなボタンを出す --}}
  @if ($nextScene)
    <x-ui.big-button :href="route('guide.scenes.show', $nextScene['key'])" tone="answer" icon="sparkle" class="mt-4">
      {{ count($doneKeys) < count($scenes) ? config('suiri.guide.scenes.play_next') : config('suiri.guide.scenes.play_again') }}
    </x-ui.big-button>
  @endif
  <p class="mt-2 text-center text-sm text-muted tabular-nums">{{ __(config('suiri.guide.scenes.progress'), ['done' => count($doneKeys), 'total' => count($scenes)]) }}</p>

  <ul class="mt-4 divide-y divide-rule border-y border-rule">
    {{-- カードを1枚ずつ、番号つきの一覧の行にする。推理を書いたカードには、はんこを押す --}}
    @foreach ($scenes as $scene)
      <x-guide.scene-item :scene="$scene" :number="$loop->iteration" :done="in_array($scene['key'], $doneKeys, true)" />
    @endforeach
  </ul>
</x-layouts.app>
