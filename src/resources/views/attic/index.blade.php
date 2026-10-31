{{--
  屋根裏の棚（R-5）。上から：切り替え → 短い説明（何をする所か・名前の由来の一言。お話は開くと読める）→ 棚。
  ・自分で書くとき（?add=1、か書いて送ったのにエラーで戻ったとき）：書くカードを1枚だけ出す（いまやることは書くこと。上の説明もしまう）
  ・棚が空のとき：絵と一言と「自分で決まりを1つ書く」ボタンだけ
  ・知識があるとき：棚を見るのが主なので、一覧（新しい順）を出し、書くリンクは上に小さく1行で置く
  1枚を直しているあいだ（?edit=）は、書くカードもリンクも出さない（入力し直しの値が2つの欄に入らないように）。
  親子の場面では、子ども向けの呼び名「ぼくの図鑑・わたしの図鑑」と一言で出す
--}}
@php($a = config('suiri.review.attic'))
@php($writing = ! $editing && ($adding || $errors->any()))
<x-layouts.app :title="\App\Support\Label::word('attic')">
  <x-review.tabs current="attic" :kid="$kid" />
  {{-- 書いているあいだは、書くことに集中できるよう名前の説明をしまう --}}
  @unless ($writing)
    <x-review.story icon="attic" :lead="$kid ? $a['kid_lead'] : $a['lead']" :origin="$a['origin']" :summary="config('suiri.review.origin_story')" :story="$a['story']"
      :kid-name="$kid ? \App\Support\Label::kidWord('attic') : null" :kid-tagline="$kid ? $a['kid_tagline'] : null" />
  @endunless

  {{-- 書くときは書くカード、棚が空なら絵と一言、知識があれば一覧を出す --}}
  @if ($writing)
    <x-review.attic-add :case-options="$caseOptions" />
  @elseif ($notes->isEmpty())
    <x-ui.empty-state icon="attic" :title="$a['empty']['title']" :action-href="route('attic.index', ['add' => 1])" :action-label="$a['add']">{{ $a['empty']['note'] }}</x-ui.empty-state>
  @else
    <section aria-labelledby="attic-list" class="border-t border-rule pt-4">
      <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 id="attic-list" class="text-lg">棚の決まり <span class="text-base font-bold tabular-nums">{{ $notes->total() }}</span><span class="text-sm font-normal text-muted">個</span></h2>
        {{-- 1枚を直していなければ、自分で書くリンクを出す --}}
        @unless ($editing)
          <a href="{{ route('attic.index', ['add' => 1]) }}" class="inline-flex min-h-11 items-center gap-1 rounded-lg border-2 border-ink px-3 text-sm font-bold no-underline hover:bg-mark">
            <x-ui.icon name="plus" class="size-4" />{{ $a['add'] }}
          </a>
        @endunless
      </div>
      <ul class="divide-y divide-rule border-y border-rule">
        {{-- 知識を1枚ずつ取り出して並べる。直している1枚だけ入力欄にする --}}
        @foreach ($notes as $note)
          <x-review.attic-item :note="$note" :case-options="$caseOptions" :editing="$editing === $note->id" />
        @endforeach
      </ul>
      <x-review.pager :paginator="$notes" />
    </section>
  @endif
</x-layouts.app>
