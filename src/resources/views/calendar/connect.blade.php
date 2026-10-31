{{--
  カレンダーとつなぐ前の説明（GET /calendar/connect）。上から：一言（つなぐと、確かめる日をカレンダーが知らせてくれる）→
  どうなるか・送る項目（題名・日時・戻るリンク・見分けるための番号）・送らない項目・読む項目・Google は米国の会社・いつでもやめられる →
  「同意して Google へ進む」で POST /calendar/connect（Google の画面へ）に送る。送る・読む項目の文は config/suiri.php の calendar.consent から引く（中身は変えない）。
  説明の文が長いので、枠のカードにせず小さめの文字で詰め、主なボタンがスマホの最初の画面に入るようにする。
  Google の鍵が未設定（ready が false）なら「準備中」と出して、進むボタンを出さない
--}}
@php($c = config('suiri.settings.calendar'))
<x-layouts.app title="カレンダーとつなぐ">
  <div class="flex items-start gap-3">
    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-prediction-soft text-prediction"><x-ui.step-icon name="calendar" class="size-6" /></span>
    <h2 class="pt-1 text-lg leading-snug">{{ $c['lead'] }}</h2>
  </div>

  <dl class="mt-3 divide-y divide-rule border-y border-rule text-xs leading-relaxed">
    <x-settings.consent-row label="どうなる？">{{ config('suiri.calendar.consent.what') }}</x-settings.consent-row>
    <x-settings.consent-row label="送るもの">{{ config('suiri.calendar.consent.send') }}</x-settings.consent-row>
    <x-settings.consent-row label="送らないもの">{{ config('suiri.calendar.consent.not_send') }}</x-settings.consent-row>
    <x-settings.consent-row label="読むもの">{{ config('suiri.calendar.consent.receive') }}</x-settings.consent-row>
    <x-settings.consent-row label="Google">{{ config('suiri.calendar.consent.company') }}</x-settings.consent-row>
    <x-settings.consent-row label="やめるとき">{{ config('suiri.calendar.consent.stop') }}</x-settings.consent-row>
  </dl>

  {{-- Google の鍵が設定されていれば、同意して進むボタンを出す。なければ「準備中」とだけ出す --}}
  @if ($ready)
    <form method="POST" action="{{ route('calendar.redirect') }}" class="mt-4">
      @csrf
      {{-- すでに連携の行がある（切れた・つながっている）人には「つなぎ直す」と出す --}}
      <x-ui.big-button icon="calendar" tone="check">{{ $connection ? '同意して、つなぎ直す' : '同意して Google へ進む' }}</x-ui.big-button>
    </form>
    <p class="mt-2 text-xs leading-relaxed text-muted">{{ config('suiri.calendar.consent.warning') }}</p>
  @else
    <p class="mt-4 rounded-lg border border-rule bg-mark px-3 py-2 text-sm font-bold">{{ $c['not_ready'] }}</p>
  @endif

  <div class="mt-3">
    <x-ui.button :href="route('settings.show')" variant="ghost" size="sm">{{ $c['back'] }}</x-ui.button>
  </div>
</x-layouts.app>
