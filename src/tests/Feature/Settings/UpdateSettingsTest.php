<?php

namespace Tests\Feature\Settings;

use App\Enums\Scene;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 設定の画面（表示名・相棒の名前・ふだんの場面・記録のきっかけ） */
class UpdateSettingsTest extends TestCase
{
    use RefreshDatabase;

    /** 設定の画面に、いまの値と、場面の選択肢と、きっかけの例文が出ることを確かめる */
    public function test_the_settings_page_shows_current_values(): void
    {
        $user = User::factory()->withPartner('長男')->create(['name' => 'ワトソン', 'cue' => '夕食のあとに']);

        $this->actingAs($user)->get(route('settings.show'))->assertOk()
            ->assertSee('ワトソン')->assertSee('長男')->assertSee('夕食のあとに')
            ->assertSee('日常')->assertSee('親子')->assertSee('仕事')
            ->assertSee(config('suiri.cue_examples')[0]);
    }

    /** 設定を保存すると、表示名・相棒・場面・きっかけが変わり、空にした相棒は null になることを確かめる */
    public function test_settings_can_be_updated(): void
    {
        $user = User::factory()->withPartner('長男')->create();

        $this->actingAs($user)->patch(route('settings.update'), [
            'name' => '  シャーロック ',
            'partner_name' => '',
            'default_scene' => 'work',
            'cue' => '施術のあとに、自分が見たことを1つ',
        ])->assertRedirect(route('settings.show'))->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('シャーロック', $user->name);
        $this->assertNull($user->partner_name);
        $this->assertSame(Scene::Work, $user->default_scene);
        $this->assertSame('施術のあとに、自分が見たことを1つ', $user->cue);
    }

    /** 表示名が空、場面が選択肢にない、長すぎる、のときは保存されず、日本語のエラーの文が出ることを確かめる */
    public function test_invalid_settings_are_rejected(): void
    {
        $user = User::factory()->create(['name' => 'ワトソン']);

        $this->actingAs($user)->patch(route('settings.update'), [
            'name' => ' ',
            'default_scene' => 'office',
            'partner_name' => str_repeat('あ', config('suiri.limits.partner_name') + 1),
        ])->assertSessionHasErrors(['name' => '表示名を入力してください。', 'default_scene', 'partner_name']);

        $this->assertSame('ワトソン', $user->refresh()->name);
    }
}
