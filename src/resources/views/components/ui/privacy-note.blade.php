{{--
  入力欄の下にいつも小さく出す、個人情報の注意書き（文は config('suiri.notes')）。
  使い方：<x-ui.privacy-note />。仕事の場面では <x-ui.privacy-note :scene="$case->scene" /> と渡すと、施術者向けの一文も足す
--}}
@props(['scene' => null])
@php($work = ($scene instanceof \App\Enums\Scene ? $scene->value : $scene) === 'work')
<div {{ $attributes->class(['space-y-1 text-xs leading-relaxed text-muted']) }}>
  {{-- 仕事の場面なら、お客さまの記録には使えないことを先に出す --}}
  @if ($work)
    <p>{{ config('suiri.notes.privacy_work') }}</p>
  @endif
  <p>{{ config('suiri.notes.privacy') }}</p>
</div>
