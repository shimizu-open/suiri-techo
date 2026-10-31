{{--
  進み具合の丸の列。済んだ段は塗り、いまの段は大きな枠、まだの段はうすく出し、上に「ステップ 2 / 5」を出す。
  使い方：<x-ui.progress :steps="[['label' => 'なぞ', 'done' => true], ['label' => '見たこと', 'current' => true], ['label' => 'どうして？']]" tone="clue" />
  steps：段の一覧（label・done・current。tone を入れると、その段がいまのときの色になる）。
  tone：いまの段の色（clue・hypothesis・prediction・check・answer・plain。既定 plain）。:count="false" で「ステップ 2 / 5」を出さない
--}}
@props(['steps' => [], 'tone' => 'plain', 'count' => true])
@php($total = count($steps))
@php($now = collect($steps)->search(fn ($s) => ! empty($s['current'])))
{{-- いまの段の色の組み合わせ（ring：大きな枠／dot：枠の中の点／text：段の名前）。check は予想と同じ緑 --}}
@php($tones = [
  'clue' => ['ring' => 'border-clue', 'dot' => 'bg-clue', 'text' => 'text-clue'],
  'hypothesis' => ['ring' => 'border-hypothesis', 'dot' => 'bg-hypothesis', 'text' => 'text-hypothesis'],
  'prediction' => ['ring' => 'border-prediction', 'dot' => 'bg-prediction', 'text' => 'text-prediction'],
  'check' => ['ring' => 'border-prediction', 'dot' => 'bg-prediction', 'text' => 'text-prediction'],
  'answer' => ['ring' => 'border-answer', 'dot' => 'bg-answer', 'text' => 'text-answer'],
  'plain' => ['ring' => 'border-ink', 'dot' => 'bg-ink', 'text' => 'text-ink'],
])
<div {{ $attributes->class(['w-full']) }}>
  {{-- いまの段があり、数を出すように頼まれていれば、「ステップ 2 / 5」を上に出す --}}
  @if ($count && $now !== false)
    <p class="mb-1 text-sm font-bold text-muted">{{ __(config('suiri.ui.step'), ['n' => $now + 1, 'total' => $total]) }}</p>
  @endif
  <ol aria-label="{{ config('suiri.ui.progress_label') }}" class="flex items-start">
    {{-- 段を1つずつ取り出し、丸と名前を並べる。丸と丸の間は線でつなぐ（済んだ所は濃い線） --}}
    @foreach ($steps as $i => $s)
      @php($state = ! empty($s['current']) ? 'current' : (! empty($s['done']) ? 'done' : 'todo'))
      @php($c = $tones[$s['tone'] ?? $tone] ?? $tones['plain'])
      <li @if ($state === 'current') aria-current="step" @endif class="relative flex min-w-0 flex-1 flex-col items-center text-center">
        {{-- 最後の段でなければ、次の丸へつなぐ線を引く --}}
        @if (! $loop->last)
          <span aria-hidden="true" @class(['absolute top-4 left-1/2 h-0.5 w-full', 'bg-ink' => $state === 'done', 'bg-rule' => $state !== 'done'])></span>
        @endif
        <span class="relative grid h-8 place-items-center">
          {{-- 段の様子で丸の形を変える：済み＝塗った丸にチェック／いま＝大きな枠／まだ＝うすい小さな丸 --}}
          @switch($state)
            @case('done')
              <span class="grid size-6 place-items-center rounded-full bg-ink text-on-ink">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-4"><path d="m6 12.5 4 4 8-8.5"/></svg>
              </span>
              @break
            @case('current')
              <span class="grid size-8 place-items-center rounded-full border-[3px] bg-sheet {{ $c['ring'] }}">
                <span class="size-3 rounded-full {{ $c['dot'] }}"></span>
              </span>
              @break
            @default
              <span class="size-4 rounded-full border-2 border-rule bg-paper"></span>
          @endswitch
        </span>
        {{-- 名前は折り返さない（「もしそうなら」が2行に割れないように）。段が6つ以上でスマホの幅が足りないときは、いまの段の名前だけを見せ、ほかは読み上げだけにする --}}
        <span @class([
          'mt-1 text-[0.75rem] leading-tight tracking-tight whitespace-nowrap sm:text-xs',
          'font-bold '.$c['text'] => $state === 'current',
          'font-semibold text-ink' => $state === 'done',
          'text-muted' => $state === 'todo',
          'max-sm:sr-only' => $state !== 'current' && $total > 5,
        ])>
          {{ $s['label'] }}
          {{-- 読み上げのために、済み・いまここを言葉でも添える（画面には出さない） --}}
          @if ($state !== 'todo')
            <span class="sr-only">（{{ $state === 'done' ? config('suiri.ui.done') : config('suiri.ui.now') }}）</span>
          @endif
        </span>
      </li>
    @endforeach
  </ol>
</div>
