{{--
  週の問い（R-3）を1問ずつ出す：①今週いちばん考えが変わった事件は？ → ②つまり、何がわかった？ → ③ほかに当てはまりそうなことは？
  いまの問いだけを大きなカード（<x-ui.step-card>）で出し、書いた問いはその下に小さくたたむ（<x-ui.done-summary>）。まだ先の問いは出さない。
  どの問いを出すか：送り直しでエラーのある問い → ?q=（changed_case・abstraction・concrete・done）で選んだもの → まだ書いていない最初の問い。
  ①は選ばずに進んでもよいので、その週の行があれば済みとみなす。3つとも済んだら（か ?q=done）、書いた答えの一覧と「直す」を出す。
  保存は3つの欄をまとめて送る作り（SaveWeeklyReflection）なので、いま答えない問いの値は hidden で送り直して消えないようにする。
  問いの補足は「なぜそうなった？」の形（文は config('suiri.review.questions')）。前の週を開いたときは「今週」を「この週」と読み替える。
  使い方：<x-review.reflection-steps :week="$week" :reflection="$reflection" :case-options="$caseOptions" />
  week：ReviewWeek。reflection：保存済みの WeeklyReflection か null。caseOptions：「事件の id => なぞ」（その週に動いた自分の事件）
--}}
@props(['week', 'reflection' => null, 'caseOptions' => []])
@php($q = config('suiri.review.questions'))
@php($fields = ['changed_case' => 'changed_case_file_id', 'abstraction' => 'abstraction', 'concrete' => 'concrete'])
@php($keys = array_keys($fields))
@php($values = ['changed_case' => $reflection?->changed_case_file_id, 'abstraction' => $reflection?->abstraction, 'concrete' => $reflection?->concrete])
@php($done = ['changed_case' => $reflection !== null, 'abstraction' => filled($values['abstraction']), 'concrete' => filled($values['concrete'])])
@php($errored = collect($fields)->search(fn (string $field): bool => $errors->has($field)))
@php($asked = in_array(request('q'), [...$keys, 'done'], true) ? request('q') : null)
@php($current = $errored ?: ($asked ?? (array_search(false, $done, true) ?: 'done')))
@php($thisWeek = fn (string $text): string => $week->isCurrent() ? $text : str_replace('今週', 'この週', $text))
@php($answer = fn (string $key): string => $key === 'changed_case' ? ($caseOptions[$values['changed_case']] ?? $q['not_chosen']) : (string) $values[$key])
@php($link = fn (string $key): string => route('review.show', ['week' => $week->param(), 'q' => $key]))
<div {{ $attributes }}>
  {{-- 3つとも済んだ（か「とばす」で最後まで来た）なら書いた答えの一覧、そうでなければいまの問いのカードを出す --}}
  @if ($current === 'done')
    <section aria-labelledby="reflection-done" class="border-t border-rule pt-4">
      <div class="flex items-center gap-3">
        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-ok-soft text-ok"><x-ui.step-icon name="check" class="size-6" /></span>
        <h2 id="reflection-done" class="text-lg">{{ $thisWeek($q['done']) }}</h2>
      </div>
      <dl class="mt-3 divide-y divide-rule border-t border-rule">
        {{-- 3つの問いを1つずつ取り出し、書いた答え（なければ「まだ書いていないよ」）と「直す」「書く」のリンクを並べる --}}
        @foreach ($keys as $key)
          <div class="flex items-start gap-3 py-3">
            <div class="min-w-0 flex-1">
              <dt class="text-sm text-muted">{{ $thisWeek($q[$key]['label']) }}</dt>
              <dd @class(['mt-0.5 text-base leading-relaxed', 'text-muted' => ! $done[$key]])>{{ $done[$key] ? $answer($key) : $q['not_yet'] }}</dd>
            </div>
            <a href="{{ $link($key) }}" class="inline-flex min-h-11 shrink-0 items-center rounded-lg px-2 text-sm font-bold no-underline hover:bg-mark">{{ $done[$key] ? $q['edit'] : $q['write'] }}</a>
          </div>
        @endforeach
      </dl>
    </section>
  @else
    @php($n = array_search($current, $keys, true) + 1)
    @php($answered = collect($keys)->filter(fn (string $key): bool => $key !== $current && $done[$key]))
    <form method="POST" action="{{ route('review.update', $week->param()) }}">
      @csrf
      @method('PUT')
      {{-- いま答えない問いを1つずつ取り出し、保存済みの値を hidden で送り直す（上書きで消えないように） --}}
      @foreach ($fields as $key => $field)
        {{-- いまの問いの欄はカードの中に出すので、hidden にしない --}}
        @if ($key !== $current)
          <input type="hidden" name="{{ $field }}" value="{{ old($field, $values[$key]) }}">
        @endif
      @endforeach
      <x-ui.step-card :step="$n" :total="count($keys)" icon="sparkle" :title="$thisWeek($q[$current]['label'])" :hint="$q[$current]['hint']">
        {{-- 1問目は事件を選ぶ欄（選べる事件がなければ一言だけ）、2・3問目は書く欄を出す --}}
        @if ($current === 'changed_case' && $caseOptions)
          <x-ui.select name="changed_case_file_id" :label="$q['changed_case']['short']" :options="$caseOptions" :selected="$values['changed_case']" placeholder="選ばない" />
        @elseif ($current === 'changed_case')
          <input type="hidden" name="changed_case_file_id" value="">
          <p class="text-base text-muted">{{ $thisWeek($q['changed_case']['none']) }}</p>
        @else
          <x-ui.textarea :name="$current" :label="$q[$current]['short']" :value="$values[$current]" rows="3" :maxlength="config('suiri.limits.reflection_text')" />
        @endif
        {{-- 主なボタンを書く欄のすぐ下に置き、個人情報の注意書きはその下に小さく出す（書く欄がある問いだけ） --}}
        <x-slot:action>
          <x-ui.big-button :icon="$n === count($keys) ? 'check' : null">{{ $n === count($keys) ? $q['finish'] : $q['next'] }}</x-ui.big-button>
          {{-- 書く欄のある問い（2・3問目）だけ、個人情報の注意書きを出す --}}
          @if ($current !== 'changed_case')
            <x-ui.privacy-note class="mt-3" />
          @endif
        </x-slot:action>
        <x-slot:later>
          <a href="{{ $link($keys[$n] ?? 'done') }}">{{ $q['skip'] }}</a>
        </x-slot:later>
      </x-ui.step-card>
    </form>
    {{-- ほかに書いた問いがあれば、カードの下に小さくたたんで出す（開くと答えが読める。いまの問いのカードとボタンを最初の画面に入れるため下に置く） --}}
    @if ($answered->isNotEmpty())
      <x-ui.done-summary class="mt-4" :title="strtr($q['answered'], [':n' => $answered->count()])" :edit-href="$link('done')" :edit-label="$q['edit']"
        :items="$answered->map(fn (string $key): string => $q[$key]['short'].'　'.$answer($key))->values()->all()" />
    @endif
  @endif
</div>
