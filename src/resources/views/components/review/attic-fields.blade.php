{{--
  屋根裏の棚の知識の入力欄だけ（つまり、どんな決まり？・たとえば？・どの事件で見つけた？）。個人情報の注意書きは、使う側がボタンの下に置く。
  自分で書くカード（<x-review.attic-add>）と、その場で直す欄（<x-review.attic-form>）の両方で使う。<form> は使う側が置く。
  どの事件で見つけた？は選ばなくてよいので、たたんでおく（選んである・直すときは開いておく）。
  使い方：<x-review.attic-fields :note="$note" :case-options="$caseOptions" />。note：直す知識（書くときは null）。caseOptions：「事件の id => なぞ」
--}}
@props(['note' => null, 'caseOptions' => []])
@php($a = config('suiri.review.attic'))
@php($suffix = $note?->id ?? 'new')
<div {{ $attributes->class(['space-y-4']) }}>
  <x-ui.textarea name="rule" :label="$a['rule']['label']" :value="$note?->rule" rows="2" required
    :maxlength="config('suiri.limits.attic_rule')" :id="'rule-'.$suffix" :placeholder="$a['rule']['placeholder']" />
  <x-ui.textarea name="examples" :label="$a['examples']['label']" :value="$note?->examples" rows="2"
    :maxlength="config('suiri.limits.attic_examples')" :id="'examples-'.$suffix" :placeholder="$a['examples']['placeholder']" />
  {{-- 事件を選べるときだけ、たたんだ選ぶ欄を出す（選んである・送り直しのときは開いておく） --}}
  @if ($caseOptions)
    <details class="group" @if ($note?->case_file_id || old('case_file_id')) open @endif>
      <summary class="inline-flex min-h-11 cursor-pointer items-center gap-1 text-sm font-bold text-muted hover:text-ink">
        {{ $a['case'] }}
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4 group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
      </summary>
      <x-ui.select name="case_file_id" :label="$a['case_label']" :options="$caseOptions" :selected="$note?->case_file_id" placeholder="選ばない" :id="'case-'.$suffix" />
    </details>
  @endif
</div>
