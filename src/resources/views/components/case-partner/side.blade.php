{{--
  見せ合いの画面の「1人分の見立て」の欄。だれのものかの丸い印と呼び名、その人の見立てを番号つきで並べる（番号は荷札の印の数字と同じ）。
  使い方：<x-case-partner.side who="partner" name="長男さん" word="見立て" :hypotheses="$board['partner']" />
--}}
@props(['who', 'name', 'word', 'hypotheses'])
<div class="min-w-0 rounded-2xl border border-rule bg-paper p-4">
  <h3 class="flex items-center gap-2 text-base font-bold">
    <x-case-partner.who :who="$who" :name="$name" />
    <span class="min-w-0 break-words">{{ __(config('suiri.case_partner.reveal.side'), ['name' => $name, 'word' => $word]) }}</span>
  </h3>
  <ol class="mt-3 space-y-2">
    {{-- 見立てを書いた順に番号つきで出す。1つもなければ「まだ書いていないよ」 --}}
    @forelse ($hypotheses as $hypothesis)
      <li class="flex gap-2 text-base leading-relaxed">
        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-mark text-sm font-bold">{{ $loop->iteration }}</span>
        <span class="min-w-0 break-words">{{ $hypothesis->body }}</span>
      </li>
    @empty
      <li class="text-base text-muted">{{ config('suiri.case_partner.reveal.none') }}</li>
    @endforelse
  </ol>
</div>
