<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Google での本人確認はできたが、推理手帳にはログインさせないとき（招待がない など）に投げる。
 * メッセージはそのまま画面に出すので、利用者に向けた日本語で書く
 */
class LoginRefused extends RuntimeException {}
