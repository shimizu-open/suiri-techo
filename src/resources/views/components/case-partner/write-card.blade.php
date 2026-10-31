{{--
  相棒の渡し合いの「どうしてだと思う？」を書くカード。なぞと手がかりの荷札（読むだけ）を見ながら、その人の見立てを1つ書く。
  送り先は見立ての受付（hypotheses.store）。書いた人（author）にいまの番を入れる。送り直しのときだけ入力の残りとエラーを出す（form_key）。
  使い方：<x-case-partner.write-card :case :who="'partner'" :names :word :kid :step="1" :cancel="…" :skip="…" />
  cancel：書くのをやめて ○ のカードに戻るリンク（もう1つ書いたあとだけ）。skip：書かずに次の人の番へ進むリンク（相棒が1つも書いていないときだけ）
--}}
@props(['case', 'who', 'names', 'word', 'kid' => false, 'step' => 1, 'cancel' => null, 'skip' => null])
@php($t = config('suiri.case_partner.write'))
@php($other = $who === 'partner' ? 'self' : 'partner')
<form method="POST" action="{{ route('hypotheses.store', $case) }}">
  @csrf
  <input type="hidden" name="form_key" value="partner-hypothesis">
  <input type="hidden" name="author" value="{{ $who }}">
  <x-ui.step-card :step="$step" :total="3" icon="question" tone="hypothesis" :title="$t['title']" :hint="__($t['mystery'], ['title' => $case->title])">
    {{-- 手がかりがあれば、考える材料として荷札で並べる（ここでは読むだけ） --}}
    @if ($case->clues->isNotEmpty())
      <div>
        <p class="text-sm font-bold text-clue">{{ $t['clues'] }}</p>
        <ul class="mt-2 space-y-2">
          {{-- 手がかりを1つずつ、番号と種類つきの荷札にする --}}
          @foreach ($case->clues as $clue)
            <li>
              <x-ui.evidence-tag :no="$loop->iteration" :label="$kid ? $clue->kind->kidLabel() : $clue->kind->label($case->scene)">
                <span class="line-clamp-2 break-words">{{ $clue->body }}</span>
              </x-ui.evidence-tag>
            </li>
          @endforeach
        </ul>
      </div>
    @endif
    <x-case.textarea name="body" form-key="partner-hypothesis" rows="3" required :maxlength="config('suiri.limits.hypothesis_body')"
      :label="__($t['label'], ['name' => $names[$who], 'word' => $word])" :placeholder="$t['placeholder']"
      :hint="__($t['word_hint'], ['word' => $word])" />
    <x-slot:action>
      <x-ui.big-button tone="hypothesis" icon="check">{{ $t['save'] }}</x-ui.big-button>
    </x-slot:action>
    {{-- カードのすぐ下：やめて戻る・書かずに次の人へ のリンク（あるときだけ）と、個人情報の注意書き（いつも）。
         注意書きを入力欄とボタンのあいだに置くと、主なボタンが最初の画面から押し出されるため、カードの下に置く --}}
    <x-slot:later>
      {{-- やめて戻る・書かずに次の人へ のリンクがあれば出す --}}
      @if ($cancel || $skip)
        <a href="{{ $cancel ?? $skip }}" class="mb-2 inline-flex min-h-11 items-center font-semibold">{{ $cancel ? $t['cancel'] : __($t['skip'], ['other' => $names[$other]]) }}</a>
      @endif
      <x-ui.privacy-note :scene="$case->scene" />
    </x-slot:later>
  </x-ui.step-card>
</form>
