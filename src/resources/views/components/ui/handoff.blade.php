{{--
  相棒モードの「スマホを渡して」画面の大きな付箋（相棒の番の付箋）。1台のスマホを親子で回すとき、次に書く人と「見ないでね」をはっきり見せる。
  使い方：<x-ui.handoff :to="'長男'" :href="route('...')">お父さんは見ないでね</x-ui.handoff>
  to：次に書く人の呼び名（「長男にスマホを渡してね」になる）。中身（slot）：ひとこと（省略可）。
  href：受け取った人が押すボタンの行き先。label：そのボタンの文字（既定「受け取った！はじめる」）。
  リンクではなくフォームで進めたいときは、href の代わりに <x-slot:action> にボタン（<x-ui.big-button>）を入れて渡す
--}}
@props(['to', 'href' => null, 'label' => null])
<section {{ $attributes->class(['relative mt-3 rounded-sm bg-hypothesis-soft px-5 pt-10 pb-8 text-center']) }}>
  {{-- 付箋を留めるテープ（飾りではなく「渡す紙」だとわかる印） --}}
  <span aria-hidden="true" class="absolute -top-3 left-1/2 h-6 w-28 -translate-x-1/2 -rotate-2 bg-rule/80"></span>
  <span class="mx-auto grid size-24 place-items-center rounded-full bg-sheet text-hypothesis">
    <x-ui.step-icon name="partner" class="size-14" />
  </span>
  <h2 class="mt-5 text-2xl leading-snug">{{ __(config('suiri.ui.handoff_title'), ['to' => $to]) }}</h2>
  {{-- ひとこと（「お父さんは見ないでね」など）が渡されていれば、見出しの下に大きめに出す --}}
  @if (trim($slot) !== '')
    <p class="mt-3 text-lg font-semibold text-hypothesis">{{ $slot }}</p>
  @endif
  {{-- フォームのボタンが渡されていればそれを、なければ行き先があるときだけリンクのボタンを出す --}}
  @isset($action)
    <div class="mx-auto mt-8 max-w-sm">{{ $action }}</div>
  @elseif ($href)
    <div class="mx-auto mt-8 max-w-sm">
      <x-ui.big-button :href="$href" tone="hypothesis" icon="check">{{ $label ?? config('suiri.ui.handoff_button') }}</x-ui.big-button>
    </div>
  @endisset
</section>
