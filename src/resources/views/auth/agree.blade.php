{{--
  同意の画面（初めてログインしたとき・A-3）。いまやることは「2つにチェックして、はじめる」なので、1枚のカード（<x-ui.step-card>）にまとめる：
  利用規約・プライバシーポリシーへの同意と、年齢の確認の2つのチェック（押しやすいよう、行全体を押せる大きな枠にする）。
  両方にチェックして「はじめる」を押すと agreed_at が入り、手帳を使えるようになる。文は config('suiri.agree')（約束の中身は規約と同じ）
--}}
@php($a = config('suiri.agree'))
<x-layouts.guest title="はじめる前に">
  <form method="POST" action="{{ route('agree.store') }}">
    @csrf
    <x-ui.step-card icon="check" :title="$a['title']" :hint="$a['lead']">
      <div class="space-y-3">
        <x-auth.agree-check name="accept_terms"><a href="{{ route('terms') }}" target="_blank" rel="noopener">利用規約</a>と<a href="{{ route('privacy') }}" target="_blank" rel="noopener">プライバシーポリシー</a>{{ $a['terms_tail'] }}</x-auth.agree-check>
        <x-auth.agree-check name="confirm_age">{{ $a['age'] }}</x-auth.agree-check>
      </div>
      <p class="text-xs leading-relaxed text-muted">{{ config('suiri.notes.privacy') }}</p>
      <x-slot:action><x-ui.big-button icon="check">{{ $a['button'] }}</x-ui.big-button></x-slot:action>
    </x-ui.step-card>
  </form>

  <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
    @csrf
    <x-ui.button variant="ghost" size="sm">{{ $a['logout'] }}</x-ui.button>
  </form>
</x-layouts.guest>
