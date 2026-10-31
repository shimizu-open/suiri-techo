{{--
  設定の画面のいちばん下：ログインしているアカウントとログアウト、その下にたたんだ「データのダウンロードと退会」（<x-settings.danger-zone>）。
  危ない操作はふだん見えないようにたたみ、退会の確かめでエラーになって戻ったときだけ開いておく。
  使い方：<x-settings.account :user="$user" class="mt-8" />
--}}
@props(['user'])
@php($s = config('suiri.settings'))
<section aria-label="アカウント" {{ $attributes->class(['border-t border-rule pt-4']) }}>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <p class="min-w-0 text-sm text-muted">{{ strtr($s['login'], [':email' => $user->email]) }}</p>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <x-ui.button variant="secondary"><x-ui.icon name="logout" class="size-5" />{{ $s['logout'] }}</x-ui.button>
    </form>
  </div>
  <details class="group mt-4 border-t border-rule" @if ($errors->has('confirmation')) open @endif>
    <summary class="flex min-h-12 cursor-pointer items-center justify-between gap-2 text-sm font-bold text-muted hover:text-ink">
      {{ $s['danger']['summary'] }}
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="size-5 group-open:rotate-180"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <x-settings.danger-zone :user="$user" class="pb-2" />
  </details>
</section>
