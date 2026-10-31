{{--
  いまやることのカード（1画面に1枚）。捜査ファイルの形：上に段の色のつまみ（タブ）、その下に問いの見出し・例・入力欄・主なボタン。
  使い方：
    <x-ui.step-card :step="2" :total="5" icon="eye" tone="clue" title="見たことを1つ教えて" hint="例：今週、体操着を2回わすれた">
      <x-ui.textarea name="body" label="見たこと" />
      <x-slot:action><x-ui.big-button tone="clue">これでOK</x-ui.big-button></x-slot:action>
      <x-slot:later><a href="...">あとで書く</a></x-slot:later>
    </x-ui.step-card>
  step・total：つまみの「ステップ 2 / 5」の数（どちらか省くと数を出さない）。icon：つまみに出す <x-ui.step-icon> の名前（省略可）。
  数も絵もないときは、つまみを出さずに角の丸いカードにする。
  tone：clue・hypothesis・prediction・check・answer・plain（既定）。title：問いの見出し。hint：例や一言の説明（省略可）。
  中身（slot）：入力欄など。action：主なボタン。later：「あとでできること」の小さなリンク（カードの下に1行で出す）。
  フォームにするときは、このカードを <form> で包む
--}}
@props(['step' => null, 'total' => null, 'icon' => null, 'tone' => 'plain', 'title', 'hint' => null])
{{-- 段の種類ごとの色の組み合わせ（tab：つまみ／frame：枠／top：見出しの面）。check は予想と同じ緑 --}}
@php($tones = [
  'clue' => ['tab' => 'bg-clue text-on-ink', 'frame' => 'border-clue', 'top' => 'bg-clue-soft'],
  'hypothesis' => ['tab' => 'bg-hypothesis text-on-ink', 'frame' => 'border-hypothesis', 'top' => 'bg-hypothesis-soft'],
  'prediction' => ['tab' => 'bg-prediction text-on-ink', 'frame' => 'border-prediction', 'top' => 'bg-prediction-soft'],
  'check' => ['tab' => 'bg-prediction text-on-ink', 'frame' => 'border-prediction', 'top' => 'bg-prediction-soft'],
  'answer' => ['tab' => 'bg-answer text-on-ink', 'frame' => 'border-answer', 'top' => 'bg-answer-soft'],
  'plain' => ['tab' => 'bg-ink text-on-ink', 'frame' => 'border-ink', 'top' => 'bg-mark'],
])
@php($c = $tones[$tone] ?? $tones['plain'])
@php($tab = ($step && $total) || $icon)
@php($headingId = 'step-'.substr(md5($title), 0, 8))
<div {{ $attributes }}>
  {{-- 数か絵があれば、カードの左上に段の色のつまみ（捜査ファイルのタブ）を出す --}}
  @if ($tab)
    <div class="inline-flex min-h-9 items-center gap-1.5 rounded-t-xl px-3.5 pt-1 text-sm font-bold {{ $c['tab'] }}">
      {{-- 絵が渡されていれば、つまみの先頭に出す --}}
      @if ($icon)
        <x-ui.step-icon :name="$icon" class="size-5" />
      @endif
      {{-- 何番目のステップかがわかるときだけ、「ステップ 2 / 5」を出す --}}
      @if ($step && $total)
        <span>{{ __(config('suiri.ui.step'), ['n' => $step, 'total' => $total]) }}</span>
      @endif
    </div>
  @endif
  <section aria-labelledby="{{ $headingId }}" @class(['overflow-hidden rounded-step border-2 bg-sheet', $c['frame'], 'rounded-tl-none' => $tab])>
    <div class="px-4 pt-4 pb-3 sm:px-6 {{ $c['top'] }}">
      <h2 id="{{ $headingId }}" class="text-xl sm:text-2xl">{{ $title }}</h2>
      {{-- 例や一言の説明があれば、問いのすぐ下に出す（何を書けばいいかを先に見せる） --}}
      @if ($hint)
        <p class="mt-1 text-base text-muted">{{ $hint }}</p>
      @endif
    </div>

    {{-- 入力欄か主なボタンがあるときだけ、見出しの下に中身の場所を作る --}}
    @if (trim($slot) !== '' || isset($action))
      <div class="space-y-4 px-4 pt-4 pb-5 sm:px-6">
        {{ $slot }}

        {{-- 主なボタンが渡されていれば、カードの一番下に出す --}}
        @isset($action)
          <div class="pt-1">{{ $action }}</div>
        @endisset
      </div>
    @endif
  </section>

  {{-- 「あとでできること」が渡されていれば、カードの外の下に小さく1行で出す --}}
  @isset($later)
    <div class="mt-3 text-sm text-muted">{{ $later }}</div>
  @endisset
</div>
