{{--
  複数行の入力欄（ラベル・入力・補足・エラー）。使い方：<x-ui.textarea name="body" label="手がかり" rows="3" maxlength="500" />
  ほかの属性は入力欄にそのまま付く。値は送り直したときの入力（old）を優先する
--}}
@props(['name', 'label', 'value' => null, 'hint' => null, 'required' => false, 'rows' => 3])
@php($id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name)))
<div class="space-y-1">
  <label for="{{ $id }}" class="block text-base font-semibold">
    {{ $label }}
    {{-- 必須の項目には印を付ける --}}
    @if ($required)
      <span class="ml-1 text-xs font-normal text-accent">必須</span>
    @endif
  </label>
  <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
    @error($name) aria-invalid="true" @enderror
    {{ $attributes->except('id')->class([
      'block w-full rounded-xl border bg-sheet px-3.5 py-3 text-base leading-relaxed text-ink placeholder:text-muted focus:border-ink focus:outline-none',
      'border-accent' => $errors->has($name),
      'border-rule' => ! $errors->has($name),
    ]) }}>{{ old($name, $value) }}</textarea>
  {{-- 補足の説明があれば、入力欄の下に小さく出す --}}
  @if ($hint)
    <p class="text-sm text-muted">{{ $hint }}</p>
  @endif
  @error($name)
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
</div>
