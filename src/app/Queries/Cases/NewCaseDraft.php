<?php

namespace App\Queries\Cases;

use App\Enums\OriginKind;
use App\Enums\Scene;
use App\Models\User;
use App\Support\Guide\GuideLibrary;

/**
 * 事件を作る画面の初めの値を組み立てる係（URL の ?origin=practice&practice= と、POST /cases/draft から一度きりの入力で届く origin・label）。
 * 練習メニューから来たら、そのメニューの問いかけ（prompts）を各欄の案内にし、メニューの名前をきっかけとして残す
 */
class NewCaseDraft
{
    /** 教材を読む係を受け取る */
    public function __construct(private GuideLibrary $library) {}

    /**
     * 画面に渡す値を返す：origin_kind・origin_label・practice（メニュー1件か null）・title（なぞの初めの値）・
     * scene（ふだんの場面）・firstWork（仕事の場面の事件がまだ1つもないか）・partner（相棒の名前か null）
     */
    public function for(User $user, array $query): array
    {
        $origin = OriginKind::tryFrom((string) ($query['origin'] ?? ''));
        $practice = $origin === OriginKind::Practice ? $this->library->practice((string) ($query['practice'] ?? '')) : null;
        // 練習メニューから来たのにメニューが見つからなければ、きっかけなしにする
        if ($origin === OriginKind::Practice && $practice === null) {
            $origin = null;
        }
        $label = $practice['name'] ?? trim((string) ($query['label'] ?? ''));

        return [
            'origin_kind' => $origin?->value,
            'origin_label' => $origin && $label !== '' ? mb_substr($label, 0, config('suiri.limits.origin_label')) : null,
            'practice' => $practice,
            'title' => $practice['prompts']['title'] ?? null,
            'scene' => $user->default_scene ?? Scene::Daily,
            'firstWork' => ! $user->caseFiles()->where('scene', Scene::Work)->exists(),
            'partner' => $user->hasPartner() ? $user->partner_name : null,
            'kid' => $user->prefersKidWords(), // ふだんの場面が親子の人には、子ども向けの言葉で問いかける
        ];
    }
}
