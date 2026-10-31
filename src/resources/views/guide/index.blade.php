{{--
  手引きの入口。上から：いちばん大きく「名推理カードで遊ぼう」（次のカードの見本と大きなボタン）→ ほかの行き先（今日の練習・10のわざ・3つの考え方）
  → ホームズの10のわざを手帳の段ごとに（段の絵と色、初めて出る言葉の一言の説明つき）。
  入口では行き先の一覧そのものが案内なので、ほかの手引きの画面の上にあるタブ（<x-guide.tabs>）は置かない（同じ行き先を二重に出さない）。
  中身は overview.json（GuideLibrary::overview()）。わざの名前と説明は skills.json から引く
--}}
<x-layouts.app :title="config('suiri.guide.titles.index')">
  <x-guide.play-card :play="$overview['play']" :scene="$nextScene" :number="$nextNumber" :total="count($scenes)" :fresh="$nextFresh" />

  <x-guide.entry-list :entries="$overview['entries']" :practice="$practice" class="mt-6" />

  <section id="skills" class="mt-10 scroll-mt-20">
    <h2 class="text-2xl">{{ $overview['flow_title'] }}</h2>
    <p class="mt-1 mb-3 text-base text-muted">{{ $overview['flow_intro'] }}</p>
    <x-guide.flow-steps :steps="$overview['steps']" :skills="$skills" :scenes="$scenes" :reasoning-names="$reasoningNames" :kid="$kid" />
  </section>
</x-layouts.app>
