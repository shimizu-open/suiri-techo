{{--
  いまやることのカード「もしそうなら、何が見えるはず？」（予想を立てる。送り先は predictions.store・入力は statement・confidence・check_on・
  check_at・attic_note_id）。1問ずつ見せる：① 見えるはずのこと → 書くと ② どのくらい自信ある？ → 選ぶと ③ いつ確かめる？（大きな日付と候補）。
  次の問いは、前の問いに答えるまで隠す（CSS の :has。:has が使えない端末では全部の問いが縦に並ぶ）。主なボタンはいつもカードの下にある。
  保存すると文と自信は直せないことを、ボタンの上で知らせる。主なボタンの下に、言葉の説明かワトソンのひとことを1つ添える。
  使い方：<x-case-step.predict-card :case :hypothesis :kid :number :total :note :prompt :template :dates :attic :attic-options :today :check-default />
  template：予想の穴埋めの型（「型を使う」で欄に入る）。dates：確かめる日の候補。attic・attic-options：屋根裏の棚の候補と選べる知識
--}}
@props([
    'case', 'hypothesis', 'kid' => false, 'number' => null, 'total' => null, 'note' => null, 'prompt' => null, 'template' => null,
    'dates' => [], 'attic' => [], 'atticOptions' => [], 'today' => null, 'checkDefault' => null,
])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
@php($work = $case->scene === \App\Enums\Scene::Work)
@php($input = 'block min-h-12 w-full rounded-xl border border-rule bg-sheet px-3.5 py-2.5 text-base text-ink focus:border-ink focus:outline-none')
<x-ui.step-card :step="$number" :total="$total" icon="arrow" tone="prediction" :title="$t($work ? 'predict.title_work' : 'predict.title')"
  :hint="$prompt ?? $t('predict.hint')">
  <form method="POST" action="{{ route('predictions.store', $hypothesis) }}" class="group/predict space-y-5">
    @csrf
    <input type="hidden" name="form_key" value="predict-{{ $hypothesis->id }}">
    <blockquote class="border-l-2 border-prediction pl-3">
      <p class="text-sm font-bold text-prediction">{{ $work ? \App\Support\Label::word('hypothesis', $case->scene) : ($kid ? \App\Support\Label::kidWord('hypothesis') : $t('predict.quote')) }}</p>
      <p class="leading-relaxed font-semibold break-words">{{ $hypothesis->body }}</p>
    </blockquote>
    <div class="space-y-1">
      <x-ui.textarea id="f-step-statement" name="statement" :label="$t('predict.label')" rows="2" required
        :maxlength="config('suiri.limits.prediction_statement')" :placeholder="$t('predict.placeholder')" />
      <x-case.suggest-chips :items="filled($template) ? [['label' => $t('predict.template'), 'text' => $template]] : []" target="f-step-statement" />
    </div>

    {{-- ② と ③：見えるはずのことを書くまで隠す --}}
    <div class="space-y-5 group-has-[textarea[name=statement]:placeholder-shown]/predict:hidden">
      <x-case-step.confidence :label="$t('predict.confidence')" :kid="$kid" />
      {{-- ③：自信を選ぶまで隠す --}}
      <div class="space-y-4 group-not-has-[input[name=confidence]:checked]/predict:hidden">
        <x-case-step.check-date :label="$t('predict.check_on')" :dates="$dates" :today="$today" :check-default="$checkDefault" />
        <details @if ($errors->hasAny(['check_at', 'attic_note_id'])) open @endif class="group/more">
          <summary class="inline-flex min-h-12 cursor-pointer items-center gap-1 text-sm font-semibold text-muted">
            <span aria-hidden="true" class="group-open/more:rotate-90">›</span>{{ $t('predict.more') }}
          </summary>
          <div class="mt-2 space-y-4">
            <x-ui.field type="time" name="check_at" id="f-step-check-at" :label="$t('predict.check_at')" />
            {{-- 屋根裏の棚に知識があれば、使う知識を選べるようにする --}}
            @if ($atticOptions !== [])
              <div class="space-y-1">
                <label for="f-step-attic" class="block text-base font-semibold">{{ $t('predict.attic') }}</label>
                <x-case.suggest-chips :items="$attic" />
                <select id="f-step-attic" name="attic_note_id" class="{{ $input }}">
                  <option value="">{{ $t('predict.attic_none') }}</option>
                  {{-- 自分の棚の知識（つまり？）を1つずつ出す --}}
                  @foreach ($atticOptions as $id => $rule)
                    <option value="{{ $id }}" @selected((int) old('attic_note_id') === $id)>{{ \Illuminate\Support\Str::limit($rule, 60, '…') }}</option>
                  @endforeach
                </select>
                @error('attic_note_id')
                  <p class="text-sm text-accent">{{ $message }}</p>
                @enderror
              </div>
            @endif
          </div>
        </details>
        <p class="rounded-xl bg-mark px-4 py-3 text-sm leading-relaxed">{{ $t('predict.lock') }}</p>
      </div>
    </div>
    <x-ui.big-button tone="prediction" icon="calendar">{{ $t('predict.button') }}</x-ui.big-button>
    <x-ui.privacy-note :scene="$case->scene" />
  </form>
  <x-case-step.bubble :note="$note" :case="$case" :explain="$t('predict.explain')" />
</x-ui.step-card>
