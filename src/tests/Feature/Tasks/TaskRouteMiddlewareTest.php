<?php

namespace Tests\Feature\Tasks;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * タスクの住所に、書き込みの回数制限と持ち主の確認が付いているかを確かめる。
 * middlewareFor を同じ動作に重ねて書くと、あとの指定が前の指定を上書きして回数制限が外れたことがあるため
 */
class TaskRouteMiddlewareTest extends TestCase
{
    /** 作る・直す・消すには回数制限、直す・消すには持ち主の確認が付いている */
    public function test_write_routes_keep_rate_limit_and_owner_check(): void
    {
        $expected = [
            'tasks.store' => ['throttle:writes'],
            'tasks.update' => ['throttle:writes', 'can:update,task'],
            'tasks.destroy' => ['throttle:writes', 'can:delete,task'],
            'tasks.edit' => ['can:update,task'],
        ];

        // ルートごとに、付いているはずのミドルウェアがすべてそろっているかを見る
        foreach ($expected as $name => $middleware) {
            $actual = Route::getRoutes()->getByName($name)->gatherMiddleware();
            // 1つずつ、実際に付いている一覧の中にあるかを確かめる
            foreach ($middleware as $one) {
                $this->assertContains($one, $actual, "{$name} に {$one} がない");
            }
        }
    }
}
