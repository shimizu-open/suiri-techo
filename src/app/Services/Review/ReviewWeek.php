<?php

namespace App\Services\Review;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * ふりかえりの「週」（月曜日の 0 時から、次の月曜日の 0 時の手前まで）。
 * 住所の '2026-10-26' の形との行き来・前の週と次の週・画面に出す期間の言葉をまとめる。未来の週は作らない
 */
final class ReviewWeek
{
    /** 週の始まり（月曜日の 0 時）を受け取って作る。外からは current()・fromParam() で作る */
    private function __construct(private readonly CarbonImmutable $start) {}

    /** 今週（今日を含む週）を返す */
    public static function current(): self
    {
        return new self(CarbonImmutable::today()->startOfWeek(CarbonInterface::MONDAY));
    }

    /**
     * 住所の 'Y-m-d' から、その日を含む週を返す。省略（null）なら今週。
     * 日付として正しくない・未来の週のときは null を返す（受付は 404 にする）
     */
    public static function fromParam(?string $param): ?self
    {
        // 省略されたら今週
        if ($param === null) {
            return self::current();
        }
        // 'Y-m-d' の形でない、または 2月31日のようなない日付なら、週を作らない
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $param, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        $week = new self(CarbonImmutable::parse($param)->startOfWeek(CarbonInterface::MONDAY));

        return $week->start->greaterThan(self::current()->start) ? null : $week;
    }

    /** 週の始まり（月曜日の 0 時）を返す */
    public function start(): CarbonImmutable
    {
        return $this->start;
    }

    /** 週の終わり（次の月曜日の 0 時。この時刻は含まない）を返す */
    public function end(): CarbonImmutable
    {
        return $this->start->addWeek();
    }

    /** 住所に使う形（月曜日の 'Y-m-d'）を返す。weekly_reflections.week_start と同じ形 */
    public function param(): string
    {
        return $this->start->toDateString();
    }

    /** 前の週を返す */
    public function previous(): self
    {
        return new self($this->start->subWeek());
    }

    /** 次の週を返す。今週なら次の週はまだないので null */
    public function next(): ?self
    {
        return $this->isCurrent() ? null : new self($this->start->addWeek());
    }

    /** 今週かを返す */
    public function isCurrent(): bool
    {
        return $this->start->equalTo(self::current()->start);
    }

    /** 画面に出す期間の言葉を返す（例「10月26日〜11月1日」。今年でなければ年も付ける） */
    public function label(): string
    {
        $last = $this->end()->subDay();
        $format = $this->start->year === CarbonImmutable::today()->year ? 'n月j日' : 'Y年n月j日';

        return $this->start->format($format).'〜'.$last->format($last->year === $this->start->year ? 'n月j日' : 'Y年n月j日');
    }

    /** 問い合わせで「この週の中」に絞る：列の値が週の始まり以上、次の月曜日の 0 時より前 */
    public function scope($query, string $column)
    {
        return $query->where($column, '>=', $this->start)->where($column, '<', $this->end());
    }
}
