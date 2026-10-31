/*
  提案のボタン（チップ）の動き（requirements.md の 4-10 S-1〜S-5。AI なし）。部品は components/case/suggest-chips.blade.php。
  押すと、同じフォームの欄に書き出し・種類・確かめる日と時刻・使う知識を入れるだけで、送信はしない（保存は本人がする）。
  予想の穴埋め（S-3）も「型を使う」のチップ（data-text）なので、ほかの書き出しと同じく押すと欄に入る。
  JavaScript が動かなくても、フォームはふつうに書いて送れる。
  チップの並び（data-suggest-chips）は、押しても何も起きないボタンを見せないよう、初めは隠してある（hidden）。このファイルが動いたら見せる。
*/

/** 入力欄に書き出しを足す。空ならそのまま入れ、何か書いてあれば改行して後ろに足す。入れたら欄にカーソルを置く */
const insertText = (field, text) => {
    const current = field.value.replace(/\s+$/u, '');
    // 同じ書き出しがもう入っていなければ足す（二度押しで二重にしない）
    if (!current.includes(text)) {
        field.value = current === '' ? text : `${current}\n${text}`;
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }
    // かぎかっこで終わる書き出し（ご本人の言葉：「」）は、かっこの中にカーソルを置く。ほかは最後に置く
    const end = field.value.endsWith('」') ? field.value.length - 1 : field.value.length;
    field.focus();
    field.setSelectionRange(end, end);
};

/** フォームの中の、名前で決まる欄に値を入れる（その欄がなければ何もしない） */
const setValue = (form, name, value) => {
    const input = form?.querySelector(`[name="${name}"]`);
    // 欄があるときだけ入れ、変わったことを知らせる
    if (input) {
        input.value = value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
};

/** チップ1つの「押したときにすること」を、同じフォームの欄に当てはめる */
const applyChip = (chip) => {
    const form = chip.closest('form');
    const targetId = chip.closest('[data-suggest-target]')?.dataset.suggestTarget;
    const { text, kind, date, time, attic } = chip.dataset;
    const field = targetId ? document.getElementById(targetId) : null;

    // 書き出しがあり、入れる先の欄があれば、そこに足す
    if (text && field) {
        insertText(field, text);
    }
    // 種類の指定があれば（「なかった」など）、その種類の札を選ぶ
    if (kind) {
        const radio = form?.querySelector(`input[name="kind"][value="${kind}"]`);
        // 札が見つかったときだけ選ぶ
        if (radio) {
            radio.checked = true;
        }
    }
    // 確かめる日の候補なら、日付と時刻をその候補にそろえる。時刻のない候補（明日など）は時刻を空にする
    // （前に押したカレンダーの予定の時刻が残って、その時刻で保存されないように）
    if (date) {
        setValue(form, 'check_on', date);
        setValue(form, 'check_at', time ?? '');
    }
    // 屋根裏の棚の候補なら、「使う知識」にその知識を選ぶ
    if (attic) {
        setValue(form, 'attic_note_id', attic);
    }
};

// チップが押されたら、書き出しなどを欄に入れる（送信はしない）
document.addEventListener('click', (event) => {
    const chip = event.target.closest('button[data-suggest]');
    // チップでなければ何もしない
    if (chip) {
        event.preventDefault();
        applyChip(chip);
    }
});

// 隠しておいたチップの並びを1つずつ取り出して見せる（ここまで動いたので、押せば欄に入る）
document.querySelectorAll('[data-suggest-chips]').forEach((chips) => {
    chips.hidden = false;
});
