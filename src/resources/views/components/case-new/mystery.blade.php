{{--
  事件を作る画面の1問目「何が気になった？」：なぞ（title）・場面（scene）・相棒モード（partner_mode。相棒の名前がある人だけ）。
  使い方：<x-case-new.mystery :draft="$draft" :kid="$kid" />（draft は Queries/Cases/NewCaseDraft の戻り値）。
  練習メニューから来たら、なぞにメニューの問いかけが入っていることを例の行で知らせ、カードの下で練習のしかたを開いて読めるようにする。
  タスク・予定の「気になる」から来たら、きっかけを小さく出す。場面で「仕事」を選ぶと、やさしい一言の注意が出る
--}}
@props(['draft', 'kid' => false])
@php($w = config('suiri.case_new.mystery'))
@php($practice = $draft['practice'])
@php($selected = (string) old('scene', $draft['scene'] instanceof \BackedEnum ? $draft['scene']->value : $draft['scene']))
<x-case-new.step :n="1">
  <x-ui.step-card :step="1" :total="4" tone="plain" :title="$w['title']"
    :hint="$practice ? __($w['practice_example'], ['name' => $practice['name']]) : $w['example']">
    {{-- タスク・予定の「気になる」から来たら（練習メニューではないとき）、何から作る事件かを小さく出す --}}
    @if ($draft['origin_label'] && ! $practice)
      <p class="text-sm text-muted">{{ __(config('suiri.case_new.origin'), ['kind' => \App\Enums\OriginKind::from($draft['origin_kind'])->label(), 'label' => $draft['origin_label']]) }}</p>
    @endif

    <x-case-new.field name="title" :value="$draft['title']" required :maxlength="config('suiri.limits.case_title')"
      :placeholder="$w['placeholder']" :tip="$w['tip']" autocomplete="off" enterkeyhint="next">
      {{ $w['label'] }}<span class="ml-1 text-sm font-normal text-muted">{{ $w['meaning'] }}</span>
    </x-case-new.field>

    <fieldset>
      <legend class="mb-2 text-base font-semibold">{{ config('suiri.case_new.scene.legend') }}</legend>
      <div class="grid grid-cols-3 gap-2">
        {{-- 場面を1つずつ取り出し、絵つきの札にする（親子の人には子ども向けの呼び名）。いまの値と同じものを選んでおく --}}
        @foreach (\App\Enums\Scene::cases() as $scene)
          <x-case-new.pick name="scene" :value="$scene->value" :checked="$selected === $scene->value" :pic="$scene->value"
            :label="$kid ? $scene->kidLabel() : $scene->label()" :desc="config('suiri.case_new.scene.desc.'.$scene->value)" />
        @endforeach
      </div>
      @error('scene')
        <p data-error class="mt-1 text-sm text-accent">{{ $message }}</p>
      @enderror
    </fieldset>
    <x-case-new.work-note :first="$draft['firstWork']" />

    {{-- 相棒の名前が設定にある人だけ、相棒といっしょに考えるかを選べるようにする --}}
    @if ($draft['partner'])
      <x-case-new.partner-toggle :partner="$draft['partner']" />
    @endif

    <x-slot:action><x-case-new.next /></x-slot:action>
    {{-- 練習メニューから来たら、カードの下で練習のしかたを開いて読めるようにする（親子の人には子ども向けの説明） --}}
    @if ($practice)
      <x-slot:later>
        <details>
          <summary class="inline-flex min-h-11 cursor-pointer items-center text-base font-semibold underline underline-offset-4">{{ $w['practice_how'] }}</summary>
          <p class="mt-1 text-base leading-relaxed text-ink">{{ $kid ? ($practice['kid_description'] ?? $practice['description']) : $practice['description'] }}</p>
        </details>
      </x-slot:later>
    @endif
  </x-ui.step-card>
</x-case-new.step>
