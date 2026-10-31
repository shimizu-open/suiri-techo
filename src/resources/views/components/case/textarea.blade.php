{{--
  事件の画面で使う複数行の入力欄。1つの画面に同じ名前の欄（body など）を持つフォームがいくつもあるので、
  form-key を渡すと「その form_key のフォームから送り直したとき」だけ、入力の残り（old）とエラーを出す。
  使い方：<x-case.textarea name="body" form-key="add-clue" label="手がかり" rows="2" />（ほかの属性は入力欄に付く）
  label の代わりに <x-slot:caption> で見出しを渡せる（場面で言葉を出し分けるとき）。
  <x-slot:above> に渡したもの（書き出しの提案のボタンなど）は、見出しと入力欄のあいだ（入力欄の真上）に置く
--}}
@props(['name', 'label' => null, 'formKey' => null, 'value' => null, 'hint' => null, 'rows' => 3, 'required' => false])
@php($mine = $formKey === null || old('form_key') === $formKey)
@php($id = 'f-'.($formKey ?? 'case').'-'.$name)
@php($error = $mine ? $errors->first($name) : null)
<div class="space-y-1">
  <label for="{{ $id }}" class="block text-sm font-semibold">
    {{ $caption ?? $label }}
    {{-- 必須の項目には印を付ける --}}
    @if ($required)
      <span class="ml-1 text-xs font-normal text-accent">必須</span>
    @endif
  </label>
  {{ $above ?? '' }}
  <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) @if ($error) aria-invalid="true" @endif
    {{ $attributes->class([
      'block w-full rounded-lg border bg-sheet px-3 py-2.5 text-base leading-relaxed text-ink placeholder:text-muted focus:border-ink focus:outline-none',
      'border-accent' => $error,
      'border-rule' => ! $error,
    ]) }}>{{ $mine ? old($name, $value) : $value }}</textarea>
  {{-- 補足の説明があれば、入力欄の下に小さく出す --}}
  @if ($hint)
    <p class="text-xs text-muted">{{ $hint }}</p>
  @endif
  {{-- このフォームから送ったときのエラーだけを出す --}}
  @if ($error)
    <p class="text-sm text-accent">{{ $error }}</p>
  @endif
</div>
