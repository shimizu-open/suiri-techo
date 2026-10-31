{{--
  捜査ファイルの見出しのつまみ（段の色のタブ）が付いた紙。タブに絵と見出しを書き、下の紙に中身を置く。
  3つの考え方の1つ、技能の「ふだんの練習の例」など、手引きの中で「別の話」を区切るときに使う（何でも囲まず、区切りが要る所だけ）。
  使い方：<x-guide.section icon="question" tone="hypothesis" title="ホームズはこう考えた"> 中身 </x-guide.section>
  icon：<x-ui.step-icon> の名前（省略可）。tone：clue・hypothesis・prediction・check・answer・accent・plain（段の色。既定は plain＝紺）。
  title：タブの見出し。kicker：タブの右の小さな言葉（省略可）。中身（slot）：本文
--}}
@props(['icon' => null, 'tone' => 'plain', 'title', 'kicker' => null])
{{-- 段の色ごとの、タブ（塗り）と紙の枠のクラスの組み合わせ。check は予想と同じ緑 --}}
@php($tones = [
  'clue' => ['bg-clue', 'border-clue'],
  'hypothesis' => ['bg-hypothesis', 'border-hypothesis'],
  'prediction' => ['bg-prediction', 'border-prediction'],
  'check' => ['bg-prediction', 'border-prediction'],
  'answer' => ['bg-answer', 'border-answer'],
  'accent' => ['bg-accent', 'border-accent'],
  'plain' => ['bg-ink', 'border-ink'],
])
@php([$tab, $border] = $tones[$tone] ?? $tones['plain'])
<section {{ $attributes }}>
  <h3 @class(['inline-flex min-h-10 max-w-full items-center gap-2 rounded-t-xl px-3.5 py-1.5 text-base leading-snug text-on-ink', $tab])>
    {{-- 絵が渡されていれば、見出しの前に出す --}}
    @if ($icon)
      <x-ui.step-icon :name="$icon" class="size-5 shrink-0" />
    @endif
    <span>{{ $title }}</span>
    {{-- 小さな言葉が渡されていれば、見出しの右に出す --}}
    @if ($kicker)
      <span class="text-sm font-semibold opacity-80">{{ $kicker }}</span>
    @endif
  </h3>
  <div @class(['rounded-tr-xl rounded-b-xl border-2 bg-sheet px-4 py-4', $border])>{{ $slot }}</div>
</section>
