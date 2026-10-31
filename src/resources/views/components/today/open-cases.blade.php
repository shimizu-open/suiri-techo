{{--
  今日の画面の下：捜査中の事件（最近さわった順に数件）。カードにせず小さく出す。押すと事件の詳細へ。
  1件の見た目は事件の担当の <x-case.summary compact>。事件がなければ一言だけ（書くボタンは上のカードにある）。
  使い方：<x-today.open-cases :cases="$cases" />
  cases：捜査中の事件の最近の数件（CaseFile のコレクション。数は config('suiri.today.open_cases')）。
  App\Queries\Cases\OpenCasesQuery::get($user) を渡すと、手がかり・見立て・答え合わせ待ちの数も出る（数がなければ省く）
--}}
@props(['cases' => []])
@php($words = config('suiri.today.cases'))
<section aria-labelledby="open-cases-title" {{ $attributes->class(['border-t border-rule pt-4']) }}>
  <div class="flex items-start justify-between gap-3">
    <div class="min-w-0">
      <h2 id="open-cases-title" class="flex items-center gap-2 text-sm font-bold text-clue">
        <x-ui.icon name="search" class="size-5" />{{ $words['title'] }}
      </h2>
      <p class="text-xs text-muted">{{ $words['lead'] }}</p>
    </div>
    {{-- 事件の一覧の画面があれば、「ぜんぶ見る」へのリンクを置く --}}
    @if (Route::has('cases.index'))
      <a href="{{ route('cases.index') }}" class="inline-flex min-h-11 shrink-0 items-center text-sm text-muted">{{ $words['all'] }}</a>
    @endif
  </div>
  {{-- 事件があれば短い一覧に、なければ一言だけ出す --}}
  @if (collect($cases)->isNotEmpty())
    <ul class="-mx-4 mt-1 divide-y divide-rule md:mx-0">
      {{-- 事件を1件ずつ短い形で出す --}}
      @foreach ($cases as $case)
        <x-case.summary :case="$case" compact />
      @endforeach
    </ul>
  @else
    <p class="mt-2 text-sm text-muted">{{ $words['empty'] }}</p>
  @endif
</section>
