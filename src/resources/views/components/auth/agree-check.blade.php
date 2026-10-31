{{--
  同意の画面の1つのチェック。行全体を押せる大きな枠にし、チェックすると枠が紺になる（子どもの指でも押しやすい）。エラーがあれば下に赤い一文を出す。
  使い方：<x-auth.agree-check name="confirm_age">13歳以上です。…</x-auth.agree-check>。name：送る項目の名前。中身（slot）：チェックの文
--}}
@props(['name'])
<div {{ $attributes }}>
  <label @class([
    'flex min-h-14 cursor-pointer items-start gap-3 rounded-xl border-2 bg-sheet px-4 py-3 has-checked:border-ink has-checked:bg-mark',
    'border-accent' => $errors->has($name),
    'border-rule' => ! $errors->has($name),
  ])>
    <input type="checkbox" name="{{ $name }}" value="1" class="mt-0.5 size-6 shrink-0 accent-[var(--ink)]" @checked(old($name))>
    <span class="text-base leading-relaxed">{{ $slot }}</span>
  </label>
  @error($name)
    <p class="mt-1 text-sm text-accent">{{ $message }}</p>
  @enderror
</div>
