{{--
  1行の入力欄（ラベル・入力・補足・エラー）。使い方：<x-ui.field name="title" label="なぞ" :value="$case->title" required maxlength="120" />
  ほかの属性（maxlength・placeholder・list・autocomplete など）は入力欄にそのまま付く。値は送り直したときの入力（old）を優先する
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])
@php($id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name)))
<div class="space-y-1">
  <label for="{{ $id }}" class="block text-base font-semibold">
    {{ $label }}
    {{-- 必須の項目には印を付ける --}}
    @if ($required)
      <span class="ml-1 text-xs font-normal text-accent">必須</span>
    @endif
  </label>
  <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
    @error($name) aria-invalid="true" @enderror
    {{ $attributes->except('id')->class([
      'block w-full rounded-xl border bg-sheet px-3.5 py-3 text-base text-ink placeholder:text-muted focus:border-ink focus:outline-none',
      'border-accent' => $errors->has($name),
      'border-rule' => ! $errors->has($name),
    ]) }}>
  {{-- 補足の説明があれば、入力欄の下に小さく出す --}}
  @if ($hint)
    <p class="text-sm text-muted">{{ $hint }}</p>
  @endif
  @error($name)
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
</div>
