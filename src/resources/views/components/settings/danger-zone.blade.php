{{--
  「データのダウンロードと退会」の中身（A-5・A-6）。設定の画面のいちばん下で、たたんだ中に置く（<x-settings.account>）。
  ダウンロード：自分のデータをすべて JSON で（カレンダー連携の鍵は入らない）。退会：確かめの言葉を入れると、すべてを消してトップへ戻る。
  使い方：<x-settings.danger-zone :user="$user" />
--}}
@props(['user'])
@php($word = config('suiri.review.delete_confirmation'))
@php($d = config('suiri.settings.danger'))
<div {{ $attributes->class(['space-y-5 pt-2']) }}>
  <div class="space-y-2">
    <p class="text-sm">{{ $d['export'] }}</p>
    <x-ui.button variant="secondary" :href="route('settings.export')">{{ $d['export_button'] }}</x-ui.button>
  </div>
  <div class="space-y-2 border-t border-rule pt-4">
    <h3 class="text-base">{{ $d['delete_title'] }}</h3>
    <p class="text-sm">{{ strtr($d['delete'], [':email' => $user->email]) }}</p>
    <x-ui.confirm :action="route('account.destroy')" :label="$d['delete_button']" :confirm-label="$word"
      :message="strtr($d['delete_confirm'], [':word' => $word])" :open="$errors->has('confirmation')">
      <div class="mb-3">
        <x-ui.field name="confirmation" :label="strtr($d['delete_field'], [':word' => $word])" autocomplete="off" required />
      </div>
    </x-ui.confirm>
  </div>
</div>
