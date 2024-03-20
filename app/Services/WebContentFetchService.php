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
     * @param array $articles
     * @return array
     */
    public function fetchContent(array $articles): array
    {
        // URLのみ抜粋
        $urls = array_column($articles, 'url');

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
                throw new \RuntimeException('Failed to fetch content.');
            }

            // 取得した本文とurlで構成されるjsonを配列に変換
            $contents = $response->json();

            foreach ($articles as $key => $article) {
                // lambdaで返される本文とurlの配列の順番は最初に渡したurlの順番と同じと思うが念のためarray_searchを使用
                // contents の url と article の url が一致するものを取得
                $content_key = array_search($article['url'], array_column($contents, 'url'));
                if ($content_key !== false) {
                    $articles[$key]['content'] = $contents[$content_key]['content'];
                }
            }
            return $articles;

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
