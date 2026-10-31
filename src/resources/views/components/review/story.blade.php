{{--
  ノーベリ帳・屋根裏の棚のいちばん上に置く短い説明：段の絵・何をする所かの一言・名前の由来の一言（いつも見える）と、
  名前のお話（開くと読める <details>）。親子の場面では、子ども向けの呼び名と一言を先に出す。読むための所なので枠で囲まない。
  使い方：<x-review.story icon="norbury" lead="…" origin="…" summary="名前のお話を読む" :story="…" kid-name="まちがいノート" kid-tagline="…" />
  kid-name・kid-tagline：子ども向けの呼び名と一言（親子の場面のときだけ渡す。省略すると出さない）
--}}
@props(['icon', 'lead', 'origin', 'summary', 'story', 'kidName' => null, 'kidTagline' => null])
<div {{ $attributes->class(['mb-5 flex items-start gap-3']) }}>
  <span class="grid size-12 shrink-0 place-items-center rounded-full bg-mark text-ink"><x-ui.step-icon :name="$icon" class="size-7" /></span>
  <div class="min-w-0 flex-1">
    {{-- 親子の場面なら、子ども向けの呼び名と一言を先に出す --}}
    @if ($kidName)
      <p class="text-lg leading-snug font-bold">{{ $kidName }}</p>
      <p class="text-sm text-muted">{{ $kidTagline }}</p>
    @endif
    <p @class(['text-base leading-relaxed', 'mt-1' => $kidName])>{{ $lead }}</p>
    <p class="mt-1 text-sm text-muted">{{ $origin }}</p>
    <details class="group">
      <summary class="inline-flex min-h-11 cursor-pointer items-center gap-1 text-sm font-bold underline underline-offset-4">{{ $summary }}</summary>
      <p class="mb-1 rounded-xl border border-rule bg-sheet p-3 text-sm leading-relaxed">{{ $story }}</p>
    </details>
  </div>
</div>
