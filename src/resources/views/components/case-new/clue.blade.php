{{--
  事件を作る画面の2問目「見たこと・聞いたことを1つ教えて」：最初の手がかりの種類（clue_kind）と本文（clue_body）。書かずに進んでもよい。
  使い方：<x-case-new.clue :draft="$draft" :kid="$kid" />（draft は Queries/Cases/NewCaseDraft の戻り値）。
  種類は絵つきの大きな札（見た・聞いた・なかった）。親子の人には短い名前の下に子ども向けの言い方を添え、場面で「仕事」を選ぶと仕事の言い方に切り替わる。
  練習メニューから来たら、メニューの問いかけ（prompts.clue）を例の行に出す。入力欄の上に書き出しのヒント（提案 S-1）を置く
--}}
@props(['draft', 'kid' => false])
@php($w = config('suiri.case_new.clue'))
@php($selected = (string) old('clue_kind', \App\Enums\ClueKind::Seen->value))
<x-case-new.step :n="2">
  <x-ui.step-card :step="2" :total="4" icon="eye" tone="clue" :title="$w['title']" :hint="$draft['practice']['prompts']['clue'] ?? $w['example']">
    {{-- 言葉の説明の吹き出し。場面で「仕事」を選んだときは、同じ場所に施術者向けの注意を出す（入力欄の上に注意を置く決まり。カードの高さも変えない） --}}
    <div class="group-has-[input[name=scene][value=work]:checked]/new:hidden">
      <x-ui.hint tone="clue" icon="eye">{{ $w['meaning'] }}</x-ui.hint>
    </div>
    <x-case-new.work-note />

    <fieldset>
      <legend class="mb-2 text-base font-semibold">{{ $w['legend'] }}</legend>
      <div class="grid grid-cols-3 gap-2">
        {{-- 種類を1つずつ取り出し、絵つきの札にする。仕事の場面だけ言い方が変わる種類は、仕事の言い方も持たせる --}}
        @foreach (\App\Enums\ClueKind::cases() as $kind)
          <x-case-new.pick name="clue_kind" tone="clue" :value="$kind->value" :checked="$selected === $kind->value" :pic="$kind->value"
            :label="$kind->label()" :desc="$kid ? $kind->kidLabel() : $w['desc'][$kind->value]"
            :work-label="$kind->label(\App\Enums\Scene::Work) !== $kind->label() ? $kind->label(\App\Enums\Scene::Work) : null" />
        @endforeach
      </div>
      @error('clue_kind')
        <p data-error class="mt-1 text-sm text-accent">{{ $message }}</p>
      @enderror
    </fieldset>

    <x-case-new.field name="clue_body" :rows="2" quiet :maxlength="config('suiri.limits.clue_body')" :placeholder="$w['placeholder']">
      <span class="group-has-[input[name=scene][value=work]:checked]/new:hidden">{{ $w['label'] }}</span>
      <span class="hidden group-has-[input[name=scene][value=work]:checked]/new:inline">{{ $w['label_work'] }}</span>
      <x-slot:above>
        <x-case.suggest-chips folded :items="config('suiri.suggest.clue_questions.default')" target="f-new-clue_body" :caption="$w['starters']" class="group-has-[input[name=scene][value=work]:checked]/new:hidden" />
        <x-case.suggest-chips folded :items="config('suiri.suggest.clue_questions.work')" target="f-new-clue_body" :caption="$w['starters']" class="hidden group-has-[input[name=scene][value=work]:checked]/new:block" />
      </x-slot:above>
    </x-case-new.field>

    <x-slot:action><x-case-new.next tone="clue" /></x-slot:action>
    <x-slot:later><x-case-new.moves skip /></x-slot:later>
  </x-ui.step-card>
</x-case-new.step>
