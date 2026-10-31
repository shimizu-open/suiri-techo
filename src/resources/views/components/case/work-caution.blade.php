{{--
  仕事の場面で使うときの注意（お客さまの記録には使えない）。調査 R5 の 9-2 の文案（config/suiri.php の cases.work_caution・notes.privacy_work）。
  使い方：<x-case.work-caution :first="true" toggle />
  first：初めて仕事の場面の事件を作るとき。目立つ枠で全文を出す。それ以外は入力欄の上に置く短い注意にする。
  toggle：同じフォームで場面の「仕事」を選んだときだけ見せる（フォームに class="group/new" が要る。CSS の :has で切り替える）
--}}
@props(['first' => false, 'toggle' => false])
<div role="note" {{ $attributes->class([
  'rounded-lg border p-3',
  'hidden group-has-[input[name=scene][value=work]:checked]/new:block' => $toggle,
  'border-accent bg-accent-soft' => $first,
  'border-rule bg-paper' => ! $first,
]) }}>
  {{-- 初めてのときは見出しと全文、2回目からは短い注意を出す --}}
  @if ($first)
    <p class="font-mincho font-bold text-accent">{{ config('suiri.cases.work_caution.title') }}</p>
    <p class="mt-1 text-sm leading-relaxed">{{ config('suiri.cases.work_caution.body') }}</p>
  @else
    <p class="text-xs leading-relaxed text-muted">{{ config('suiri.notes.privacy_work') }}</p>
  @endif
</div>
