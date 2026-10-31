{{--
  事件を作る画面（C-1・C-10・C-11）の1問ずつ進む形。1つのフォームの中に4つの問いを持つ：
  ①何が気になった？（なぞ・場面・相棒モード）→ ②見たこと・聞いたことを1つ（最初の手がかり）→ ③どうしてだと思う？（最初の見立て）→ ④これで事件を作るよ（確かめて送る）。
  JavaScript が動くときは resources/js/parts/stepper.js がいまの問いだけを見せ、「つぎへ」で1つずつ進む。動かないときは全部の問いが縦に並び、いちばん下の「事件を作る」で送れる。
  送り先と入力の名前は今の作る画面と同じ（cases.store・StoreCaseRequest）。受け取るのは draft（Queries/Cases/NewCaseDraft の戻り値。kid があれば子ども向けの言葉）。
  きっかけ（タスク・予定・練習メニュー）は隠し項目で送る
--}}
<x-layouts.app :title="config('suiri.case_new.page_title')">
  <form id="new-case" method="POST" action="{{ route('cases.store') }}" data-stepper class="group/new max-w-xl space-y-10">
    @csrf
    <input type="hidden" name="origin_kind" value="{{ old('origin_kind', $draft['origin_kind']) }}">
    <input type="hidden" name="origin_label" value="{{ old('origin_label', $draft['origin_label']) }}">
    <input type="hidden" name="practice_key" value="{{ old('practice_key', $draft['practice']['key'] ?? null) }}">

    <x-case-new.mystery :draft="$draft" :kid="$draft['kid'] ?? false" />
    <x-case-new.clue :draft="$draft" :kid="$draft['kid'] ?? false" />
    <x-case-new.hypothesis :draft="$draft" :kid="$draft['kid'] ?? false" />
    <x-case-new.confirm :draft="$draft" :kid="$draft['kid'] ?? false" />

    <x-ui.privacy-note />
  </form>
</x-layouts.app>
