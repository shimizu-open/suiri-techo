/*
  「何分かかりそう？」「実際は何分だった？」のよく使う分のボタンの動き。部品は components/task/minutes-field.blade.php。
  ボタンを押すと、同じまとまりの分の欄にその数を入れ、押したボタンを塗る。欄に手で書いたら、同じ数のボタンだけを塗る。
  JavaScript が動かないときはボタンが隠れたままで、欄に書けば送れる。
*/

/** まとまり1つ（data-minute-chips）のボタンと欄をつなぐ */
const setupChips = (group) => {
    const input = group.querySelector('input[type="number"]');
    const chips = group.querySelectorAll('[data-minutes]');

    /** 欄の値と同じ数のボタンだけを、押した印（aria-pressed）にする */
    const mark = () => {
        // ボタンを1つずつ見て、欄の値と同じなら印を付け、違えば外す
        chips.forEach((chip) => chip.setAttribute('aria-pressed', String(chip.dataset.minutes === input.value)));
    };

    // ボタンを1つずつ見せて、押したら欄にその数を入れる
    chips.forEach((chip) => {
        chip.hidden = false;
        chip.addEventListener('click', () => {
            input.value = chip.dataset.minutes;
            mark();
        });
    });
    input.addEventListener('input', mark);
};

// 画面の中の分のまとまりを1つずつ取り出して、動くようにする
document.querySelectorAll('[data-minute-chips]').forEach(setupChips);
