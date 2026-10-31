<?php

namespace Tests\Feature\Auth;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 招待を管理するコマンド（invite:add・invite:list・invite:remove） */
class InviteCommandTest extends TestCase
{
    use RefreshDatabase;

    /** invite:add はメールアドレスを小文字にそろえてメモと一緒に足し、形のおかしいアドレスは足さないことを確かめる */
    public function test_invite_add(): void
    {
        $this->artisan('invite:add', ['email' => ' Holmes@Example.com ', '--note' => '依頼主'])->assertSuccessful();
        $this->assertDatabaseHas('invitations', ['email' => 'holmes@example.com', 'note' => '依頼主']);

        $this->artisan('invite:add', ['email' => 'not-an-email'])->assertFailed();
        $this->assertSame(1, Invitation::count());
    }

    /** invite:list は招待したメールアドレスと、使われたかどうかを出すことを確かめる */
    public function test_invite_list(): void
    {
        $this->artisan('invite:list')->expectsOutputToContain('招待はまだありません')->assertSuccessful();

        Invitation::create(['email' => 'hudson@example.com', 'note' => '大家さん']);
        $this->artisan('invite:list')->expectsOutputToContain('hudson@example.com')->assertSuccessful();
    }

    /** invite:remove は招待を外すが、登録済みのアカウントは消さず、招待にないアドレスは失敗として知らせることを確かめる */
    public function test_invite_remove(): void
    {
        Invitation::create(['email' => 'lestrade@example.com']);
        User::factory()->create(['email' => 'lestrade@example.com']);

        $this->artisan('invite:remove', ['email' => 'LESTRADE@example.com'])->assertSuccessful();
        $this->assertSame(0, Invitation::count());
        $this->assertSame(1, User::count());

        $this->artisan('invite:remove', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
