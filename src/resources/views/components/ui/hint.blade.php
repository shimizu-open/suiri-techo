{{--
  やわらかい説明の吹き出し。言葉の説明（「見立て＝『どうしてだろう？』の答えの案」）やワトソンのひとことを、同じ見た目で出す。
  使い方：<x-ui.hint>見立て＝「どうしてだろう？」の答えの案だよ</x-ui.hint>
          <x-ui.hint label="ワトソンのひとこと" icon="sparkle">ほかの理由も考えてみない？<x-slot:actions>…閉じるボタン…</x-slot:actions></x-ui.hint>
  label：吹き出しの上の小さな名前（省略可）。icon：<x-ui.step-icon> の名前（既定 sparkle。:icon="null" で出さない）。
  tone：clue・hypothesis・prediction・check・answer・plain（既定。絵の色と吹き出しの面の色）。actions：右下に置く小さなボタン（省略可）
--}}
@props(['label' => null, 'icon' => 'sparkle', 'tone' => 'plain'])
{{-- 段の種類ごとの色の組み合わせ（face：話し手の丸／bubble：吹き出しの面と枠／label：小さな名前）。check は予想と同じ緑 --}}
@php($tones = [
  'clue' => ['face' => 'border-clue/40 bg-clue-soft text-clue', 'bubble' => 'border-clue/30 bg-clue-soft', 'label' => 'text-clue'],
  'hypothesis' => ['face' => 'border-hypothesis/40 bg-hypothesis-soft text-hypothesis', 'bubble' => 'border-hypothesis/30 bg-hypothesis-soft', 'label' => 'text-hypothesis'],
  'prediction' => ['face' => 'border-prediction/40 bg-prediction-soft text-prediction', 'bubble' => 'border-prediction/30 bg-prediction-soft', 'label' => 'text-prediction'],
  'check' => ['face' => 'border-prediction/40 bg-prediction-soft text-prediction', 'bubble' => 'border-prediction/30 bg-prediction-soft', 'label' => 'text-prediction'],
  'answer' => ['face' => 'border-answer/40 bg-answer-soft text-answer', 'bubble' => 'border-answer/30 bg-answer-soft', 'label' => 'text-answer'],
  'plain' => ['face' => 'border-rule bg-mark text-ink', 'bubble' => 'border-rule bg-sheet', 'label' => 'text-muted'],
])
@php($c = $tones[$tone] ?? $tones['plain'])
<div {{ $attributes->class(['flex items-start gap-2']) }}>
  {{-- 絵が渡されていれば、吹き出しの左に話し手の丸として出す --}}
  @if ($icon)
    <span class="mt-1 grid size-9 shrink-0 place-items-center rounded-full border {{ $c['face'] }}">
      <x-ui.step-icon :name="$icon" class="size-5" />
    </span>
  @endif
  <div class="min-w-0 flex-1 rounded-2xl rounded-tl-sm border px-4 py-3 text-base leading-relaxed {{ $c['bubble'] }}">
    {{-- 小さな名前（ワトソンのひとこと など）があれば、吹き出しの上に出す --}}
    @if ($label)
      <p class="mb-0.5 text-sm font-bold {{ $c['label'] }}">{{ $label }}</p>
    @endif
    <div>{{ $slot }}</div>
    {{-- 閉じるなどの小さなボタンが渡されていれば、吹き出しの下に右寄せで出す --}}
    @isset($actions)
      <div class="mt-2 flex justify-end gap-2">{{ $actions }}</div>
    @endisset
  </div>
</div>
