{{--
  事件の画面のいちばん上：なぞ（明朝体はアプリでここだけ）と、あるときだけ小さな1行（場面・相棒モード・きっかけ）。
  事件の番号は上の帯（画面の名前「事件 No.12」）に出すので、ここでは出さない。なぞを直す入口は「ほかにできること」に置く。
  使い方：<x-case-step.head :case="$case" :kid="$kid" />（kid：子ども向けの言葉を出すか＝ふだんの場面が親子の人）
--}}
@props(['case', 'kid' => false])
@php($w = config('suiri.case_board'))
@php($meta = array_filter([
  $case->scene !== \App\Enums\Scene::Daily ? ($kid ? $case->scene->kidLabel() : $case->scene->label()) : null,
  $case->partner_mode ? $w['meta_partner'] : null,
  $case->origin_kind && $case->origin_label ? __($w['meta_origin'], ['kind' => $case->origin_kind->label(), 'label' => \Illuminate\Support\Str::limit($case->origin_label, 40, '…')]) : null,
]))
<header {{ $attributes }}>
  <h2 class="font-mincho text-xl leading-snug font-bold break-words sm:text-2xl">{{ $case->title }}</h2>
  {{-- 場面（日常のときは出さない）・相棒モード・きっかけのどれかがあれば、なぞの下に小さく1行で出す --}}
  @if ($meta !== [])
    <p class="mt-1 text-sm text-muted">{{ implode('・', $meta) }}</p>
  @endif
</header>
