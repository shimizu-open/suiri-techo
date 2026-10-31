<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * すべてのテストの親。画面の CSS・JS のビルド（Vite）がなくても画面を開けるようにしておく
 */
abstract class TestCase extends BaseTestCase
{
    /** テストごとの準備：Vite の読み込みを空にする（npm run build をしていなくても画面のテストが通る） */
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
}
