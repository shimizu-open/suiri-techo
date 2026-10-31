{{--
  屋根裏の棚に、自分で決まりを1つ書くカード（いまやることのカード <x-ui.step-card>）。?add=1 か、書いて送ったのにエラーで戻ったときに出す。
  使い方：<x-review.attic-add :case-options="$caseOptions" />。caseOptions：「事件の id => なぞ」
--}}
@props(['caseOptions' => []])
@php($a = config('suiri.review.attic'))
<form method="POST" action="{{ route('attic.store') }}" {{ $attributes }}>
  @csrf
  <x-ui.step-card icon="attic" :title="$a['add_title']" :hint="$a['add_hint']">
    <x-review.attic-fields :case-options="$caseOptions" />
    {{-- 主なボタンを入力欄のすぐ下に置き、個人情報の注意書きはその下に小さく出す --}}
    <x-slot:action>
      <x-ui.big-button icon="attic">{{ $a['save'] }}</x-ui.big-button>
      <x-ui.privacy-note class="mt-3" />
    </x-slot:action>
    <x-slot:later><a href="{{ route('attic.index') }}">{{ $a['cancel'] }}</a></x-slot:later>
  </x-ui.step-card>
</form>
