{{--
  AI に記録を読ませてよいかを聞く同意の画面（GET /oauth/authorize。Passport の受付が出し、値は App\Support\Ai\ConsentView が詰め替える）。
  いまやることは「この AI に読ませてよいか決める」の1つなので、1枚のカードにまとめる：
  問い（AI の名前は自己申告）→ 許可したあとに戻る先（確かな手がかりなので太く）→ 読めるもの・読めないもの（1行ずつ）→ 記録が渡る会社と国 →
  なりすましの注意 → 「許可する」（POST）と「やめる」（DELETE）。スマホの最初の画面に「許可する」まで収める。埋め込み禁止の札は laravel/mcp がルートの名前で付ける。
  文は config('ai_connect.consent')
--}}
@php($t = config('ai_connect.consent'))
<x-layouts.guest :title="$t['page_title']">
  <x-ui.step-card icon="book" :title="strtr($t['title'], [':name' => $appName])" :hint="$t['lead']">
    <div class="rounded-xl bg-mark px-4 py-2.5">
      <p class="flex flex-wrap items-baseline gap-x-2"><span class="text-sm text-muted">{{ $t['host_label'] }}</span><span class="font-mono text-lg font-bold break-all">{{ $redirectHost }}</span></p>
      <p class="text-sm leading-relaxed text-muted">{{ $t['host_why'] }}</p>
    </div>

    <dl class="space-y-1.5 text-base">
      <div class="flex items-start gap-2">
        <x-ui.step-icon name="check" class="mt-1 size-4 shrink-0 text-prediction" />
        <div><dt class="inline font-bold">{{ $t['shows_label'] }}：</dt><dd class="inline">{{ implode('・', $t['shows']) }}</dd></div>
      </div>
      <div class="flex items-start gap-2">
        <span aria-hidden="true" class="w-4 shrink-0 text-center font-bold text-accent">×</span>
        <div><dt class="inline font-bold">{{ $t['hides_label'] }}：</dt><dd class="inline">{{ implode('・', $t['hides']) }}</dd></div>
      </div>
    </dl>

    <p class="text-sm leading-relaxed">
      {{ strtr($t['goes_to'], [':company' => $company['name'], ':country' => $company['country']]) }}
      {{-- 会社のプライバシーポリシーの住所がわかるときだけ、リンクを出す --}}
      @if ($company['policy'])
        <a href="{{ $company['policy'] }}" target="_blank" rel="noopener noreferrer">{{ strtr($t['policy'], [':company' => $company['name']]) }}</a>
      @endif
    </p>

    <p class="rounded-xl border border-accent/40 bg-accent-soft px-4 py-2.5 text-sm leading-relaxed font-bold text-accent">{{ $t['warning'] }}</p>

    <x-slot:action>
      <form method="POST" action="{{ route('passport.authorizations.approve') }}">
        @csrf
        <input type="hidden" name="state" value="{{ $state }}">
        <input type="hidden" name="client_id" value="{{ $appId }}">
        <input type="hidden" name="auth_token" value="{{ $authToken }}">
        <x-ui.big-button icon="check">{{ $t['approve'] }}</x-ui.big-button>
      </form>
      <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="mt-3">
        @csrf
        @method('DELETE')
        <input type="hidden" name="state" value="{{ $state }}">
        <input type="hidden" name="client_id" value="{{ $appId }}">
        <input type="hidden" name="auth_token" value="{{ $authToken }}">
        <x-ui.big-button tone="secondary">{{ $t['deny'] }}</x-ui.big-button>
      </form>
    </x-slot:action>
    <x-slot:later>{{ $t['later'] }}</x-slot:later>
  </x-ui.step-card>
</x-layouts.guest>
