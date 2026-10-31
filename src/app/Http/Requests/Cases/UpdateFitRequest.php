<?php

namespace App\Http\Requests\Cases;

use App\Enums\Fit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ○×（手がかりが見立てに合うか）を付ける・外す送信を確かめる（C-4）。付けるとき（PUT）は合う・合わないのどちらかが要る。
 * 見立てと手がかりが同じ事件のものでなければ断る（持ち主かは、両方ともルートの ->can() で確かめ済み）
 */
class UpdateFitRequest extends FormRequest
{
    /** 見立てと手がかりが同じ事件のものかを確かめる */
    public function authorize(): bool
    {
        return $this->route('hypothesis')->case_file_id === $this->route('clue')->case_file_id;
    }

    /** 項目ごとの決まり（外す DELETE のときは fit は要らない） */
    public function rules(): array
    {
        return [
            'fit' => [$this->isMethod('PUT') ? 'required' : 'nullable', Rule::enum(Fit::class)],
        ];
    }
}
