{{--
  「どのくらい自信ある？」の大きな選択（確かさ。50〜95％の5刻み。100％は置かない）。札1枚に数と目安の言葉（親子の人は子ども向けの言い方）。
  選択肢は config の confidence.choices。初めはどれも選ばない（自分で選んでもらうため）。入力の名前は confidence。
  使い方：<x-case-step.confidence label="どのくらい自信ある？" :kid="$kid" />（送り直したときは、前に選んだ札を選んでおく）
--}}
@props(['label', 'kid' => false])
<fieldset class="space-y-2">
  <legend class="mb-1 block text-base font-semibold">{{ $label }}</legend>
  <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
    {{-- 確かさの選択肢を1つずつ、押せる札にする --}}
    @foreach (config('suiri.confidence.choices') as $value => $choice)
      <label class="cursor-pointer">
        <input type="radio" name="confidence" value="{{ $value }}" required class="peer sr-only" @checked((int) old('confidence') === $value)>
        <span class="flex min-h-14 flex-col items-center justify-center rounded-xl border-2 border-rule bg-sheet px-2 py-1.5 text-center leading-tight peer-checked:border-prediction peer-checked:bg-prediction peer-checked:text-on-ink peer-focus-visible:outline-2 peer-focus-visible:outline-accent">
          <span class="text-lg font-bold">{{ $value }}%</span>
          <span class="mt-0.5 text-xs">{{ $kid ? $choice['kid'] : $choice['hint'] }}</span>
        </span>
      </label>
    @endforeach
  </div>
  @error('confidence')
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
</fieldset>
