{{--
  証拠品の荷札。手がかり1つ＝1枚の札にして、左の角を落とした形とひもを通す穴で「集めた証拠」だとわかるようにする。
  使い方：<x-ui.evidence-tag :no="3" label="見た">今週、体操着を2回わすれた<x-slot:actions>…○×や消すボタン…</x-slot:actions></x-ui.evidence-tag>
  no：札の番号（「No.3」。省略可）。label：種類の言葉（見た・聞いた・なかった。省略可）。中身（slot）：手がかりの文。
  tone：clue（既定・紺）・hypothesis・prediction・check・answer・plain。actions：札の右に置く小さなボタン（省略可）
--}}
@props(['no' => null, 'label' => null, 'tone' => 'clue'])
{{-- 段の種類ごとの色の組み合わせ（face：札の面／hole：ひもの穴の縁／head：番号と種類の文字）。check は予想と同じ緑 --}}
@php($tones = [
  'clue' => ['face' => 'bg-clue-soft', 'hole' => 'border-clue/60', 'head' => 'text-clue'],
  'hypothesis' => ['face' => 'bg-hypothesis-soft', 'hole' => 'border-hypothesis/60', 'head' => 'text-hypothesis'],
  'prediction' => ['face' => 'bg-prediction-soft', 'hole' => 'border-prediction/60', 'head' => 'text-prediction'],
  'check' => ['face' => 'bg-prediction-soft', 'hole' => 'border-prediction/60', 'head' => 'text-prediction'],
  'answer' => ['face' => 'bg-answer-soft', 'hole' => 'border-answer/60', 'head' => 'text-answer'],
  'plain' => ['face' => 'bg-mark', 'hole' => 'border-muted/60', 'head' => 'text-muted'],
])
@php($c = $tones[$tone] ?? $tones['plain'])
<div {{ $attributes->class(['relative flex items-start gap-3 py-3 pr-3 pl-10 [clip-path:polygon(18px_0,100%_0,100%_100%,18px_100%,0_calc(100%_-_18px),0_18px)]', $c['face']]) }}>
  <span aria-hidden="true" class="absolute top-1/2 left-3.5 size-3.5 -translate-y-1/2 rounded-full border-2 bg-paper {{ $c['hole'] }}"></span>
  <div class="min-w-0 flex-1">
    {{-- 番号か種類があれば、札の上の小さな見出しにする（例：No.3・見た） --}}
    @if ($no !== null || $label)
      <p class="text-xs font-bold tracking-wide {{ $c['head'] }}">
        {{-- 番号があれば「No.3」の形で出す --}}
        @if ($no !== null)
          <span>{{ __(config('suiri.ui.tag_no'), ['n' => $no]) }}</span>
        @endif
        {{-- 番号と種類の両方があるときだけ、間に点を入れる --}}
        @if ($no !== null && $label)
          <span aria-hidden="true">・</span>
        @endif
        <span>{{ $label }}</span>
      </p>
    @endif
    <div class="text-base leading-relaxed">{{ $slot }}</div>
  </div>
  {{-- 小さなボタン（○×・消す）が渡されていれば、札の右に出す --}}
  @isset($actions)
    <div class="flex shrink-0 items-center gap-1">{{ $actions }}</div>
  @endisset
</div>
