{{--
  説明を「子ども向け」と「大人向け」で切り替える、横いっぱいの大きな2つのボタン（技能・3つの考え方の画面の上）。
  JavaScript なしで動くよう、?kid=1・?kid=0 を付けたリンクにする（何も選んでいないときは、ふだんの場面が親子の人は子ども向け。User::prefersKidWords()）。
  言葉は config('suiri.guide.audience')。
  使い方：<x-guide.audience-toggle :kid="$kid" />
  kid：いま子ども向けを出しているか
--}}
@props(['kid' => false])
@php($words = config('suiri.guide.audience'))
<div {{ $attributes->class(['space-y-1.5']) }}>
  <p class="text-sm font-semibold text-muted">{{ $words['label'] }}</p>
  <div role="group" aria-label="{{ $words['label'] }}" class="grid grid-cols-2 gap-1 rounded-2xl border-2 border-rule bg-sheet p-1">
    {{-- 子ども向け・大人向けを1つずつ、いま選んでいる方を紺で塗ったリンクにする --}}
    @foreach ([1 => $words['kid'], 0 => $words['adult']] as $value => $label)
      <a href="{{ request()->fullUrlWithQuery(['kid' => $value]) }}" @if ($kid === (bool) $value) aria-current="true" @endif @class([
        'flex min-h-12 items-center justify-center rounded-xl text-base font-bold no-underline',
        'bg-ink text-on-ink' => $kid === (bool) $value,
        'text-muted hover:bg-mark hover:text-ink' => $kid !== (bool) $value,
      ])>{{ $label }}</a>
    @endforeach
  </div>
</div>
