{{--
  選ぶ欄（プルダウン）。使い方：<x-ui.select name="scene" label="場面" :options="\App\Enums\Scene::options()" :selected="$case->scene" />
  options：「値 => 言葉」の配列（列挙の options() をそのまま渡せる）。selected：選んでおく値（列挙でも文字でもよい）。
  placeholder を渡すと、先頭に「選ばない」の行を足す
--}}
@props(['name', 'label', 'options' => [], 'selected' => null, 'hint' => null, 'required' => false, 'placeholder' => null])
@php($id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name)))
@php($current = (string) old($name, $selected instanceof \BackedEnum ? $selected->value : $selected))
<div class="space-y-1">
  <label for="{{ $id }}" class="block text-base font-semibold">
    {{ $label }}
    {{-- 必須の項目には印を付ける --}}
    @if ($required)
      <span class="ml-1 text-xs font-normal text-accent">必須</span>
    @endif
  </label>
  <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes->except('id')->class([
    'block w-full rounded-xl border bg-sheet px-3.5 py-3 text-base text-ink focus:border-ink focus:outline-none',
    'border-accent' => $errors->has($name),
    'border-rule' => ! $errors->has($name),
  ]) }}>
    {{-- 「選ばない」の行を出すように頼まれたら、先頭に足す --}}
    @if ($placeholder !== null)
      <option value="">{{ $placeholder }}</option>
    @endif
    {{-- 選択肢を1つずつ取り出して並べる。いまの値と同じものを選んでおく --}}
    @foreach ($options as $value => $text)
      <option value="{{ $value }}" @selected((string) $value === $current)>{{ $text }}</option>
    @endforeach
  </select>
  {{-- 補足の説明があれば、選ぶ欄の下に小さく出す --}}
  @if ($hint)
    <p class="text-sm text-muted">{{ $hint }}</p>
  @endif
  @error($name)
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
</div>
