{{--
  技能（わざ）の画面（S1〜S10）。上から：大きな「子ども向け・大人向け」の切り替え → 名前と一言・説明（選んだ向き）→ 原作の引用
  → ふだんの練習の例（大人・子ども・施術者）とやりがちなこと → このわざを練習できる練習メニューと名推理カード → 前と次のわざ。
  中身は skills.json（GuideLibrary::skill()）。言葉は config('suiri.guide.skill')
--}}
<x-layouts.app :title="__(config('suiri.guide.skill.page_title'), ['key' => $skill['key']])">
  <x-guide.tabs current="guide.index" />

  <x-guide.audience-toggle :kid="$kid" />

  <div class="mt-5">
    <p class="text-sm font-bold text-muted tabular-nums">{{ __(config('suiri.guide.skill.kicker'), ['key' => $skill['key']]) }}</p>
    <h2 class="text-2xl leading-snug">{{ $kid ? $skill['kid_name'] : $skill['name'] }}</h2>
    <p class="text-sm text-muted">{{ $kid ? $skill['name'] : __(config('suiri.guide.skill.kid_name'), ['name' => $skill['kid_name']]) }}</p>
  </div>

  <p class="mt-4 text-lg font-bold leading-relaxed">{{ $skill['summary'] }}</p>
  <p class="mt-2 leading-loose">{{ $kid ? $skill['kid_body'] : $skill['body'] }}</p>

  <x-guide.quote :text="$skill['quote_en']" class="mt-6">{{ $skill['quote_source'] }}</x-guide.quote>

  <x-guide.skill-practice :practice="$skill['practice']" :pitfall="$skill['pitfall']" :kid="$kid" class="mt-8" />

  <section class="mt-10">
    <h2 class="text-xl">{{ config('suiri.guide.skill.try') }}</h2>
    <p class="mb-3 text-base text-muted">{{ config('suiri.guide.skill.try_lead') }}</p>
    <div class="space-y-4">
      {{-- このわざの練習メニューを1つずつ出す --}}
      @foreach ($practices as $practice)
        <x-guide.practice-item :practice="$practice" :skill-names="$skillNames" :kid="$kid" />
      @endforeach
    </div>
    {{-- このわざを練習できる名推理カードがあれば、一覧で出す --}}
    @if ($scenes !== [])
      <h3 class="mt-6 text-lg">{{ config('suiri.guide.skill.scenes') }}</h3>
      <ul class="mt-1 divide-y divide-rule border-y border-rule">
        {{-- カードを1枚ずつ、題と作品名の行にする --}}
        @foreach ($scenes as $scene)
          <x-guide.scene-item :scene="$scene" />
        @endforeach
      </ul>
    @endif
  </section>

  <x-guide.pager :neighbors="$neighbors" route="guide.skills.show" label="name" class="mt-8" />
</x-layouts.app>
