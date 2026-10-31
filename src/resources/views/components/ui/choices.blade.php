{{--
  横に並んだ選択ボタン（ラジオボタン）。選択肢が2〜5個で、ひと目で全部見せたいときに使う（手がかりの種類・場面など）。
  使い方：<x-ui.choices name="kind" label="種類" :options="\App\Enums\ClueKind::options($case->scene)" selected="seen" />
  options：「値 => 言葉」の配列。selected：選んでおく値（列挙でも文字でもよい）
--}}
@props(['name', 'label', 'options' => [], 'selected' => null, 'hint' => null])
@php($current = (string) old($name, $selected instanceof \BackedEnum ? $selected->value : $selected))
<fieldset class="space-y-1">
  <legend class="mb-1 block text-base font-semibold">{{ $label }}</legend>
  <div {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    {{-- 選択肢を1つずつ取り出し、押せる札にする。いまの値と同じものを選んでおく --}}
    @foreach ($options as $value => $text)
      <label class="cursor-pointer">
        <input type="radio" name="{{ $name }}" value="{{ $value }}" class="peer sr-only" @checked((string) $value === $current)>
        <span class="inline-flex min-h-12 items-center rounded-xl border border-rule bg-sheet px-4 text-base font-semibold text-muted peer-checked:border-ink peer-checked:bg-ink peer-checked:text-on-ink peer-focus-visible:outline-2 peer-focus-visible:outline-accent">{{ $text }}</span>
      </label>
    @endforeach
  </div>
  {{-- 補足の説明があれば、下に小さく出す --}}
  @if ($hint)
    <p class="text-sm text-muted">{{ $hint }}</p>
  @endif
  @error($name)
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
</fieldset>
