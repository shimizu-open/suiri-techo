<?php

namespace Tests\Feature\Ai;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Ai\Concerns\ConnectsAi;
use Tests\TestCase;

/** ai:prune-apps：だれにも使われないまま日がたった AI の自己登録だけを消す */
class PruneAiAppsTest extends TestCase
{
    use ConnectsAi, RefreshDatabase;

    /** 古くて通行証のない登録は消え、通行証のある登録と新しい登録は残る */
    public function test_prunes_only_old_unused_registrations(): void
    {
        $unused = $this->registerApp();
        $used = $this->registerApp();
        $fresh = $this->registerApp();
        $this->grantToken(User::factory()->create()->id, $used);
        DB::table('oauth_clients')->whereIn('id', [$unused, $used])->update(['created_at' => now()->subDays(8)]);

        $this->artisan('ai:prune-apps')->assertSuccessful();

        $this->assertDatabaseMissing('oauth_clients', ['id' => $unused]);
        $this->assertDatabaseHas('oauth_clients', ['id' => $used]);
        $this->assertDatabaseHas('oauth_clients', ['id' => $fresh]);
    }
}
