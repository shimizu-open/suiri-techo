<?php

namespace App\Services\Google;

use App\Contracts\CalendarApi as CalendarApiContract;
use App\Models\CalendarConnection;
use App\Services\Google\Exceptions\GoogleApiFailed;
use App\Services\Google\Exceptions\SyncTokenExpired;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Google カレンダーの REST API を、Laravel の HTTP クライアントで直接呼ぶ係（公式の PHP ライブラリは使わない）。
 * 住所は https://www.googleapis.com/calendar/v3 の下。毎回、GoogleTokens から使える access token をもらって付ける。
 * 401 なら access token を期限切れにして（次で取り直す）、そのほかの失敗と同じく GoogleApiFailed を投げる
 */
class CalendarApi implements CalendarApiContract
{
    /** トークンの係を受け取る */
    public function __construct(private GoogleTokens $tokens) {}

    /** 専用カレンダーを作り、その ID を返す（POST /calendars。送るのは名前とタイムゾーンだけ） */
    public function insertCalendar(CalendarConnection $connection, string $name, string $timeZone): string
    {
        $response = $this->send($connection, 'post', '/calendars', ['summary' => $name, 'timeZone' => $timeZone]);
        $this->failUnless($connection, $response, 'insertCalendar');

        return (string) $response->json('id');
    }

    /** そのカレンダーがまだあるかを返す（GET /calendars/{id}。404・410 なら消されている） */
    public function hasCalendar(CalendarConnection $connection, string $calendarId): bool
    {
        $response = $this->send($connection, 'get', $this->calendarPath($calendarId));

        // 見つからない・消されたなら false。それ以外の失敗は GoogleApiFailed
        if (in_array($response->status(), [404, 410], true)) {
            return false;
        }
        $this->failUnless($connection, $response, 'hasCalendar');

        return true;
    }

    /** 専用カレンダーを消す（DELETE /calendars/{id}。404・410 ならもう消えているので何もしない） */
    public function deleteCalendar(CalendarConnection $connection, string $calendarId): void
    {
        $response = $this->send($connection, 'delete', $this->calendarPath($calendarId));
        $this->failUnless($connection, $response, 'deleteCalendar', [404, 410]);
    }

    /**
     * 予定を作る（POST /calendars/{id}/events に id つきで）。同じ id の予定がもうあれば 409 が返るので、
     * 中身を置き換える（PUT /calendars/{id}/events/{eventId}）。これで何度送っても予定は1つになる
     */
    public function upsertEvent(CalendarConnection $connection, string $calendarId, string $eventId, array $event): void
    {
        $path = $this->calendarPath($calendarId).'/events';
        $response = $this->send($connection, 'post', $path, ['id' => $eventId] + $event);

        // もう同じ id の予定があれば、作り直さずに中身を置き換える
        if ($response->status() === 409) {
            $response = $this->send($connection, 'put', $path.'/'.$eventId, $event);
        }
        $this->failUnless($connection, $response, 'upsertEvent');
    }

    /** 予定を消す（DELETE /calendars/{id}/events/{eventId}。404・410 ならもう消えているので何もしない） */
    public function deleteEvent(CalendarConnection $connection, string $calendarId, string $eventId): void
    {
        $response = $this->send($connection, 'delete', $this->calendarPath($calendarId).'/events/'.$eventId);
        $this->failUnless($connection, $response, 'deleteEvent', [404, 410]);
    }

    /**
     * 変わった予定を、ページを最後までたどって集める（GET /calendars/{id}/events?syncToken=…）。
     * 最後のページにある nextSyncToken を次回の印として返す。410 なら SyncTokenExpired
     */
    public function listChangedEvents(CalendarConnection $connection, string $calendarId, ?string $syncToken): array
    {
        $events = [];
        $pageToken = null;

        // 次のページの印（nextPageToken）がなくなるまで、1ページずつ読む
        do {
            $query = array_filter(['syncToken' => $syncToken, 'pageToken' => $pageToken, 'maxResults' => 250]);
            $response = $this->send($connection, 'get', $this->calendarPath($calendarId).'/events', $query);

            // 印が古くなっていれば、全件を取り直す合図を出す
            if ($response->status() === 410) {
                throw new SyncTokenExpired;
            }
            $this->failUnless($connection, $response, 'listChangedEvents');
            $events = [...$events, ...$response->json('items', [])];
            $pageToken = $response->json('nextPageToken');
        } while (filled($pageToken));

        return ['events' => $events, 'sync_token' => $response->json('nextSyncToken')];
    }

    /**
     * その時間の範囲の予定を開始の順に返す（GET /calendars/{id}/events?timeMin&timeMax&singleEvents=true&orderBy=startTime）。
     * 受け取る項目は題名と日時（とアプリが書いた印）だけに絞る（fields）。予定の説明・場所・参加者は受け取らない
     */
    public function listEvents(CalendarConnection $connection, string $calendarId, string $timeMin, string $timeMax): array
    {
        $response = $this->send($connection, 'get', $this->calendarPath($calendarId).'/events', [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
            'maxResults' => 250,
            'fields' => config('suiri.calendar.agenda_fields'),
        ]);
        $this->failUnless($connection, $response, 'listEvents');

        return $response->json('items', []);
    }

    /**
     * access token を付けて Google に1回問い合わせる。つながらなければ GoogleApiFailed にする
     *
     * @param  array<string, mixed>  $data  GET なら住所の後ろの ?…、それ以外なら JSON の本体
     */
    private function send(CalendarConnection $connection, string $method, string $path, array $data = []): Response
    {
        $http = $this->http($connection);

        // Google に送る。つながらない・時間切れなら、あとで試し直せるよう GoogleApiFailed にする
        try {
            return $http->{$method}(config('suiri.calendar.urls.api').$path, $data);
        } catch (ConnectionException) {
            throw new GoogleApiFailed($method.' '.$path, 0);
        }
    }

    /** access token と待ち時間を決めた HTTP の道具を返す */
    private function http(CalendarConnection $connection): PendingRequest
    {
        return Http::withToken($this->tokens->accessToken($connection))
            ->acceptJson()
            ->timeout(config('suiri.calendar.timeout_seconds'));
    }

    /**
     * 応答がうまくいっていなければ GoogleApiFailed を投げる。$ok に入れた番号（404 など）はうまくいったものとして扱う。
     * 401 なら access token を期限切れにしておく（次の問い合わせで取り直し、許可が切れていれば broken になる）
     *
     * @param  list<int>  $ok
     */
    private function failUnless(CalendarConnection $connection, Response $response, string $operation, array $ok = []): void
    {
        // うまくいった、または気にしなくてよい番号なら、何もしない
        if ($response->successful() || in_array($response->status(), $ok, true)) {
            return;
        }

        // 鍵が通らなかったなら、次で取り直すよう期限切れにする
        if ($response->status() === 401) {
            $this->tokens->expire($connection);
        }

        throw new GoogleApiFailed($operation, $response->status());
    }

    /** カレンダーの住所を返す（ID に @ などが入るので、住所に使える形にする） */
    private function calendarPath(string $calendarId): string
    {
        return '/calendars/'.rawurlencode($calendarId);
    }
}
