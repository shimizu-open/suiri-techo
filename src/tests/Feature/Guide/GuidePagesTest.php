<?php

namespace Tests\Feature\Guide;

use App\Models\User;
use App\Support\Guide\GuideLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 手引きの画面（入口・技能・演繹と帰納とアブダクション・名推理カードの一覧・練習メニュー）が開くこと */
class GuidePagesTest extends TestCase
{
    use RefreshDatabase;

    /** 手引きの入口が開き、入口の案内と、流れの段と10の技能がすべて出ることを確かめる */
    public function test_index_shows_entries_and_all_skills(): void
    {
        $library = app(GuideLibrary::class);
        $response = $this->actingAs(User::factory()->create())->get(route('guide.index'))->assertOk()
            ->assertSee('名推理カード')->assertSee('練習メニュー')->assertSee('演繹・帰納・アブダクション')
            ->assertSee('もしそうなら')->assertSee('答え合わせ');

        // 10の技能が1つずつ、技能の画面へのリンクとして出ているか確かめる
        foreach ($library->skills() as $skill) {
            $response->assertSee($skill['name'])->assertSee(route('guide.skills.show', $skill['key']));
        }
    }

    /** 10の技能の画面がどれも開き、説明・英語の引用と出典・3つの場面の練習の例・よくある失敗が出ることを確かめる */
    public function test_every_skill_page_opens(): void
    {
        $user = User::factory()->create();

        // 技能を1つずつ開いて、中身が出ているか確かめる
        foreach (app(GuideLibrary::class)->skills() as $skill) {
            $this->actingAs($user)->get(route('guide.skills.show', $skill['key']))->assertOk()
                ->assertSee($skill['name'])->assertSee($skill['body'])->assertDontSee($skill['kid_body'])
                ->assertSee($skill['quote_en'])->assertSee($skill['quote_source'])
                ->assertSee($skill['practice']['adult'])->assertSee($skill['practice']['kid'])->assertSee($skill['practice']['work'])
                ->assertSee($skill['pitfall']);
        }
    }

    /** ?kid=1 を付けると、技能の説明が子ども向けに切り替わることを確かめる */
    public function test_skill_page_switches_to_the_kid_explanation(): void
    {
        $skill = app(GuideLibrary::class)->skill('S6');

        $this->actingAs(User::factory()->create())->get(route('guide.skills.show', ['key' => 'S6', 'kid' => 1]))->assertOk()
            ->assertSee($skill['kid_name'])->assertSee($skill['kid_body'])->assertDontSee($skill['body']);
    }

    /** 技能の画面に、その技能を練習できる練習メニューと名推理カードへのリンクが出ることを確かめる */
    public function test_skill_page_links_to_its_practices_and_scenes(): void
    {
        $library = app(GuideLibrary::class);
        $response = $this->actingAs(User::factory()->create())->get(route('guide.skills.show', 'S7'))->assertOk();

        // S7 の練習メニューの名前が出ているか確かめる
        foreach ($library->practicesFor('S7') as $practice) {
            $response->assertSee($practice['name']);
        }
        // S7 の名推理カードへのリンクが出ているか確かめる
        foreach ($library->scenesFor('S7') as $scene) {
            $response->assertSee(route('guide.scenes.show', $scene['key']));
        }
    }

    /** 演繹・帰納・アブダクションのページが開き、3つの説明と、このアプリのどの段で使うかが出ることを確かめる */
    public function test_reasoning_page_opens_for_adults_and_kids(): void
    {
        $reasoning = app(GuideLibrary::class)->reasoning();
        $user = User::factory()->create();
        $adult = $this->actingAs($user)->get(route('guide.reasoning'))->assertOk()->assertSee($reasoning['intro']);
        $kid = $this->actingAs($user)->get(route('guide.reasoning', ['kid' => 1]))->assertOk()->assertSee($reasoning['kid_intro']);

        // 3つの考え方を1つずつ、大人向けと子ども向けの説明が出し分けられているか確かめる
        foreach ($reasoning['items'] as $item) {
            $adult->assertSee($item['name'])->assertSee($item['adult'])->assertSee($item['in_app'])->assertDontSee($item['kid']);
            $kid->assertSee($item['kid'])->assertDontSee($item['adult']);
        }
    }

    /** 名推理カードの一覧に全部のカードが出て、ホームズが外した場面に印が付くことを確かめる */
    public function test_scene_list_shows_every_card_and_marks_mistakes(): void
    {
        $scenes = app(GuideLibrary::class)->scenes();
        $response = $this->actingAs(User::factory()->create())->get(route('guide.scenes.index'))->assertOk()
            ->assertSee('ホームズが外した');

        // カードを1枚ずつ、題とカードへのリンクが出ているか確かめる
        foreach ($scenes as $scene) {
            $response->assertSee($scene['title'])->assertSee(route('guide.scenes.show', $scene['key']));
        }
    }

    /** 練習メニューの一覧に11種が出て、名前・由来・説明・子ども向けの説明が並ぶことを確かめる */
    public function test_practice_list_shows_every_practice(): void
    {
        $practices = app(GuideLibrary::class)->practices();
        $response = $this->actingAs(User::factory()->create())->get(route('guide.practices.index'))->assertOk();

        // 練習メニューを1つずつ、中身が出ているか確かめる
        foreach ($practices as $practice) {
            $response->assertSee($practice['name'])->assertSee($practice['origin'])
                ->assertSee($practice['description'])->assertSee($practice['kid_description'])
                ->assertSee('id="practice-'.$practice['key'].'"', false);
        }
    }

    /** ないキーの技能・カードは 404 になることを確かめる */
    public function test_unknown_keys_are_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('guide.skills.show', 'S11'))->assertNotFound();
        $this->actingAs($user)->get(route('guide.scenes.show', 'no-such-scene'))->assertNotFound();
        $this->actingAs($user)->get(route('guide.scenes.show', ['key' => 'no-such-scene', 'side' => 'back']))->assertNotFound();
        $this->actingAs($user)->post(route('guide.scenes.attempt', 'no-such-scene'), ['answer' => 'x'])->assertNotFound();
    }

    /** ログインしていない人は手引きを開けず、トップへ送られることを確かめる */
    public function test_guests_are_sent_to_the_top_page(): void
    {
        $this->get(route('guide.index'))->assertRedirect(route('home'));
        $this->get(route('guide.scenes.show', 'scandal-17-steps'))->assertRedirect(route('home'));
        $this->post(route('guide.scenes.attempt', 'scandal-17-steps'), ['answer' => 'x'])->assertRedirect(route('home'));
    }
}
