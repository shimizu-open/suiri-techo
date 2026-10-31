<?php

/*
| 入力の確かめ（検証）で出す日本語の文。:attribute には項目の名前が入る。
| 項目の名前は下の attributes に集める（各 FormRequest には書かない）。新しい列を検証するときはここに足す
*/

return [
    'accepted' => ':attributeにチェックを入れてください。',
    'after' => ':attributeは :date より後の日付にしてください。',
    'after_or_equal' => ':attributeは :date 以降の日付にしてください。',
    'array' => ':attributeの形が正しくありません。',
    'before' => ':attributeは :date より前の日付にしてください。',
    'before_or_equal' => ':attributeは :date 以前の日付にしてください。',
    'between' => [
        'numeric' => ':attributeは :min から :max の間にしてください。',
        'string' => ':attributeは :min 文字から :max 文字の間にしてください。',
        'array' => ':attributeは :min 個から :max 個の間にしてください。',
    ],
    'boolean' => ':attributeの値が正しくありません。',
    'date' => ':attributeは正しい日付にしてください。',
    'date_format' => ':attributeは :format の形にしてください。',
    'different' => ':attributeと :other は別のものにしてください。',
    'email' => ':attributeは正しいメールアドレスにしてください。',
    'enum' => ':attributeは選択肢から選んでください。',
    'exists' => ':attributeが見つかりません。',
    'filled' => ':attributeを入力してください。',
    'in' => ':attributeは選択肢から選んでください。',
    'integer' => ':attributeは整数にしてください。',
    'max' => [
        'numeric' => ':attributeは :max 以下にしてください。',
        'string' => ':attributeは :max 文字以内にしてください。',
        'array' => ':attributeは :max 個以内にしてください。',
    ],
    'min' => [
        'numeric' => ':attributeは :min 以上にしてください。',
        'string' => ':attributeは :min 文字以上にしてください。',
        'array' => ':attributeは :min 個以上にしてください。',
    ],
    'multiple_of' => ':attributeは :value 刻みにしてください。',
    'not_in' => ':attributeの値が正しくありません。',
    'not_regex' => ':attributeの形が正しくありません。',
    'numeric' => ':attributeは数字にしてください。',
    'prohibited' => ':attributeは入力できません。',
    'regex' => ':attributeの形が正しくありません。',
    'required' => ':attributeを入力してください。',
    'required_if' => ':otherが :value のときは、:attributeも入力してください。',
    'required_with' => ':valuesを入力するときは、:attributeも入力してください。',
    'same' => ':attributeと :other が同じになるようにしてください。',
    'size' => [
        'numeric' => ':attributeは :size にしてください。',
        'string' => ':attributeは :size 文字にしてください。',
        'array' => ':attributeは :size 個にしてください。',
    ],
    'string' => ':attributeは文字で入力してください。',
    'unique' => 'その:attributeはすでに使われています。',
    'url' => ':attributeは正しい URL にしてください。',

    // 項目の名前（エラーの文の :attribute に入る）。表の列の名前と同じキーにする。機能ごとの FormRequest には書かない
    'attributes' => [
        // ログイン・設定
        'accept_terms' => '利用規約とプライバシーポリシーへの同意',
        'confirm_age' => '年齢の確認',
        'name' => '表示名',
        'partner_name' => '相棒の名前',
        'default_scene' => 'ふだんの場面',
        'cue' => '記録のきっかけ',
        'email' => 'メールアドレス',
        // タスク
        'title' => '題名',
        'memo' => 'メモ',
        'due_on' => '期日',
        'starts_at' => '開始時刻',
        'duration_minutes' => '予想時間',
        'actual_minutes' => 'かかった時間',
        // 事件・手がかり・見立て・予想
        'scene' => '場面',
        'kind' => '種類',
        'body' => '本文',
        'status' => '状態',
        'fit' => '合う・合わない',
        'origin_kind' => 'きっかけ',
        'origin_label' => 'きっかけ',
        'practice_key' => '練習メニュー',
        'partner_mode' => '相棒モード',
        'author' => '書いた人',
        'conclusion' => '結論',
        'statement' => '予想の文',
        'confidence' => '確かさ',
        'check_on' => '確かめる日',
        'check_at' => '確かめる時刻',
        'result' => '答え合わせの結果',
        'result_note' => '一言',
        'miss_reason' => '何を見落とした？',
        'attic_note_id' => '屋根裏の知識',
        // 屋根裏の棚・ふりかえり・名推理カード
        'rule' => 'つまり？',
        'examples' => 'たとえば？',
        'abstraction' => 'つまり？',
        'concrete' => 'たとえば？',
        'changed_case_file_id' => '考えが変わった事件',
        'answer' => '自分の推理',
    ],

    // 項目ごとの決まった文（上の共通の文より先に使われる）
    'custom' => [
        // 予想の穴埋め（S-3）の「＿＿」が残ったまま保存しようとしたとき
        'statement' => ['not_regex' => '「＿＿」の所を、自分の言葉に書き換えてください（予想の文は保存したあと直せません）。'],
    ],

];
