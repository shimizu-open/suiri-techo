{{--
  練習メニューの一覧。上から：使い方の一言 → 11種の名前のボタン（押すと下の説明へ飛ぶ）→ 練習メニュー11種（名前・わざ・もとの場面・説明・子ども向けの説明・「この練習を始める」）。
  中身は practices.json（GuideLibrary::practices()）。始めると事件を作る画面（cases.create）へ問いかけ入りで進む
--}}
<x-layouts.app :title="config('suiri.guide.titles.practices')">
  <x-guide.tabs current="guide.practices.index" />

  <p class="text-base leading-relaxed">{{ config('suiri.guide.practices.lead') }}</p>

  <nav aria-label="{{ config('suiri.guide.practices.nav') }}" class="mt-4">
    <ul class="flex flex-wrap gap-2">
      {{-- 練習メニューを1つずつ、下の説明へ飛ぶボタンにする（11種をひと目で見て選べるように） --}}
      @foreach ($practices as $practice)
        <li>
          <a href="#practice-{{ $practice['key'] }}" class="inline-flex min-h-11 items-center rounded-full border-2 border-rule bg-sheet px-3.5 font-semibold no-underline hover:border-ink">{{ $practice['name'] }}</a>
        </li>
      @endforeach
    </ul>
  </nav>

  <div class="mt-8 space-y-10">
    {{-- 練習メニューを1つずつ出す --}}
    @foreach ($practices as $practice)
      <x-guide.practice-item :practice="$practice" :skill-names="$skillNames" :kid="$kid" />
    @endforeach
  </div>
</x-layouts.app>
