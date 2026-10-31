<?php

namespace App\Http\Requests\Settings;

use App\Enums\Scene;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 設定の画面の送信を確かめる：表示名（必須）・相棒の名前（任意）・ふだんの場面（日常・親子・仕事）・記録のきっかけ（任意） */
class UpdateSettingsRequest extends FormRequest
{
    /** ログインして同意した人なら送ってよい（ルートで確かめ済み。自分の設定しか直せない） */
    public function authorize(): bool
    {
        return true;
    }

    /** 送る前に、前後の空白を取る（空白だけの名前を防ぐ） */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'partner_name' => trim((string) $this->input('partner_name')),
            'cue' => trim((string) $this->input('cue')),
        ]);
    }

    /** 項目ごとの決まり（長さの上限は config/suiri.php の limits） */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.config('suiri.limits.user_name')],
            'partner_name' => ['nullable', 'string', 'max:'.config('suiri.limits.partner_name')],
            'default_scene' => ['required', Rule::enum(Scene::class)],
            'cue' => ['nullable', 'string', 'max:'.config('suiri.limits.cue')],
        ];
    }
}
