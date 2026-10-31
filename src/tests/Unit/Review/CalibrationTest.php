<?php

namespace Tests\Unit\Review;

use App\Services\Review\Calibration;
use Tests\TestCase;

/** 確かさの答え合わせの区間の集計（Calibration::summarize。DB を使わない） */
class CalibrationTest extends TestCase
{
    /** 確かさと当たったかの組を、同じ確かさで n 件（うち hits 件が当たり）作る */
    private function rows(int $confidence, int $n, int $hits): array
    {
        return array_map(fn ($i) => ['confidence' => $confidence, 'hit' => $i < $hits], range(0, $n - 1));
    }

    /** 区間は 50〜55・60〜65・70〜75・80〜85・90〜95 の5つで、それぞれの件数と当たりの数を数えることを確かめる */
    public function test_rows_are_split_into_buckets(): void
    {
        $result = (new Calibration)->summarize([...$this->rows(50, 2, 1), ...$this->rows(55, 3, 2), ...$this->rows(95, 1, 1)]);

        $this->assertSame(['50〜55％', '60〜65％', '70〜75％', '80〜85％', '90〜95％'], array_column($result['buckets'], 'label'));
        $this->assertSame([5, 0, 0, 0, 1], array_column($result['buckets'], 'count'));
        $this->assertSame([3, 0, 0, 0, 1], array_column($result['buckets'], 'hits'));
        $this->assertSame(6, $result['counted']);
    }

    /** 件数が 5 件以上の区間だけ当たった割合（％）を出し、5 件未満は「まだ判断できない」（rate が null）になることを確かめる */
    public function test_buckets_with_few_rows_are_not_judged_yet(): void
    {
        $result = (new Calibration)->summarize([...$this->rows(70, 6, 4), ...$this->rows(80, 4, 4)]);
        [, , $seventy, $eighty] = $result['buckets'];

        $this->assertTrue($seventy['enough']);
        $this->assertSame(67, $seventy['rate']);
        $this->assertFalse($eighty['enough']);
        $this->assertNull($eighty['rate']);
        $this->assertSame(5, $result['min']);
    }

    /** 図の横の位置に使う確かさの平均は、区間の中の実際の確かさの平均で、1件もない区間は区間のまん中になることを確かめる */
    public function test_mean_confidence_per_bucket(): void
    {
        $result = (new Calibration)->summarize([...$this->rows(60, 3, 1), ...$this->rows(65, 1, 0)]);

        $this->assertEqualsWithDelta(61.25, $result['buckets'][1]['mean'], 0.001);
        $this->assertEqualsWithDelta(92.5, $result['buckets'][4]['mean'], 0.001);
    }

    /** 区間の境目の件数の決まりは config で変えられる（ここでは 3 件にすると、3 件の区間も判断する）ことを確かめる */
    public function test_minimum_count_comes_from_config(): void
    {
        config(['suiri.calibration_min_count' => 3]);

        $result = (new Calibration)->summarize($this->rows(90, 3, 3));

        $this->assertSame(100, $result['buckets'][4]['rate']);
    }
}
