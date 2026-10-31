{{--
  「いつ確かめる？」の大きな日付と、すぐ選べる日のボタン（明日・3日後・1週間後と、カレンダーとつないでいれば「次の『◯◯』の日」）。
  ボタンを押すと日付と時刻が入るだけ（時刻のない候補は時刻を空にする。動きは resources/js/parts/suggest-chips.js）。入力の名前は check_on。
  JavaScript がなくても日付の欄で選べる。押しても何も起きないボタンを見せないよう、ボタンの並びは初めは隠し（hidden・data-suggest-chips）、JavaScript が動いたら見せる。
  使い方：<x-case-step.check-date label="いつ確かめる？" :dates="$suggest['dates']" :today="$today" :check-default="$checkDefault" />
  dates：CheckDateSuggestionsQuery の値（label・date・time・from）。初めの日付は check-default（明日）、送り直したときは前の値
--}}
@props(['label', 'dates' => [], 'today' => null, 'checkDefault' => null])
<div class="space-y-2">
  <label for="f-step-check-on" class="block text-base font-semibold">{{ $label }}</label>
  <input id="f-step-check-on" type="date" name="check_on" required min="{{ $today }}" value="{{ old('check_on', $checkDefault) }}"
    @error('check_on') aria-invalid="true" @enderror
    class="block min-h-14 w-full rounded-xl border-2 border-prediction bg-sheet px-4 py-2 text-xl font-bold text-ink focus:outline-2 focus:outline-prediction">
  {{-- すぐ選べる日があれば、日付の下に大きなボタンで並べる（カレンダーの予定は横いっぱいに） --}}
  @if ($dates !== [])
    <div hidden data-suggest-chips class="grid grid-cols-3 gap-2">
      {{-- 候補を1つずつ、押すと日付が入るボタンにする（送信はしない） --}}
      @foreach ($dates as $date)
        <button type="button" data-suggest data-date="{{ $date['date'] }}" data-time="{{ $date['time'] ?? '' }}" @class([
          'min-h-12 cursor-pointer rounded-xl border border-rule bg-sheet px-2 text-base font-semibold text-ink hover:border-prediction hover:bg-prediction-soft',
          'col-span-3 px-4 text-left' => ($date['from'] ?? 'quick') === 'calendar',
        ])>{{ $date['label'] }}</button>
      @endforeach
    </div>
  @endif
  @error('check_on')
    <p class="text-sm text-accent">{{ $message }}</p>
  @enderror
</div>
