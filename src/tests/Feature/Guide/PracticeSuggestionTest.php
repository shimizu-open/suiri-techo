<?php

namespace Tests\Feature\Guide;

use App\Support\Guide\GuideLibrary;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** 今日の画面の部品「今日の練習」（<x-today.practice-suggestion>）：控えめな提案で、圧力の言葉を使わないこと */
class PracticeSuggestionTest extends TestCase
{
    /** 練習メニューを渡すと、名前・短い説明・説明へのリンク・やらなくてよいことの一言が出ることを確かめる */
    public function test_it_shows_the_practice_gently(): void
    {
        $practice = app(GuideLibrary::class)->practice('silent-dog');

        $this->blade('<x-today.practice-suggestion :practice="$practice" />', ['practice' => $practice])
            ->assertSee(config('suiri.today.practice.title'))->assertSee($practice['name'])->assertSee($practice['description'])
            ->assertDontSee($practice['kid_description'])
            ->assertSee(route('guide.practices.index').'#practice-silent-dog', false)
            ->assertSee(config('suiri.today.practice.note'))
            ->assertDontSee('連続')->assertDontSee('まだやっていません')->assertDontSee('ポイント');
    }

    /** ふだんの場面が親子の人（kid）には、子ども向けの短い説明を出すことを確かめる */
    public function test_kid_description_is_for_family_users(): void
    {
        $practice = app(GuideLibrary::class)->practice('silent-dog');

        $this->blade('<x-today.practice-suggestion :practice="$practice" kid />', ['practice' => $practice])
            ->assertSee($practice['kid_description'])->assertDontSee($practice['description']);
    }

    /** 事件を作る画面（cases.create）があるときだけ、「始める」がその練習の問いかけ入りのリンクになることを確かめる */
    public function test_start_link_points_to_the_case_form_when_it_exists(): void
    {
        $practice = app(GuideLibrary::class)->practice('silent-dog');
        $view = $this->blade('<x-today.practice-suggestion :practice="$practice" />', ['practice' => $practice]);

        // 事件の画面のルートがあれば、始めるリンクが練習のキー入りで出る。まだなければ始めるボタンを出さない
        if (Route::has('cases.create')) {
            $view->assertSee(route('cases.create', ['origin' => 'practice', 'practice' => 'silent-dog']));
        } else {
            $view->assertDontSee(config('suiri.today.practice.start'));
        }
    }

    /** 練習メニューがない日（null）は、今日の画面の場所をとらないよう何も出さないことを確かめる */
    public function test_it_shows_empty_without_a_practice(): void
    {
        $this->blade('<x-today.practice-suggestion :practice="null" />')->assertDontSee(config('suiri.today.practice.title'));
    }
}
