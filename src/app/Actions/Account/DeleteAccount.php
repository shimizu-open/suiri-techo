<?php

namespace App\Actions\Account;

use App\Actions\Calendar\DisconnectCalendar;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

/**
 * 退会の用事（A-6）。その人のデータをすべて物理削除する。
 * 1. カレンダー連携があれば、Google で許可を取り消し（revoke）、連携の行を消す（カレンダーの担当の DisconnectCalendar。
 *    Google が止まっていても手元の鍵は消える。利用者の Google にある専用カレンダーそのものは消さない）
 * 2. 利用者の行を消す。タスク・事件（手がかり・見立て・○×・予想…）・屋根裏の棚・ふりかえり・名推理カード・閉じたひとことは、
 *    表の cascadeOnDelete でまとめて消える
 * 3. ほかの端末のログインの記録（sessions）と、招待の行（メールアドレスを持つので）も消す
 * 4. AI とつないだ許可（Laravel Passport の通行証・更新の札・引換券・デバイスの番号）も消す。Passport の表には
 *    「親を消したら子も消える」が付いていないので、user_id（更新の札はその人の通行証の番号）で明示して消す。
 *    AI のサービスの登録（自己登録の行）は利用者の記録ではないので残す（使われなければ ai:prune-apps が消す）
 * ログアウト（いまのセッションを捨てる）は受付がする
 */
class DeleteAccount
{
    /** カレンダーとつなぐのをやめる用事を受け取る */
    public function __construct(private readonly DisconnectCalendar $disconnectCalendar) {}

    /** その人のデータをすべて消す。表を消す途中で失敗したら、表への書き込みはなかったことにする */
    public function handle(User $user): void
    {
        $this->disconnectCalendar->handle($user);

        DB::transaction(function () use ($user) {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            Invitation::where('email', Invitation::normalize($user->email))->delete();
            $this->forgetAiGrants($user);
            $user->delete();
        });
    }

    /** AI とつないだ許可の行（通行証・更新の札・引換券・デバイスの番号）を、その人の分だけ消す */
    private function forgetAiGrants(User $user): void
    {
        $tokenIds = Passport::token()->newQuery()->where('user_id', $user->id)->pluck('id');
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $tokenIds)->delete();
        Passport::token()->newQuery()->where('user_id', $user->id)->delete();
        Passport::authCode()->newQuery()->where('user_id', $user->id)->delete();
        Passport::deviceCode()->newQuery()->where('user_id', $user->id)->delete();
    }
}
