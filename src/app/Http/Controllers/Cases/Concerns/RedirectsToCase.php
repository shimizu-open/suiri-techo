<?php

namespace App\Http\Controllers\Cases\Concerns;

use App\Models\CaseFile;
use App\Services\Watson\WatsonNotes;
use Illuminate\Http\RedirectResponse;

/**
 * 事件の受付が共通で使う「事件の画面へ戻す」部品。お知らせ（status）を付け、保存した文にほかの人がわかる情報らしきものがあれば
 * ワトソンの W-5 の警告（watson_alert）も付ける（保存は止めない）。警告があるときは、画面の一番上から見せるため場所の印（#…）を付けない。
 * フォームが隠し項目 back_open（見直し・○× のカードなど）を送ってきたら、?open= を付けて同じカードに戻す（値は決まった候補だけ）
 */
trait RedirectsToCase
{
    /**
     * 事件の画面へ戻す。$texts は保存した文（W-5 を確かめる）、$anchor は戻る場所の印、$turn は相棒モードの番
     *
     * @param  array<int, ?string>  $texts
     */
    protected function backToCase(CaseFile $case, string $status, array $texts = [], ?string $anchor = null, ?string $turn = null): RedirectResponse
    {
        $alert = app(WatsonNotes::class)->alertFor(...$texts);
        $url = route('cases.show', array_filter(['case' => $case, 'turn' => $turn, 'open' => $this->backOpen()]));
        // 警告がなければ、さわっていた場所へ戻す（○× を続けて付けるときなどに、画面が上へ飛ばないように）
        if ($alert === null && $anchor !== null) {
            $url .= '#'.$anchor;
        }

        return redirect()->to($url)->with('status', $status)->with('watson_alert', $alert);
    }

    /**
     * フォームの隠し項目 back_open が決まった候補（config の case_board.back_open。{id} は数字）なら、その値を返す。
     * 送られていない・候補にない値なら null（?open= を付けず、ふだんの事件の画面へ戻す）
     */
    private function backOpen(): ?string
    {
        $value = request()->input('back_open');
        // 文字で送られていなければ、何も付けない
        if (! is_string($value) || $value === '') {
            return null;
        }
        // 候補を1つずつ、{id} を数字に置きかえた形とくらべる。ぴったり合う候補があれば、その値で戻す
        foreach (config('suiri.case_board.back_open') as $allowed) {
            $pattern = '/^'.str_replace('\{id\}', '[0-9]{1,10}', preg_quote($allowed, '/')).'$/';
            // 合えば、その値を返す
            if (preg_match($pattern, $value) === 1) {
                return $value;
            }
        }

        return null;
    }
}
