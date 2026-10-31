<?php

namespace App\Http\Controllers\Attic;

use App\Actions\Attic\SaveAtticNote;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attic\StoreAtticNoteRequest;
use App\Http\Requests\Attic\UpdateAtticNoteRequest;
use App\Models\AtticNote;
use App\Queries\Review\AtticNotesQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 屋根裏の棚（GET/POST /attic・PATCH/DELETE /attic/{note}）。自分の知識（つまり？・たとえば？）の一覧・足す・直す・消す。
 * 直す・消すの持ち主の確認はルートの ->can()（AtticNotePolicy）。直すときは一覧の中のその1枚を ?edit={id} で入力欄にする
 */
class AtticNoteController extends Controller
{
    /** 屋根裏の棚の画面を返す。?edit={id} はその1枚を直す欄に、?add=1 は自分で書く欄を開いて出す */
    public function index(Request $request, AtticNotesQuery $query): View
    {
        $user = $request->user();

        return view('attic.index', [
            'notes' => $query->notes($user),
            'caseOptions' => $query->caseOptions($user),
            'editing' => $request->integer('edit') ?: null,
            'adding' => $request->boolean('add'),
            'kid' => $user->prefersKidWords(),
        ]);
    }

    /** 知識を足して、棚へ戻す */
    public function store(StoreAtticNoteRequest $request, SaveAtticNote $save): RedirectResponse
    {
        $save->handle($request->user(), $request->validated());

        return redirect()->route('attic.index')->with('status', config('suiri.review.attic.flash.stored'));
    }

    /** 知識を直して、棚へ戻す */
    public function update(UpdateAtticNoteRequest $request, SaveAtticNote $save, AtticNote $note): RedirectResponse
    {
        $save->handle($request->user(), $request->validated(), $note);

        return redirect()->route('attic.index')->with('status', config('suiri.review.attic.flash.updated'));
    }

    /** 知識を消して、棚へ戻す（この知識を使った予想は残り、つながりだけが外れる） */
    public function destroy(AtticNote $note): RedirectResponse
    {
        $note->delete();

        return redirect()->route('attic.index')->with('status', config('suiri.review.attic.flash.deleted'));
    }
}
