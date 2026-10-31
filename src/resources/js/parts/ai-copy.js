/*
  「写す」ボタンの動き。部品は components/case/ai-copy.blade.php（AI に相談する用に写す）と
  components/settings/ai-panel.blade.php（窓口の住所を写す）。
  押すと、data-target の欄（textarea・input）の文を端末のクリップボードに写し、「写したよ」を出す。アプリからはどこにも送らない。
  写せなかったら（許可がない・古いブラウザ）、文を開いて選んだ状態にし、手で写してもらう。
  JavaScript が動かないときはボタンが隠れたままで、文は「写す文を見る」を開けば手で写せる。
*/

/** 知らせの一文を出す（role="status" なので読み上げにも伝わる） */
const show = (status, message) => {
    // 知らせの場所か文がなければ、何もしない
    if (!status || !message) {
        return;
    }
    status.textContent = message;
    status.hidden = false;
};

/** 欄の文をクリップボードに写して、結果を知らせる */
const copy = async (box, field, status) => {
    // 新しい写し方（navigator.clipboard）を試す。だめなら（http・許可なし）文を開いて選んだ状態にし、手で写してもらう
    try {
        await navigator.clipboard.writeText(field.value);
        show(status, status?.dataset.done);
    } catch {
        const details = box.querySelector('[data-ai-copy-details]');
        // 文がたたまれていれば開く
        if (details) {
            details.open = true;
        }
        field.focus();
        field.select();
        show(status, status?.dataset.failed);
    }
};

/** かたまり1つ（data-ai-copy）のボタンを見せて、押したら写すようにする */
const setupCopy = (box) => {
    const button = box.querySelector('[data-ai-copy-button]');
    const field = document.getElementById(button?.dataset.target ?? '');
    // ボタンか写す文が見つからなければ、ボタンは隠したままにする
    if (!button || !field) {
        return;
    }
    button.hidden = false;
    button.addEventListener('click', () => copy(box, field, box.querySelector('[data-ai-copy-status]')));
};

// 画面の中の「写す」かたまりを1つずつ取り出して、動くようにする
document.querySelectorAll('[data-ai-copy]').forEach(setupCopy);
