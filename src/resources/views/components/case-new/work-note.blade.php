{{--
  事件を作る画面で、場面の「仕事」を選んだときだけ出す、やさしい一言の注意（お客さまの名前や病名を書かない）。
  使い方：<x-case-new.work-note :first="$draft['firstWork']" />
  first：仕事の場面の事件がまだ1つもない人。一言の下に、約束の全文（config の cases.work_caution.body）も出す。
  2回目からは、一言の下に短い注意（notes.privacy_work。お客さまの記録には使えない）を出す（.claude/rules/privacy.md：初回は全文、以降は入力欄の上）。
  フォームに class="group/new" が要る（CSS の :has で、場面の「仕事」を選んだときだけ見せる）
--}}
@props(['first' => false])
<div role="note" {{ $attributes->class(['hidden rounded-xl bg-accent-soft px-4 py-3 group-has-[input[name=scene][value=work]:checked]/new:block']) }}>
  <p class="text-base font-bold">{{ config('suiri.case_new.scene.work_line') }}</p>
  {{-- 初めて仕事の場面で作る人には約束の全文を、2回目からは短い注意（お客さまの記録には使えない）を一言の下に出す --}}
  @if ($first)
    <p class="mt-1 text-sm leading-relaxed">{{ config('suiri.cases.work_caution.body') }}</p>
  @else
    <p class="mt-1 text-sm leading-relaxed">{{ config('suiri.notes.privacy_work') }}</p>
  @endif
</div>
