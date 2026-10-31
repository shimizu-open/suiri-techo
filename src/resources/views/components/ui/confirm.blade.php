{{--
  消す前の確認。押すと確認の文と「消す」ボタンが開く（JavaScript なしで動く <details>）。
  使い方：<x-ui.confirm :action="route('tasks.destroy', $task)" message="このタスクを消しますか？" />
  action：送り先。method：DELETE（既定）など。label：最初に見せる文字。confirmLabel：本当に消すボタンの文字。
  中身（slot）に hidden の項目を入れると、一緒に送る。押す所（開く文字・消すボタン）はどちらも高さ 48px 以上（子どもの指でも押しやすく）
--}}
@props(['action', 'method' => 'DELETE', 'label' => '消す', 'message' => '消すと元に戻せません。消しますか？', 'confirmLabel' => '消す'])
<details {{ $attributes->class(['group']) }}>
  <summary class="inline-flex min-h-12 cursor-pointer items-center rounded-xl px-3 text-base font-semibold text-accent hover:bg-accent-soft">{{ $label }}</summary>
  <div class="mt-2 rounded-xl border border-accent/40 bg-accent-soft p-4 text-base leading-relaxed">
    <p>{{ $message }}</p>
    <form method="POST" action="{{ $action }}" class="mt-3">
      @csrf
      @method($method)
      {{ $slot }}
      <x-ui.button variant="danger">{{ $confirmLabel }}</x-ui.button>
    </form>
  </div>
</details>
