{{--
  事件を作る画面の3問目「どうしてだと思う？」：最初の見立て（hypothesis_body）。書かずに進んでもよい。
  使い方：<x-case-new.hypothesis :draft="$draft" :kid="$kid" />（draft は Queries/Cases/NewCaseDraft の戻り値）。
  上に、2問目で書いた見たことを小さく写す（stepper.js。書いていなければ出さない）。親子の人には「かも」の言い方、仕事の場面は仕事の言い方。
  入力欄の上に考えるヒント（別の説明の型。提案 S-2）を置く。相棒モードを入れると、入力欄の代わりに「事件を作ったあとに書く」説明を出す
--}}
@props(['draft', 'kid' => false])
@php($w = config('suiri.case_new.hypothesis'))
@php($word = $kid ? \App\Support\Label::kidWord('hypothesis') : \App\Support\Label::word('hypothesis'))
<x-case-new.step :n="3">
  <x-ui.step-card :step="3" :total="4" icon="question" tone="hypothesis" :title="$w['title']" :hint="$draft['practice']['prompts']['hypothesis'] ?? $w['example']">
    <p data-echo-filled="clue_body" hidden class="truncate rounded-lg bg-clue-soft px-3 py-2 text-sm">
      <span class="font-bold text-clue">{{ $w['recall'] }}</span><span data-echo="clue_body"></span>
    </p>

    <div class="space-y-4 group-has-[input[data-partner]:checked]/new:hidden">
      <x-ui.hint tone="hypothesis" icon="question">{{ __($w['meaning'], ['word' => $word]) }}</x-ui.hint>
      <x-case-new.field name="hypothesis_body" :rows="3" :maxlength="config('suiri.limits.hypothesis_body')" :placeholder="$w['placeholder']">
        <span class="group-has-[input[name=scene][value=work]:checked]/new:hidden">{{ $word }}</span>
        <span class="hidden group-has-[input[name=scene][value=work]:checked]/new:inline">{{ \App\Support\Label::word('hypothesis', \App\Enums\Scene::Work) }}</span>
        <x-slot:above>
          <x-case.suggest-chips folded :items="config('suiri.suggest.hypothesis_types.'.($kid ? 'kid' : 'default'))" target="f-new-hypothesis_body" :caption="$w['starters']" class="group-has-[input[name=scene][value=work]:checked]/new:hidden" />
          <x-case.suggest-chips folded :items="config('suiri.suggest.hypothesis_types.work')" target="f-new-hypothesis_body" :caption="$w['starters']" class="hidden group-has-[input[name=scene][value=work]:checked]/new:block" />
        </x-slot:above>
      </x-case-new.field>
    </div>
    {{-- 相棒の名前がある人には、相棒モードを入れたときの説明を用意しておく（入れたときだけ見える） --}}
    @if ($draft['partner'])
      <div class="hidden group-has-[input[data-partner]:checked]/new:block">
        <x-ui.hint tone="hypothesis" icon="partner">{{ __($w['partner'], ['partner' => $draft['partner']]) }}</x-ui.hint>
      </div>
    @endif

    <x-slot:action><x-case-new.next tone="hypothesis" /></x-slot:action>
    <x-slot:later><x-case-new.moves skip /></x-slot:later>
  </x-ui.step-card>
</x-case-new.step>
