{{--
  事件を作る画面の入力欄（1行か複数行）。見出し（label の slot）・入力欄・その下の一言・エラーを1つにまとめる。
  使い方：<x-case-new.field name="title" :value="$draft['title']" required maxlength="120" tip="「〜はなぜ？」の形にすると…">なぞ</x-case-new.field>
          <x-case-new.field name="clue_body" :rows="3">見たこと<x-slot:above>…書き出しのボタン…</x-slot:above></x-case-new.field>
  rows：あれば複数行（textarea）、なければ1行（input）。quiet：見出しを読み上げだけにする（問いの見出しと同じ言葉のとき）。id は f-new-{name}（書き出しのボタンの入れ先に使う）。
  値は送り直したときの入力（old）を優先する。エラーには data-error を付け、stepper.js がその段から見せる。ほかの属性は入力欄に付く
--}}
@props(['name', 'value' => null, 'rows' => null, 'tip' => null, 'required' => false, 'quiet' => false])
@php($id = 'f-new-'.$name)
@php($classes = ['block w-full rounded-xl border bg-sheet px-3.5 py-3 text-base leading-relaxed text-ink placeholder:text-muted focus:border-ink focus:outline-none', 'border-accent' => $errors->has($name), 'border-rule' => ! $errors->has($name)])
<div class="space-y-1.5">
  <label for="{{ $id }}" @class(['block text-base font-semibold', 'sr-only' => $quiet])>{{ $slot }}</label>
  {{ $above ?? '' }}
  {{-- 複数行の欄か、1行の欄かを rows で分ける --}}
  @if ($rows)
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) @error($name) aria-invalid="true" @enderror
      {{ $attributes->class($classes) }}>{{ old($name, $value) }}</textarea>
  @else
    <input id="{{ $id }}" name="{{ $name }}" type="text" value="{{ old($name, $value) }}" @required($required) @error($name) aria-invalid="true" @enderror
      {{ $attributes->class($classes) }}>
  @endif
  {{-- 書き方の一言があれば、入力欄の下に小さく出す --}}
  @if ($tip)
    <p class="text-sm text-muted">{{ $tip }}</p>
  @endif
  @error($name)
    <p data-error class="text-sm text-accent">{{ $message }}</p>
  @enderror
</div>
