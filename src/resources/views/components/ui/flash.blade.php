{{--
  お知らせ。受付で ->with('status', '保存しました。')（うまくいった）・->with('error', '…')（うまくいかなかった）と渡すと出る。
  レイアウトが置くので、ふだんは画面から使わない
--}}
{{-- うまくいったお知らせがあれば、緑の札で出す --}}
@if (session('status'))
  <p role="status" class="mb-4 rounded-lg border border-ok/30 bg-ok-soft px-4 py-3 text-sm font-semibold text-ok">{{ session('status') }}</p>
@endif
{{-- うまくいかなかったお知らせがあれば、赤の札で出す --}}
@if (session('error'))
  <p role="alert" class="mb-4 rounded-lg border border-accent/30 bg-accent-soft px-4 py-3 text-sm font-semibold text-accent">{{ session('error') }}</p>
@endif
