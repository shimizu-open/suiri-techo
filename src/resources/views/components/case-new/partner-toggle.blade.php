{{--
  「相棒といっしょに考える？」の切り替え（チェックボックス1つ。送る名前は partner_mode）。行のどこを押しても切り替わる。
  使い方：<x-case-new.partner-toggle :partner="$draft['partner']" />（partner：設定の相棒の名前。名前がない人には画面が出さない）。
  入れると、同じフォームの「どうして？」の段の入力欄が隠れ、事件を作ったあとに書く説明に替わる（CSS の :has で切り替える）。
  切り替えの目印は data-partner（Tailwind は [] の中の _ を空白に読むので、input[name=partner_mode] では選べない）
--}}
@props(['partner'])
<label class="group/pick flex min-h-16 cursor-pointer items-center gap-3 rounded-xl border-2 border-rule bg-sheet px-3.5 py-3 group-has-[:checked]/pick:border-ink group-has-[:checked]/pick:bg-mark group-has-[:focus-visible]/pick:outline-2 group-has-[:focus-visible]/pick:outline-offset-2 group-has-[:focus-visible]/pick:outline-accent">
  <input type="checkbox" name="partner_mode" value="1" data-partner class="sr-only" @checked(old('partner_mode'))>
  <x-ui.step-icon name="partner" class="size-7 shrink-0 text-muted group-has-[:checked]/pick:text-ink" />
  <span class="min-w-0 flex-1">
    <span class="block text-base font-bold">{{ __(config('suiri.case_new.partner.label'), ['partner' => $partner]) }}</span>
    <span class="block text-sm leading-snug text-muted">{{ config('suiri.case_new.partner.desc') }}</span>
  </span>
  <span aria-hidden="true" class="grid size-7 shrink-0 place-items-center rounded-lg border-2 border-rule bg-sheet text-transparent group-has-[:checked]/pick:border-ink group-has-[:checked]/pick:bg-ink group-has-[:checked]/pick:text-on-ink">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="m6 12.5 4 4 8-8.5"/></svg>
  </span>
</label>
