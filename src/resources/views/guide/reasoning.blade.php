{{--
  3つの考え方（演繹・帰納・アブダクション）の説明。上から：大きな「子ども向け・大人向け」の切り替え → 身近なたとえ（朝、道が濡れていたら？。
  大人にも子どもにも同じたとえで入る）→ はじめの説明（選んだ向き）→ 流れの順の3つ（アブダクション→演繹→帰納）→ 注意。
  中身は reasoning.json（GuideLibrary::reasoning()）。?kid=1 で子ども向け
--}}
<x-layouts.app :title="config('suiri.guide.titles.reasoning')">
  <x-guide.tabs current="guide.reasoning" />

  <x-guide.audience-toggle :kid="$kid" />

  <h2 class="mt-5 text-xl sm:text-2xl">{{ $reasoning['title'] }}</h2>

  <x-guide.rain-analogy :analogy="$reasoning['analogy']" :items="$reasoning['items']" class="mt-3" />

  <p class="mt-5 leading-relaxed">{{ $kid ? $reasoning['kid_intro'] : $reasoning['intro'] }}</p>

  <ol class="mt-8 space-y-8">
    {{-- 3つの考え方を流れの順に1つずつ出す --}}
    @foreach ($reasoning['items'] as $item)
      <li>
        <x-guide.reasoning-item :item="$item" :number="$loop->iteration" :kid="$kid" :scene="$scenes[$item['scene']] ?? null" />
      </li>
    @endforeach
  </ol>

  <x-ui.hint icon="norbury" class="mt-8">{{ $kid ? $reasoning['kid_note'] : $reasoning['note'] }}</x-ui.hint>
</x-layouts.app>
