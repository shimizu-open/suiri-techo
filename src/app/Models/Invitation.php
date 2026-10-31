<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 招待（新しく登録してよいメールアドレス）。運営者が php artisan invite:add で足す。
 * 利用者とはメールアドレスで結ぶ（外部キーは持たない）。メールアドレスはいつも小文字にそろえて入れる
 */
#[Fillable(['email', 'note', 'used_at'])]
class Invitation extends Model
{
    use HasFactory;

    /** 列の型を決める */
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    /** メールアドレスの前後の空白を取り、小文字にそろえて返す（入れるときも探すときもこれを通す） */
    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** このメールアドレスが招待されているか（その招待を返す。なければ null） */
    public static function findFor(string $email): ?self
    {
        return self::where('email', self::normalize($email))->first();
    }

    /** email を入れるとき、自動で小文字にそろえる */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => self::normalize($value));
    }

    /** 登録に使われたかを返す */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
