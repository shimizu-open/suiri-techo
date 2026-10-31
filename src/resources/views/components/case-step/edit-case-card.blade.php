{{--
  いまやることのカード「なぞを直す」（「ほかにできること」から来たとき。送り先は cases.update・入力は title と scene）。
  下に「事件を消す」（確かめてから消す。手がかり・見立て・予想・確かめるやることも消える）と、戻るリンク。
  使い方：<x-case-step.edit-case-card :case="$case" :kid="$kid" />（場面の言葉は、ふだんの場面が親子の人なら子ども向けの言い方）
--}}
@props(['case', 'kid' => false])
@php($w = config('suiri.case_board.cards'))
@php($scenes = collect(\App\Enums\Scene::cases())->mapWithKeys(fn ($s) => [$s->value => $kid ? $s->kidLabel() : $s->label()])->all())
<x-ui.step-card :title="$w['edit_case']['title']">
  <form method="POST" action="{{ route('cases.update', $case) }}" class="space-y-4">
    @csrf
    @method('PATCH')
    <input type="hidden" name="form_key" value="edit-case">
    <x-ui.field name="title" id="f-step-title" :label="$w['edit_case']['label']" :value="$case->title" required :maxlength="config('suiri.limits.case_title')" />
    <x-ui.choices name="scene" :label="$w['edit_case']['scene']" :options="$scenes" :selected="$case->scene" />
    <x-ui.big-button icon="check">{{ $w['edit_case']['button'] }}</x-ui.big-button>
    <x-ui.privacy-note :scene="$case->scene" />
  </form>
  <x-ui.confirm :action="route('cases.destroy', $case)" :label="$w['edit_case']['delete']" :message="$w['edit_case']['delete_message']"
    class="border-t border-rule pt-3" />
  <x-slot:later><a href="{{ route('cases.show', $case) }}" class="inline-flex min-h-12 items-center font-semibold">{{ $w['back'] }}</a></x-slot:later>
</x-ui.step-card>
