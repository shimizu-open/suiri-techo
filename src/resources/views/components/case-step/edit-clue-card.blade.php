{{--
  いまやることのカード「見たことを直す」（荷札の「直す」から来たとき。送り先は clues.update・入力は kind と body）。
  下に「この見たことを消す」（確かめてから消す。付けた ○× も消える）と、戻るリンク。
  使い方：<x-case-step.edit-clue-card :case="$case" :clue="$clue" :kid="$kid" />
--}}
@props(['case', 'clue', 'kid' => false])
@php($t = fn (string $k, array $r = []) => __(($kid ? config('suiri.case_board.kid.cards.'.$k) : null) ?? config('suiri.case_board.cards.'.$k), $r))
@php($work = $case->scene === \App\Enums\Scene::Work)
@php($kinds = collect(\App\Enums\ClueKind::cases())->mapWithKeys(fn ($k) => [$k->value => $work ? $k->label($case->scene) : ($kid ? $k->kidLabel() : $k->label())])->all())
<x-ui.step-card icon="eye" tone="clue" :title="$t('edit_clue.title')">
  <form method="POST" action="{{ route('clues.update', $clue) }}" class="space-y-4">
    @csrf
    @method('PATCH')
    <input type="hidden" name="form_key" value="clue-{{ $clue->id }}">
    <x-ui.choices name="kind" :label="$t('clue.kind')" :options="$kinds" :selected="$clue->kind" />
    <x-ui.textarea id="f-step-clue-edit" name="body" :label="$t('clue.label')" :value="$clue->body" rows="3" required :maxlength="config('suiri.limits.clue_body')" />
    <x-ui.big-button tone="clue" icon="check">{{ $t('edit_clue.button') }}</x-ui.big-button>
    <x-ui.privacy-note :scene="$case->scene" />
  </form>
  <x-ui.confirm :action="route('clues.destroy', $clue)" :label="$t('edit_clue.delete')" :message="$t('edit_clue.delete_message')" class="border-t border-rule pt-3" />
  <x-slot:later><a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $t('back') }}</a></x-slot:later>
</x-ui.step-card>
