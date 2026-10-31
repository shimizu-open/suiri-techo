{{--
  事件の一覧（C-12）。この画面ですること：新しい事件を作るか、続きを調べる事件を選ぶ。
  上から：「新しい事件を作る」の大きなボタン → 状態のつまみ（捜査中・解決・おあずけ）の引き出しに、事件を最近さわった順に並べる。
  1件ごとに「事件 No.12」・なぞ・段ごとの進み具合の印、確かめる日が来ていれば赤い「答え合わせの日」の札。
  事件がまだ1つもないときは、絵と一言と「最初の事件を作ってみよう」のボタンだけ（<x-ui.empty-state>）にする。
  つまみの中だけが空のときは、引き出しの中に小さな絵と一言を出す（主なボタンは上の「新しい事件を作る」）
  kid：子ども向けの言葉を出すか（ふだんの場面が親子の人。受付 CaseController@index が決めて渡す）
--}}
@php($w = config('suiri.case_list'))
<x-layouts.app :title="config('suiri.labels.words.case')">
  {{-- 事件がまだ1つもない（場面でしぼってもいない）ときは、空の画面と主なボタンだけを、あれば作るボタンと引き出しを出す --}}
  @if (array_sum($counts) === 0 && ! $scene)
    <x-ui.empty-state icon="sparkle" :title="$w['empty']['none']['title']" :action-href="route('cases.create')" :action-label="$w['empty']['none']['action']">
      {{ $w['empty']['none']['body'] }}
    </x-ui.empty-state>
  @else
    <x-ui.big-button :href="route('cases.create')" icon="plus">{{ $w['create'] }}</x-ui.big-button>

    <x-case.list-filter class="mt-6" :status="$status" :scene="$scene" :counts="$counts" :kid="$kid">
      {{-- この状態の事件がなければ一言を、あれば並びとページ送りを出す --}}
      @if ($cases->isEmpty())
        @php($empty = $w['empty'][$scene ? 'scene' : $status->value])
        <div class="flex items-start gap-3 px-4 py-6">
          <span class="grid size-11 shrink-0 place-items-center rounded-full bg-mark text-ink">
            <x-ui.step-icon :name="$status === \App\Enums\CaseStatus::Solved ? 'check' : 'book'" class="size-6" />
          </span>
          <div class="min-w-0">
            <p class="text-lg leading-snug font-bold">{{ $empty['title'] }}</p>
            <p class="mt-1 text-base text-muted">{{ $empty['body'] }}</p>
          </div>
        </div>
      @else
        <ul class="mt-1 divide-y divide-rule border-t border-rule">
          {{-- 事件を1件ずつ出す --}}
          @foreach ($cases as $case)
            <x-case.summary :case="$case" :kid="$kid" />
          @endforeach
        </ul>
        {{-- ページが2つ以上あるときだけ、前後へのリンクを出す --}}
        @if ($cases->hasPages())
          <nav aria-label="ページ" class="grid grid-cols-3 items-center gap-2 border-t border-rule px-4 py-3 text-sm">
            <span>
              {{-- 前のページがあれば、戻るリンクを置く --}}
              @if (! $cases->onFirstPage())
                <x-ui.button size="sm" variant="secondary" :href="$cases->previousPageUrl()">{{ $w['prev'] }}</x-ui.button>
              @endif
            </span>
            <span class="text-center text-muted tabular-nums">{{ $cases->currentPage() }} / {{ $cases->lastPage() }}</span>
            <span class="text-right">
              {{-- 次のページがあれば、進むリンクを置く --}}
              @if ($cases->hasMorePages())
                <x-ui.button size="sm" variant="secondary" :href="$cases->nextPageUrl()">{{ $w['next_page'] }}</x-ui.button>
              @endif
            </span>
          </nav>
        @endif
      @endif
    </x-case.list-filter>
  @endif
</x-layouts.app>
