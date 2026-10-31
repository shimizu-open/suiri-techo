{{--
  いまやることのカード「どうしてだと思う？」（見立てを書く。送り先は hypotheses.store・入力は body。相棒モードで見せ合う前は <x-case-partner.write-card> が書く）。
  variant：first（1つ目）・more（ほかの理由は？）・eliminated（全部ちがったとき）。欄の下に、ほかの説明の型（主なボタンが最初の画面に入るよう、
  いつも1行にたたむ。見立てが少ないうちは見出しで「押すと書き出しが入る」と知らせる）と
  屋根裏の棚の候補（押すと書き出しが入るだけ）。主なボタンの下に、言葉の説明かワトソンのひとことを1つ添える。
  使い方：<x-case-step.hypothesis-card :case :kid variant="first" :number :total :note :prompt :types :emphasize :attic />
--}}
@props([
    'case', 'kid' => false, 'variant' => 'first', 'number' => null, 'total' => null, 'note' => null, 'prompt' => null,
    'types' => [], 'emphasize' => false, 'attic' => [],
])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
@php($work = $case->scene === \App\Enums\Scene::Work)
@php($example = $t($work ? 'hypothesis.hint_work' : 'hypothesis.hint'))
<x-ui.step-card :step="$number" :total="$total" icon="question" tone="hypothesis"
  :title="$t(['first' => 'hypothesis.title', 'more' => 'hypothesis.title_more', 'eliminated' => 'hypothesis.title_eliminated'][$variant])"
  :hint="$prompt ?? ($variant === 'more' ? $t('hypothesis.hint_more').'。'.$example : $example)">
  <form method="POST" action="{{ route('hypotheses.store', $case) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="form_key" value="add-hypothesis">
    <div class="space-y-1">
      <x-ui.textarea id="f-step-hypothesis" name="body" rows="2" required :maxlength="config('suiri.limits.hypothesis_body')"
        :label="$work ? \App\Support\Label::word('hypothesis', $case->scene) : $t('hypothesis.label')" :placeholder="$t('hypothesis.placeholder')" />
      <x-case.suggest-chips :items="$types" target="f-step-hypothesis" folded
        :caption="config('suiri.suggest.captions.'.($emphasize ? 'hypothesis' : 'hypothesis_folded'))" />
      <x-case.suggest-chips :items="$attic" target="f-step-hypothesis" />
    </div>
    <x-ui.big-button tone="hypothesis" icon="check">{{ $t('hypothesis.button') }}</x-ui.big-button>
    <x-ui.privacy-note :scene="$case->scene" />
  </form>
  <x-case-step.bubble :note="$note" :case="$case" :explain="$t('hypothesis.explain')" />
  {{-- もう1つ書くとき（いまの一手ではないとき）だけ、カードの下に戻るリンクを出す --}}
  @if ($variant === 'more')
    <x-slot:later><a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $t('back') }}</a></x-slot:later>
  @endif
</x-ui.step-card>
