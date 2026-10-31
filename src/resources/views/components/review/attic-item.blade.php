{{--
  屋根裏の棚の1枚：つまり？（決まり）を大きく・たとえば？（例）・見つけた事件・この決まりを使った予想の数と、「直す」「消す」。
  editing のときは、その場で直す欄（<x-review.attic-form>）に変わる。
  使い方：<x-review.attic-item :note="$note" :case-options="$caseOptions" :editing="$editing === $note->id" />
  note：AtticNote（caseFile と predictions_count を読み込み済み）。caseOptions：「事件の id => なぞ」
--}}
@props(['note', 'caseOptions' => [], 'editing' => false])
@php($a = config('suiri.review.attic'))
<li id="note-{{ $note->id }}" {{ $attributes->class(['scroll-mt-20 py-4']) }}>
  {{-- 直している1枚なら入力欄、そうでなければ中身と操作を出す --}}
  @if ($editing)
    <x-review.attic-form :action="route('attic.update', $note)" :note="$note"
      :case-options="$caseOptions + ($note->caseFile ? [$note->caseFile->id => $note->caseFile->title] : [])">
      <x-ui.button variant="ghost" :href="route('attic.index').'#note-'.$note->id">{{ $a['cancel'] }}</x-ui.button>
    </x-review.attic-form>
  @else
    <p class="text-sm font-bold text-muted">{{ \App\Support\Label::word('rule') }}</p>
    <p class="text-lg leading-snug font-bold">{{ $note->rule }}</p>
    {{-- たとえば？があれば、決まりの下に出す --}}
    @if (filled($note->examples))
      <p class="mt-2 text-sm font-bold text-muted">{{ \App\Support\Label::word('examples') }}</p>
      <p class="text-base">{{ $note->examples }}</p>
    @endif
    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted">
      {{-- 見つけた事件があれば、事件へのリンクを出す --}}
      @if ($note->caseFile)
        <span>{{ $a['from'] }}：<x-review.case-link :case="$note->caseFile" /></span>
      @endif
      <span>{{ strtr($a['used'], [':n' => $note->predictions_count]) }}</span>
      <span>{{ $note->created_at->format('Y年n月j日') }}</span>
    </div>
    <div class="mt-1 flex flex-wrap items-start justify-end gap-1 text-sm">
      <a href="{{ route('attic.index', ['edit' => $note->id, 'page' => request('page')]) }}#note-{{ $note->id }}" class="inline-flex min-h-11 items-center rounded-lg px-3 font-bold text-ink no-underline hover:bg-mark">直す</a>
      <x-ui.confirm :action="route('attic.destroy', $note)" :message="$a['delete']" />
    </div>
  @endif
</li>
