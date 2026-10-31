<?php

namespace App\Http\Controllers\Guide;

use App\Actions\Guide\SaveSceneAttempt;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guide\StoreSceneAttemptRequest;
use App\Support\Guide\GuideLibrary;
use Illuminate\Http\RedirectResponse;

/** 名推理カードの「書いて裏返す」（POST /guide/scenes/{key}/attempt）。自分の推理を保存して（上書き）、カードの裏へ送る */
class SaveSceneAttemptController extends Controller
{
    /** 推理を保存してカードの裏へ送る。ないキーなら 404。空のまま送られたら保存せずに裏へ送る */
    public function __invoke(StoreSceneAttemptRequest $request, GuideLibrary $library, SaveSceneAttempt $save, string $key): RedirectResponse
    {
        abort_unless($library->scene($key), 404);
        $attempt = $save->handle($request->user(), $key, $request->validated('answer'));

        return redirect()->route('guide.scenes.show', ['key' => $key, 'side' => 'back'])
            ->with($attempt ? ['status' => config('suiri.guide.back.saved')] : []);
    }
}
