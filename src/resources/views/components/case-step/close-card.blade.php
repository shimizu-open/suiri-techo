{{--
  いまやることのカード「事件を閉じる？」（送り先は cases.close・入力は status（solved・cold）と conclusion）。
  「解決！」「おあずけ」の大きな2枚の札から選び、わかったことを一言（なくてもOK）。「つまり？」「たとえば？」は、閉じたあとに1問ずつ聞く。
  suggested：いまの一手が「閉じる」（当たりの見立てがある）とき。そうでないとき（「ほかにできること」から来た）は、下に戻るリンクを出す。
  使い方：<x-case-step.close-card :case :kid :suggested="true" :note />
--}}
@props(['case', 'kid' => false, 'suggested' => false, 'note' => null])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
@php($picked = old('status', 'solved'))
{{-- 閉じ方ごとの札の色（選んだときの塗り）。解決は答え合わせの赤、おあずけは紺 --}}
@php($looks = ['solved' => 'peer-checked:border-answer peer-checked:bg-answer', 'cold' => 'peer-checked:border-ink peer-checked:bg-ink'])
<x-ui.step-card icon="check" tone="answer" :title="$t('close.title')" :hint="$t($suggested ? 'close.hint_suggested' : 'close.hint')">
  <form id="close-form" method="POST" action="{{ route('cases.close', $case) }}" class="space-y-5">
    @csrf
    <input type="hidden" name="form_key" value="close">
    <fieldset>
      <legend class="mb-2 block text-base font-semibold">{{ $t('close.status') }}</legend>
      <div class="grid grid-cols-2 gap-3">
        {{-- 解決・おあずけを1枚ずつ、大きな札にする --}}
        @foreach (['solved', 'cold'] as $status)
          <label class="cursor-pointer">
            <input type="radio" name="status" value="{{ $status }}" class="peer sr-only" @checked($picked === $status)>
            <span class="flex min-h-20 flex-col items-center justify-center rounded-xl border-2 border-rule bg-sheet px-2 text-center peer-checked:text-on-ink peer-focus-visible:outline-2 peer-focus-visible:outline-accent {{ $looks[$status] }}">
              <span class="text-xl font-bold">{{ $t('close.'.$status) }}</span>
              <span class="text-sm">{{ $t('close.'.$status.'_note') }}</span>
            </span>
          </label>
        @endforeach
      </div>
      @error('status')
        <p class="mt-1 text-sm text-accent">{{ $message }}</p>
      @enderror
    </fieldset>
    <x-ui.textarea name="conclusion" id="f-step-conclusion" :label="$t('close.conclusion')" rows="2" :maxlength="config('suiri.cases.conclusion_max')"
      :placeholder="$t('close.placeholder')" />
    <x-ui.big-button tone="answer" icon="check">{{ $t('close.button') }}</x-ui.big-button>
    <x-ui.privacy-note :scene="$case->scene" />
  </form>
  <x-case-step.bubble :note="$note" :case="$case" :explain="$t('close.explain')" />
  {{-- いまの一手ではないとき（「ほかにできること」から来た）は、やめて戻るリンクを下に出す --}}
  @unless ($suggested)
    <x-slot:later><a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $t('back') }}</a></x-slot:later>
  @endunless
</x-ui.step-card>
