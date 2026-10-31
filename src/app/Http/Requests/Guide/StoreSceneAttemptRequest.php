<?php

namespace App\Http\Requests\Guide;

use Illuminate\Foundation\Http\FormRequest;

/** 名推理カードの「自分の推理」の送信を確かめる：書かなくてもよい（書かずに裏返せる）。書くなら長さの上限まで */
class StoreSceneAttemptRequest extends FormRequest
{
    /** ログインして同意した人なら送ってよい（ルートで確かめ済み。自分の推理しか書けない） */
    public function authorize(): bool
    {
        return true;
    }

    /** 確かめる前に、前後の空白を取る（空白だけの推理は「書かなかった」として扱う） */
    protected function prepareForValidation(): void
    {
        $this->merge(['answer' => trim((string) $this->input('answer'))]);
    }

    /** 項目ごとの決まり（長さの上限は config/suiri.php の limits.scene_answer） */
    public function rules(): array
    {
        return [
            'answer' => ['nullable', 'string', 'max:'.config('suiri.limits.scene_answer')],
        ];
    }

    /** エラーの文に出す項目の名前 */
    public function attributes(): array
    {
        return ['answer' => '自分の推理'];
    }
}
