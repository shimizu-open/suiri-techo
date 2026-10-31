{{--
  絵つきの大きな選ぶボタン（ラジオボタン1つ）。事件を作る画面の場面（日常・親子・仕事）と、手がかりの種類（見た・聞いた・なかった）に使う。
  使い方：<x-case-new.pick name="scene" value="work" :checked="true" pic="work" label="仕事" desc="施術のなかで" />
  pic：<x-case-new.pic> の名前。desc：一言の説明（省略可）。tone：plain（紺・既定）・clue（手がかりの紺）。
  work-label：同じフォームで場面の「仕事」を選んだときに入れ替える言い方（フォームに class="group/new" が要る。CSS の :has で切り替える）。
  入力には data-label（・data-label-work）を付け、確かめの段の札に選んだ言葉を写せるようにする（resources/js/parts/stepper.js）
--}}
@props(['name', 'value', 'checked' => false, 'pic', 'label', 'desc' => null, 'tone' => 'plain', 'workLabel' => null])
{{-- 段の種類ごとの、選んだときの色の組み合わせ（face：札の枠と面／pic：絵の色／badge：右上のチェックの丸） --}}
@php($tones = [
  'plain' => ['face' => 'group-has-[:checked]/pick:border-ink group-has-[:checked]/pick:bg-mark', 'pic' => 'group-has-[:checked]/pick:text-ink', 'badge' => 'bg-ink text-on-ink'],
  'clue' => ['face' => 'group-has-[:checked]/pick:border-clue group-has-[:checked]/pick:bg-clue-soft', 'pic' => 'group-has-[:checked]/pick:text-clue', 'badge' => 'bg-clue text-on-ink'],
])
@php($c = $tones[$tone] ?? $tones['plain'])
<label class="group/pick relative block cursor-pointer">
  <input type="radio" name="{{ $name }}" value="{{ $value }}" class="sr-only" @checked($checked) data-label="{{ $label }}" @if ($workLabel) data-label-work="{{ $workLabel }}" @endif>
  <span class="flex h-full min-h-24 flex-col items-center gap-1 rounded-xl border-2 border-rule bg-sheet px-1 pt-3 pb-2.5 text-center group-has-[:focus-visible]/pick:outline-2 group-has-[:focus-visible]/pick:outline-offset-2 group-has-[:focus-visible]/pick:outline-accent {{ $c['face'] }}">
    <x-case-new.pic :name="$pic" class="size-8 text-muted {{ $c['pic'] }}" />
    <span class="text-base leading-tight font-bold">
      {{-- 仕事の言い方もあれば両方を持っておき、場面の選び方で見せる方を切り替える --}}
      @if ($workLabel)
        <span class="group-has-[input[name=scene][value=work]:checked]/new:hidden">{{ $label }}</span>
        <span class="hidden group-has-[input[name=scene][value=work]:checked]/new:inline">{{ $workLabel }}</span>
      @else
        {{ $label }}
      @endif
    </span>
    {{-- 一言の説明があれば、名前の下に小さく出す（仕事の言い方に切り替えたときは、名前だけでわかるので隠す） --}}
    @if ($desc)
      <span @class(['text-xs leading-snug text-muted', 'group-has-[input[name=scene][value=work]:checked]/new:hidden' => $workLabel])>{{ $desc }}</span>
    @endif
  </span>
  <span aria-hidden="true" class="absolute top-1.5 right-1.5 hidden size-5 place-items-center rounded-full group-has-[:checked]/pick:grid {{ $c['badge'] }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3.5"><path d="m6 12.5 4 4 8-8.5"/></svg>
  </span>
</label>
