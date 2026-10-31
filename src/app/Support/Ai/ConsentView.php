<?php

namespace App\Support\Ai;

use Illuminate\Http\Request;
use Laravel\Passport\Passport;

/**
 * 同意の画面（views/oauth/authorize）に渡す値を、Passport が渡す値から詰め替える係（画面で計算しないため）。
 * Passport の同意の受付（AuthorizationController）が、許可を求めている AI と、ログイン中の人と、合言葉（authToken）を渡してくる。
 * ここで「AI の名前（自己申告）・戻る先の住所・記録が渡る会社と国・送り返す隠し欄」にそろえる
 */
class ConsentView
{
    /**
     * 画面に渡す値を返す。$parameters は Passport の値（request・authToken など）。
     * AI の名前は登録の番号（client_id）から引き直す。戻る先は Passport が登録と照らし合わせて確かめた redirect_uri
     */
    public static function from(array $parameters): array
    {
        /** @var Request $request */
        $request = $parameters['request'];
        $appId = (string) $request->query('client_id');
        $host = (string) parse_url((string) $request->query('redirect_uri'), PHP_URL_HOST);
        $companies = config('ai_connect.consent.companies');

        return [
            'appName' => Passport::client()->newQuery()->whereKey($appId)->value('name') ?? $host,
            'redirectHost' => $host,
            'company' => $companies[$host] ?? config('ai_connect.consent.unknown_company'),
            'authToken' => $parameters['authToken'],
            'state' => (string) $request->query('state'),
            'appId' => $appId,
        ];
    }
}
