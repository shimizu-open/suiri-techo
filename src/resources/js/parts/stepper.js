/*
  事件を作る画面の「1問ずつ進む」動き。画面は views/cases/create-steps.blade.php と components/case-new/*。
  1つのフォームの中の段（data-step）のうち、いまの段だけを見せ、「つぎへ」で1つずつ進める。送るのは最後の「事件を作る」の1回だけ。
  JavaScript が動かないときは全部の段が縦に並び、そのまま送れる（「つぎへ」「もどる」などはフォームに data-stepping が付いたときだけ見える）。
  ブラウザの「戻る」でも前の段へ戻れるよう、段を進めるたびに履歴に #step-n を積む。
*/

/** フォームの欄の、画面に写す言葉を返す（ラジオは選んだ札の言葉で、場面が仕事なら仕事の言い方。ほかは書いた文） */
const echoText = (form, name) => {
    const checked = form.querySelector(`input[type="radio"][name="${name}"]:checked`);
    // ラジオ（場面・手がかりの種類）なら、選んだ札の言葉を返す。仕事の場面を選んでいて仕事の言い方があれば、そちら
    if (checked) {
        const work = form.querySelector('input[name="scene"][value="work"]')?.checked;
        return (work && checked.dataset.labelWork) || checked.dataset.label || '';
    }
    const field = form.querySelector(`[name="${name}"]`);
    return field ? field.value.trim() : '';
};

/** 確かめの札などに書いた言葉を写し、書いてあるときの印（data-echo-filled）と、空のときの印（data-echo-empty）を出し分ける */
const refreshEchoes = (form) => {
    // 写す場所を1つずつ見て、その欄の言葉を入れる
    form.querySelectorAll('[data-echo]').forEach((el) => {
        el.textContent = echoText(form, el.dataset.echo);
    });
    // 書いてあるときだけ見せる所は、空なら隠す
    form.querySelectorAll('[data-echo-filled]').forEach((el) => {
        el.hidden = echoText(form, el.dataset.echoFilled) === '';
    });
    // 空のときだけ見せる所（「まだ書いていないよ」）は、書いてあれば隠す
    form.querySelectorAll('[data-echo-empty]').forEach((el) => {
        el.hidden = echoText(form, el.dataset.echoEmpty) !== '';
    });
};

/** 1つのフォームを、1問ずつ進む形にする */
const setupStepper = (form) => {
    const steps = [...form.querySelectorAll('[data-step]')];
    let current = 0;

    /** n 番目（0 から）の段だけを見せる。push なら履歴に積み、replace なら今の履歴を書き換え、focus なら段の見出しに読み上げの位置を移す */
    const show = (n, { push = false, replace = false, focus = true } = {}) => {
        current = Math.max(0, Math.min(n, steps.length - 1));
        refreshEchoes(form);
        // 段を1つずつ見て、いまの段だけを見せる
        steps.forEach((step, i) => {
            step.hidden = i !== current;
        });
        // 進めたときは、ブラウザの「戻る」で前の段に戻れるよう履歴に積む。「もどる」で戻ったときは、今の履歴を書き換えるだけ
        if (push) {
            history.pushState({ step: current }, '', `#step-${current + 1}`);
        } else if (replace) {
            history.replaceState({ step: current }, '', `#step-${current + 1}`);
        }
        window.scrollTo(0, 0);
        const heading = steps[current].querySelector('h2');
        // 見出しがあり、頼まれたときだけ、そこへ読み上げの位置を移す（画面は動かさない）
        if (heading && focus) {
            heading.tabIndex = -1;
            heading.classList.add('focus:outline-none');
            heading.focus({ preventScroll: true });
        }
    };

    /** いまの段の欄が決まりどおりか確かめる（なぞが空など）。だめなら最初の欄でブラウザの吹き出しを出して false を返す */
    const valid = () => [...steps[current].querySelectorAll('input, textarea')].every((field) => field.reportValidity());

    /** 次の段へ進む。skip（あとで書く）でなければ、先にいまの段の欄を確かめる */
    const next = (skip) => {
        // 書かずに進むときか、欄が決まりどおりのときだけ進める
        if (skip || valid()) {
            show(current + 1, { push: true });
        }
    };

    // 「つぎへ」「あとで書く」「もどる」「直す」が押されたら、それぞれの段へ動かす（送信はしない）
    form.addEventListener('click', (event) => {
        const button = event.target.closest('[data-step-next], [data-step-back], [data-step-goto]');
        // 段を動かすボタンでなければ何もしない
        if (!button) {
            return;
        }
        // ボタンの種類で行き先を分ける：つぎへ・あとで書く → 次の段／もどる → 前の段／直す → 決まった段
        if ('stepNext' in button.dataset) {
            next('skip' in button.dataset);
        } else if ('stepBack' in button.dataset) {
            show(current - 1, { replace: true });
        } else {
            show(Number(button.dataset.stepGoto) - 1, { push: true });
        }
    });

    // 1行の欄で Enter を押したら、送らずに次の段へ進む（日本語の変換を決める Enter は除く。最後の段ではふつうに送る）
    form.addEventListener('keydown', (event) => {
        const typing = event.target.matches('input[type="text"]');
        // 1行の欄での Enter で、変換中でなく、最後の段でないときだけ、次の段へ
        if (event.key === 'Enter' && typing && !event.isComposing && event.keyCode !== 229 && current < steps.length - 1) {
            event.preventDefault();
            next(false);
        }
    });

    // 書き出しのヒント（「あるはずなのに無かったものは？」など）に種類の指定があれば、手がかりの種類の札もそれに合わせる
    form.addEventListener('click', (event) => {
        const chip = event.target.closest('button[data-suggest]');
        const radio = chip?.dataset.kind ? form.querySelector(`input[name="clue_kind"][value="${chip.dataset.kind}"]`) : null;
        // 種類の札が見つかったときだけ選ぶ
        if (radio) {
            radio.checked = true;
        }
    });

    // ブラウザの「戻る」「進む」で、履歴に積んだ段を見せる（積んでいない所は最初の段）
    window.addEventListener('popstate', (event) => {
        show(event.state?.step ?? 0);
    });

    // 送り直し（検証のエラー）で戻ってきたら、エラーのある段から見せる。なければ最初の段から
    const error = form.querySelector('[data-error]');
    const start = error ? Math.max(0, steps.findIndex((step) => step.contains(error))) : 0;
    form.dataset.stepping = '';
    history.replaceState({ step: start }, '', window.location.pathname + window.location.search);
    show(start, { focus: false });
};

// 画面の中の、1問ずつ進むフォームを1つずつ取り出して、動くようにする
document.querySelectorAll('form[data-stepper]').forEach(setupStepper);
