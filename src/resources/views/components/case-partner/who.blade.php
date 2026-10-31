{{--
  相棒の渡し合いで「だれのものか」を見せる丸い印（呼び名の1文字目）。相棒はからし色、自分は紺で塗り、荷札の印と2人の見立ての欄をつなぐ。
  使い方：<x-case-partner.who who="partner" name="長男さん" />（size：md 直径 32px・既定／sm 直径 24px）
--}}
@props(['who', 'name', 'size' => 'md'])
<span aria-hidden="true" @class([
  'grid shrink-0 place-items-center rounded-full font-bold text-on-ink',
  'bg-hypothesis' => $who === 'partner',
  'bg-ink' => $who !== 'partner',
  'size-8 text-base' => $size !== 'sm',
  'size-6 text-xs' => $size === 'sm',
])>{{ mb_substr($name, 0, 1) }}</span>
