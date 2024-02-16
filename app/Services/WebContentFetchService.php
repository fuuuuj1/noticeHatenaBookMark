<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * 指定したサイトの本文を取得する
 */
class WebContentFetchService
{
    /**
     * 指定したURLの本文を取得する
     *
     * @param array $urls
     * @return array
     */
    public function fetchContent(array $urls): array
    {
        // 万が一URLが空の場合は例外を投げる
        if (empty($urls)) {
            throw new \InvalidArgumentException('URLs are empty.');
        }

        // サイト本文を取得するapiを叩く
        // headerにAuthorizationをつける
        try {
            $response = Http::withHeaders([
                'Authorization' => config('services.lambda.authorization'),
            ])->post(config('services.lambda.endpoint'), [
                'urls' => $urls,
            ]);

            if ($response->failed()) {
                // なぜ失敗したのかをログに残す
                logger()->error($response->body());
                // TODO: slackにエラーを通知する
                throw new \RuntimeException('Failed to fetch content.');
            }

            // json形式のレスポンスを配列に変換
            return $response->json();

        } catch (\Throwable $th) {
            logger()->error($th);
            // TODO: slackにエラーを通知する
            throw $th;
        }
    }
}
