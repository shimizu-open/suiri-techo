{{--
  「何分かかりそう？」「実際は何分だった？」の欄。よく使う分（15・30・60）のボタンと、ほかの分を書く欄を横に並べる。
  ボタンは押すと欄にその数を入れるだけ（resources/js/parts/minute-chips.js）。JavaScript が動かないときはボタンを隠し、欄に書けばよい。
  使い方：<x-task.minutes-field name="duration_minutes" :label="…" :value="$task->duration_minutes" />
  name：送る名前。label：問いの見出し。value：いまの値（送り直したときの入力 old を優先する）
--}}
@props(['name', 'label', 'value' => null])
@php($id = 'f-'.$name)
@php($current = (string) old($name, $value))
<fieldset data-minute-chips class="space-y-2">
  <legend class="mb-2 block text-base font-semibold">{{ $label }}</legend>
  <div class="flex flex-wrap items-center gap-2">
    {{-- よく使う分を1つずつ取り出し、押すと欄に入るボタンにする（いまの値と同じなら塗っておく） --}}
    @foreach (config('suiri.tasks.minute_choices') as $minutes)
      <button type="button" hidden data-minutes="{{ $minutes }}" aria-pressed="{{ $current === (string) $minutes ? 'true' : 'false' }}"
        class="inline-flex min-h-12 cursor-pointer items-center rounded-full border-2 border-rule bg-sheet px-4 text-base font-bold text-ink hover:border-ink aria-pressed:border-ink aria-pressed:bg-ink aria-pressed:text-on-ink">
        <x-task.minutes :value="$minutes" />
      </button>
    @endforeach
    <span class="inline-flex items-center gap-2">
      <label for="{{ $id }}" class="sr-only">{{ $label }}（{{ config('suiri.tasks.words.minutes_other') }}）</label>
      <input id="{{ $id }}" type="number" name="{{ $name }}" value="{{ $current }}" min="1" max="{{ config('suiri.tasks.max_minutes') }}" inputmode="numeric"
        placeholder="{{ config('suiri.tasks.words.minutes_other') }}" @error($name) aria-invalid="true" @enderror
        @class([
          'block min-h-12 w-28 rounded-xl border-2 bg-sheet px-3 text-lg text-ink placeholder:text-sm placeholder:text-muted focus:border-ink focus:outline-none',
          'border-accent' => $errors->has($name),
          'border-rule' => ! $errors->has($name),
        ])>
      <span class="text-base">{{ config('suiri.tasks.words.minutes_unit') }}</span>
    </span>
  </div>
  @error($name)
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
</fieldset>
