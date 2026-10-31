<?php

namespace Tests\Feature\Policies;

use App\Models\AtticNote;
use App\Models\CaseFile;
use App\Models\Clue;
use App\Models\Hypothesis;
use App\Models\Prediction;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** 自分の記録は自分しか見られない・直せない・消せない（Policy）。他人の記録は運営者でも見えない */
class OwnerOnlyTest extends TestCase
{
    use RefreshDatabase;

    /** 確かめる記録の種類（モデルのクラス）の一覧 */
    public static function records(): array
    {
        return [
            'タスク' => [Task::class],
            '事件' => [CaseFile::class],
            '手がかり' => [Clue::class],
            '見立て' => [Hypothesis::class],
            '予想' => [Prediction::class],
            '屋根裏の棚' => [AtticNote::class],
        ];
    }

    /** 持ち主は見る・直す・消すことができ、他人はどれもできないことを、記録の種類ごとに確かめる */
    #[DataProvider('records')]
    public function test_only_the_owner_can_view_update_and_delete(string $model): void
    {
        $record = $model::factory()->create();
        $owner = User::find($record->user_id);
        $stranger = User::factory()->create();

        // 見る・直す・消すの3つを1つずつ確かめる
        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue(Gate::forUser($owner)->allows($ability, $record), "持ち主は {$ability} できる");
            $this->assertTrue(Gate::forUser($stranger)->denies($ability, $record), "他人は {$ability} できない");
        }
    }

    /** 住所の {case} が事件（CaseFile）に結びつき、->can() で他人の事件は 403 になることを確かめる */
    public function test_case_route_binding_and_can_middleware(): void
    {
        Route::middleware(['web', 'auth'])->get('/_test/cases/{case}', fn ($case) => $case->title)->can('view', 'case');
        $case = CaseFile::factory()->create(['title' => '吠えなかった犬']);

        $this->actingAs($case->user)->get('/_test/cases/'.$case->id)->assertOk()->assertSee('吠えなかった犬');
        $this->actingAs(User::factory()->create())->get('/_test/cases/'.$case->id)->assertForbidden();
    }
}
