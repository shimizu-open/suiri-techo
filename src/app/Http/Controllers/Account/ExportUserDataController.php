<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\ExportUserData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** データのダウンロード（GET /settings/export）。自分のデータをすべて JSON のファイルにして渡す。ファイル名に日付を付ける */
class ExportUserDataController extends Controller
{
    /** 自分のデータを集め、ダウンロードさせる（日本語はそのまま読める形・字下げつき） */
    public function __invoke(Request $request, ExportUserData $export): JsonResponse
    {
        $filename = config('suiri.review.export_filename').'-'.today()->toDateString().'.json';

        return response()->json(
            $export->handle($request->user()),
            200,
            ['Content-Disposition' => 'attachment; filename="'.$filename.'"'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
