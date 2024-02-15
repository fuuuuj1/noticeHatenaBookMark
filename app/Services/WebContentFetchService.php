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
    public static function fetchContent(array $urls): array
    {
        // サイト本文を取得するapiを叩く
        // headerにAuthorizationをつける
        try {
            $response = Http::withHeaders([
                'Authorization' => config('services.lambda.authorization'),
            ])->post(config('services.lambda.endpoint'), [
                'urls' => $urls,
            ]);

            if ($response->failed()) {
                // なぜ失敗したのかを例外に含める
                throw new \RuntimeException('Failed to fetch content. ' . $response->body());
            }

            // json形式のレスポンスを配列に変換
            return $response->json();

        } catch (\Throwable $th) {
            logger()->error($th);
            throw $th;
        }
    }
}

