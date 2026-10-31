{{--
  手引きの入口の「ほかにも」：今日の練習・ホームズの10のわざ・3つの考え方を、絵つきの行で並べる（押すとその場所へ進む）。
  名推理カード（いちばん大きいボタン）の下に置き、2番目以降の行き先として控えめに見せる。
  使い方：<x-guide.entry-list :entries="$overview['entries']" :practice="$practice" />
  entries：overview.json の entries（key・route・anchor・icon・name・description）。practice：今日の練習（DailyPractice。なければ null）
--}}
@props(['entries', 'practice' => null])
<ul {{ $attributes->class(['divide-y divide-rule border-y border-rule']) }}>
  {{-- 行き先を1つずつ、絵・名前・一言の行にする --}}
  @foreach ($entries as $entry)
    {{-- 今日の練習の行は、その日の練習の場所へ飛び、一言に練習の名前を入れる。ほかの行は決まった行き先と一言 --}}
    @if ($entry['key'] === 'practices')
      @php($href = route($entry['route']).($practice ? '#practice-'.$practice['key'] : ''))
      @php($text = $practice ? __($entry['description'], ['name' => $practice['name']]) : $entry['description_none'])
    @else
      @php($href = route($entry['route']).(isset($entry['anchor']) ? '#'.$entry['anchor'] : ''))
      @php($text = $entry['description'])
    @endif
    <li>
      <a href="{{ $href }}" class="flex min-h-16 items-center gap-3 py-3 no-underline hover:bg-mark">
        <span class="grid size-11 shrink-0 place-items-center rounded-full border-2 border-ink text-ink">
          <x-ui.step-icon :name="$entry['icon']" class="size-6" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-lg font-bold">{{ $entry['name'] }}</span>
          <span class="block text-sm text-muted">{{ $text }}</span>
        </span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 shrink-0 text-muted"><path d="m9 6 6 6-6 6"/></svg>
      </a>
    </li>
  @endforeach
</ul>
