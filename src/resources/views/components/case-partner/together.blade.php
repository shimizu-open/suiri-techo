{{--
  相棒の渡し合いの4つ目の段：「いっしょに見せ合おう！」のカード。2人が何個書いたか（文は見せない）を並べ、見せ合う受付（cases.reveal）へ送る。
  相棒がまだ書いていなければ「まだ」と出し、相棒の番に戻るリンクを添える（書かなくても見せ合える）。
  使い方：<x-case-partner.together :case :names :partner-count="1" :self-count="2" more="#relay-self-write" :partner-href="…" />
--}}
@props(['case', 'names', 'partnerCount' => 0, 'selfCount' => 0, 'more', 'partnerHref'])
@php($t = config('suiri.case_partner.together'))
<form method="POST" action="{{ route('cases.reveal', $case) }}">
  @csrf
  <x-ui.step-card :step="3" :total="3" icon="partner" tone="hypothesis" :title="$partnerCount > 0 ? $t['title'] : $t['title_alone']"
    :hint="__($t['hint'], ['partner' => $names['partner']])">
    <ul class="grid grid-cols-2 gap-3">
      {{-- 2人それぞれの呼び名と、書いた数（まだなら「まだ」）を並べる --}}
      @foreach (['partner' => $partnerCount, 'self' => $selfCount] as $who => $count)
        <li class="flex min-w-0 items-center gap-2 rounded-xl bg-hypothesis-soft px-3 py-3">
          <x-case-partner.who :who="$who" :name="$names[$who]" />
          <span class="min-w-0">
            <span class="block truncate text-sm font-bold">{{ $names[$who] }}</span>
            <span @class(['block text-lg font-bold', 'text-muted' => $count === 0])>{{ $count > 0 ? __($t['count'], ['n' => $count]) : $t['missing'] }}</span>
          </span>
        </li>
      @endforeach
    </ul>
    {{-- 相棒がまだ書いていなければ、相棒の番に戻るリンクを出す --}}
    @if ($partnerCount === 0)
      <a href="{{ $partnerHref }}" class="inline-flex min-h-11 items-center font-semibold">{{ __($t['back'], ['partner' => $names['partner']]) }}</a>
    @endif
    <x-slot:action>
      <x-ui.big-button tone="hypothesis" icon="sparkle">{{ $t['button'] }}</x-ui.big-button>
    </x-slot:action>
    <x-slot:later>
      <a href="{{ $more }}" class="inline-flex min-h-11 items-center font-semibold">{{ $t['more'] }}</a>
    </x-slot:later>
  </x-ui.step-card>
</form>
