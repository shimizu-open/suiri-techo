{{--
  ナビ（きょう・事件・手引き・ふりかえり）。項目は config('suiri.nav')。
  variant="bottom"：スマホの下の固定ナビ（md 以上では隠す）／variant="side"：パソコンの左の縦ナビ（md 未満では隠す）。
  まだ画面（ルート）がない項目は、リンクにせず薄く出す（Route::has で確かめる）。
  いまの画面の項目は、絵を紺で塗った丸の中に出し、名前を太く濃くする（子どもでも「いまここ」がひと目でわかる）
--}}
@props(['variant' => 'bottom'])
@php($side = $variant === 'side')
<nav aria-label="メインメニュー" @class([
  'fixed inset-x-0 bottom-0 z-40 border-t border-rule bg-sheet md:hidden' => ! $side,
  'hidden md:sticky md:top-0 md:flex md:h-screen md:w-56 md:shrink-0 md:flex-col md:border-r md:border-rule md:px-3 md:py-5' => $side,
]) @if (! $side) style="padding-bottom: env(safe-area-inset-bottom)" @endif>
  {{-- 縦のナビ（パソコン）だけ、上に題字を出す --}}
  @if ($side)
    <a href="{{ \App\Support\Landing::url() }}" class="mb-6 block px-3 no-underline">
      <span class="block text-xl font-bold tracking-wide">{{ config('app.name') }}</span>
      <span class="block text-xs tracking-wider text-muted">Deduction Notebook</span>
    </a>
  @endif

  <ul @class(['grid grid-cols-4' => ! $side, 'space-y-1' => $side])>
    {{-- ナビの項目を1つずつ取り出し、画面があればリンク、なければ薄い文字で並べる。いまの画面の項目は印を付ける --}}
    @foreach (config('suiri.nav') as $item)
      @php($ready = Route::has($item['route']))
      @php($on = $ready && request()->routeIs(...$item['match']))
      <li>
        {{-- 画面（ルート）があればリンクにする。まだなければ「準備中」として押せない形で出す --}}
        @if ($ready)
          <a href="{{ route($item['route']) }}" @if ($on) aria-current="page" @endif @class([
            'no-underline',
            'flex min-h-16 flex-col items-center justify-center gap-1 pt-1.5 pb-1 text-xs' => ! $side,
            'flex min-h-12 items-center gap-3 rounded-xl px-3 py-2.5 text-base' => $side,
            'font-bold text-ink' => $on && ! $side, 'font-semibold text-muted' => ! $on && ! $side,
            'bg-ink font-bold text-on-ink' => $on && $side, 'font-semibold text-muted hover:bg-mark hover:text-ink' => ! $on && $side,
          ])>
            <span @class(['grid h-8 w-14 place-items-center rounded-full' => ! $side, 'bg-ink text-on-ink' => $on && ! $side])>
              <x-ui.icon :name="$item['icon']" class="size-6 shrink-0" />
            </span>
            <span>{{ $item['label'] }}</span>
          </a>
        @else
          <span aria-disabled="true" title="準備中" @class([
            'opacity-40 text-muted',
            'flex min-h-16 flex-col items-center justify-center gap-1 pt-1.5 pb-1 text-xs font-semibold' => ! $side,
            'flex min-h-12 items-center gap-3 px-3 py-2.5 text-base font-semibold' => $side,
          ])>
            <span @class(['grid h-8 w-14 place-items-center' => ! $side])><x-ui.icon :name="$item['icon']" class="size-6" /></span>
            <span>{{ $item['label'] }}</span>
          </span>
        @endif
      </li>
    @endforeach
  </ul>
</nav>
