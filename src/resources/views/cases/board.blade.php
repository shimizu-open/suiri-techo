{{--
  事件の画面（記録の中心）。開いたら「いまやること」が1枚だけ大きく見える形。上から：事件 No. となぞ → 進み具合 →
  済んだ段の要約（見たことは荷札の束）→ ワトソンのひとこと → いまやることのカード → ほかにできること（小さく1行ずつ）。
  パソコンでは、骨組みを広い幅（wide）にして、いまやることを左の広い列、進み具合と済んだことを右の細い列に置く（components/case-step/desk）。
  値は受付（CaseController@show）が App\Queries\Cases\CaseBoardQuery で組み立てる（calendarConnected：カレンダーとつないでいるか・
  selfName：自分の呼び名も）。どのカードを出すかは desk が決める。事件の番号は URL の番号と同じ（case_files.id）
--}}
<x-layouts.app :title="__(config('suiri.case_board.page_title'), ['no' => $case->id])" wide>
  <x-slot:actions>
    <x-ui.button size="sm" variant="ghost" :href="route('cases.index')">{{ config('suiri.case_board.back') }}</x-ui.button>
  </x-slot:actions>

  <x-case-step.head :case="$case" :kid="$kid" />
  <x-case-step.desk :case="$case" :hypotheses="$hypotheses" :hidden-count="$hiddenCount" :turn="$turn" :partner-name="$partnerName"
    :self-name="$selfName" :kid="$kid" :step="$step" :watson="$watson" :board="$board" :suggest="$suggest"
    :attic-options="$atticOptions" :prompts="$prompts" :open="$open" :today="$today" :check-default="$checkDefault"
    :calendar-connected="$calendarConnected" class="mt-4" />
</x-layouts.app>
