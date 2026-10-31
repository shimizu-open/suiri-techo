{{--
  事件の一覧の1件（<li>）：上に「事件 No.12」と場面、大きくなぞ、下に段ごとの進み具合の小さな印（なぞ → 見たこと → どうして？ → もしそうなら → 答え合わせ）と
  「次は『どうして？』」の一言。確かめる日が来た予想があれば、右上に赤い「答え合わせの日」の札を出す。押すと事件の画面へ。
  使い方：<x-case.summary :case="$case" :kid="$kid" />（<ul> の中に置く。数は CaseListQuery::withCounts() で数えたもの）
  進み具合は数から決める：clues_count・hypotheses_count・pending_predictions_count・due_predictions_count・
  resolved_predictions_count（答え合わせ済みの予想の数。数えていない問い合わせから来たときは 0 とみなす）。
  compact：今日の画面など、場面と日付を省いて短くするとき。kid：子ども向けの言葉を出すか（いまは段の名前は同じ）
--}}
@props(['case', 'compact' => false, 'kid' => false])
@php($w = config('suiri.case_list'))
@php($due = (int) ($case->due_predictions_count ?? 0))
@php($pending = (int) ($case->pending_predictions_count ?? 0))
@php($resolved = (int) ($case->resolved_predictions_count ?? 0))
{{-- 段ごとに「済んだか」を決める（なぞはいつも済み。予想は答え合わせ待ちか済みが1つでもあれば済み） --}}
@php($done = ['title' => true, 'clue' => (int) ($case->clues_count ?? 0) > 0, 'hypothesis' => (int) ($case->hypotheses_count ?? 0) > 0, 'prediction' => $pending + $resolved > 0, 'answer' => $resolved > 0])
{{-- いまの段：確かめる日が来た予想があれば答え合わせ（いちばん急ぐ）、なければまだ済んでいない最初の段 --}}
@php($now = $due > 0 ? 'answer' : collect($done)->search(false))
{{-- 段ごとの色（塗り・枠・文字）。段の順と同じ並び --}}
@php($tones = [
  'title' => ['fill' => 'bg-ink', 'ring' => 'border-ink', 'text' => 'text-ink'],
  'clue' => ['fill' => 'bg-clue', 'ring' => 'border-clue', 'text' => 'text-clue'],
  'hypothesis' => ['fill' => 'bg-hypothesis', 'ring' => 'border-hypothesis', 'text' => 'text-hypothesis'],
  'prediction' => ['fill' => 'bg-prediction', 'ring' => 'border-prediction', 'text' => 'text-prediction'],
  'answer' => ['fill' => 'bg-answer', 'ring' => 'border-answer', 'text' => 'text-answer'],
])
<li>
  <a href="{{ route('cases.show', $case) }}" @class(['block no-underline hover:bg-mark', 'px-4 py-4' => ! $compact, 'px-4 py-3' => $compact])>
    <div class="flex items-center gap-2">
      <p class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 text-sm text-muted">
        <span class="font-bold tracking-wide text-ink">{{ __($w['case_no'], ['no' => $case->id]) }}</span>
        {{-- 短くしないときは、場面を添える --}}
        @unless ($compact)
          <span>{{ $kid ? $case->scene->kidLabel() : $case->scene->label() }}</span>
        @endunless
        {{-- 相棒モードの事件なら、相棒の絵と一言を添える --}}
        @if ($case->partner_mode)
          <span class="inline-flex items-center gap-1"><x-ui.step-icon name="partner" class="size-4" />{{ $w['partner'] }}</span>
        @endif
      </p>
      {{-- 確かめる日が来た予想があれば、赤い札を出す --}}
      @if ($due > 0)
        <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-answer px-2.5 py-0.5 text-xs font-bold text-on-ink">
          <x-ui.step-icon name="check" class="size-4" />{{ $w['due'] }}<span class="sr-only">{{ $due }}</span>
        </span>
      @endif
    </div>
    <p @class(['mt-1 leading-snug font-bold break-words', 'text-lg' => ! $compact, 'text-base' => $compact])>{{ $case->title }}</p>
    <div class="mt-2 flex items-center gap-3">
      <ol aria-label="{{ config('suiri.ui.progress_label') }}" class="flex shrink-0 items-center gap-1.5">
        {{-- 段を1つずつ取り出し、済み＝塗った丸／いま＝段の色の枠／まだ＝うすい丸 で並べる --}}
        @foreach ($w['steps'] as $key => $label)
          @php($state = $done[$key] ? 'done' : ($key === $now ? 'current' : 'todo'))
          <li @if ($state === 'current') aria-current="step" @endif class="grid size-4 place-items-center">
            <span @class([
              'block rounded-full',
              'size-3 '.$tones[$key]['fill'] => $state === 'done',
              'size-4 border-[3px] bg-sheet '.$tones[$key]['ring'] => $state === 'current',
              'size-2.5 border-2 border-rule' => $state === 'todo',
            ])></span>
            <span class="sr-only">{{ $label }}{{ $state === 'todo' ? '' : '（'.config('suiri.ui.'.($state === 'done' ? 'done' : 'now')).'）' }}</span>
          </li>
        @endforeach
      </ol>
      <p class="min-w-0 truncate text-sm font-semibold">
        {{-- 閉じた事件なら閉じた日を、答え合わせ待ちなら待っていることを、まだ先の段があれば次の段を、全部済んだらそのことを出す --}}
        @if ($case->isClosed())
          <span class="text-muted">{{ __($w['closed_on'], ['date' => ($case->closed_at ?? $case->updated_at)?->isoFormat('M月D日')]) }}</span>
        @elseif ($now === 'answer' && $due === 0)
          <span class="text-muted">{{ $w['waiting'] }}</span>
        @elseif ($now !== false)
          <span class="{{ $tones[$now]['text'] }}">{{ __($w['next'], ['step' => $w['steps'][$now]]) }}</span>
        @else
          <span class="text-muted">{{ $w['all_done'] }}</span>
        @endif
      </p>
    </div>
  </a>
</li>
