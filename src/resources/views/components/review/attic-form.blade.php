{{--
  屋根裏の棚の1枚を、一覧の中でその場で直す欄（入力欄は <x-review.attic-fields>）。自分で書くときは <x-review.attic-add> を使う。
  使い方：<x-review.attic-form :action="route('attic.update', $note)" :note="$note" :case-options="…">（やめるのリンク）</x-review.attic-form>
  note：直す知識。caseOptions：「事件の id => なぞ」。中身（slot）：保存ボタンの横に置くもの（「やめる」など）
--}}
@props(['action', 'note', 'caseOptions' => [], 'submit' => '直す'])
<form method="POST" action="{{ $action }}" {{ $attributes->class(['space-y-4']) }}>
  @csrf
  @method('PATCH')
  <x-review.attic-fields :note="$note" :case-options="$caseOptions" />
  <div class="flex flex-wrap items-center gap-2">
    <x-ui.button>{{ $submit }}</x-ui.button>
    {{ $slot }}
  </div>
  <x-ui.privacy-note />
</form>
