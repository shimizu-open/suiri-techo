{{--
  いまやることのカード「見たことを1つ教えて」（手がかりを書く。送り先は clues.store・入力は kind と body）。
  どうやって知った？（見た・聞いた・なかった）→ 見たこと → これでOK。書き出しの提案（いつ？・何回？…）は欄の下にたたんでおく。
  仕事の場面では、入力欄の上に施術者向けの注意を出す。主なボタンの下に、言葉の説明かワトソンのひとことを1つ添える。
  使い方：<x-case-step.clue-card :case :kid :more="false" :number="2" :total="5" :note="$watson" :prompt :questions="$suggest['clue']" />
  more：もう1つ書くとき（「ほかにできること」から来た）。prompt：練習メニューの問いかけ（あれば例の代わりに出す）
--}}
@props(['case', 'kid' => false, 'more' => false, 'number' => null, 'total' => null, 'note' => null, 'prompt' => null, 'questions' => []])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
@php($work = $case->scene === \App\Enums\Scene::Work)
@php($kinds = collect(\App\Enums\ClueKind::cases())->mapWithKeys(fn ($k) => [$k->value => $work ? $k->label($case->scene) : ($kid ? $k->kidLabel() : $k->label())])->all())
<x-ui.step-card :step="$number" :total="$total" icon="eye" tone="clue" :title="$t($more ? 'clue.title_more' : 'clue.title')"
  :hint="$prompt ?? $t($work ? 'clue.hint_work' : 'clue.hint')">
  <form method="POST" action="{{ route('clues.store', $case) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="form_key" value="add-clue">
    {{-- 仕事の場面なら、入力欄の上に施術者向けの注意を出す --}}
    @if ($work)
      <x-case.work-caution />
    @endif
    <x-ui.choices name="kind" :label="$t('clue.kind')" :options="$kinds" selected="seen" />
    <div class="space-y-1">
      <x-ui.textarea id="f-step-clue" name="body" :label="$t('clue.label')" rows="2" required :maxlength="config('suiri.limits.clue_body')"
        :placeholder="$t('clue.placeholder')" />
      <x-case.suggest-chips :items="$questions" target="f-step-clue" folded :caption="config('suiri.suggest.captions.clue')" />
    </div>
    <x-ui.big-button tone="clue" icon="check">{{ $t('clue.button') }}</x-ui.big-button>
    <x-ui.privacy-note />
  </form>
  <x-case-step.bubble :note="$note" :case="$case" :explain="$t('clue.explain')" />
  {{-- もう1つ書くとき（いまの一手ではないとき）は、やめて戻るリンクを下に出す --}}
  @if ($more)
    <x-slot:later><a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $t('back') }}</a></x-slot:later>
  @endif
</x-ui.step-card>
