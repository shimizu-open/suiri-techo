{{--
  一覧のページ送り（前へ・何ページ目か・次へ）。1ページで収まるときは何も出さない。
  使い方：<x-review.pager :paginator="$misses" />。paginator：paginate() の結果
--}}
@props(['paginator'])
{{-- 2ページ以上あるときだけ出す --}}
@if ($paginator->hasPages())
  <nav aria-label="ページ送り" {{ $attributes->class(['mt-4 flex items-center justify-between gap-2 text-sm']) }}>
    <x-ui.button variant="secondary" size="sm" :href="$paginator->previousPageUrl()" @class(['invisible' => $paginator->onFirstPage()])>‹ 前へ</x-ui.button>
    <span class="text-muted">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }} ページ</span>
    <x-ui.button variant="secondary" size="sm" :href="$paginator->nextPageUrl()" @class(['invisible' => ! $paginator->hasMorePages()])>次へ ›</x-ui.button>
  </nav>
@endif
