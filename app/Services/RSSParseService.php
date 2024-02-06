<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class RSSParseService
{
    private $rss_url;

    /**
     * デフォルトははてなブックマークのITカテゴリのRSS
     *
     * @param string $rss_url
     */
    public function __construct($rss_url = 'https://b.hatena.ne.jp/hotentry/it.rss')
    {
        $this->checkUrl($rss_url);
        $this->rss_url = $rss_url;
    }

    /**
     * URLが正しい形式かどうかをチェックする
     *
     * @param string $url
     * @return void
     */
    private function checkUrl(string $rss_url): void
    {
        if (filter_var($rss_url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Invalid RSS URL provided');
        }
    }

    /**
     * 取得したRSSが正しい形式かどうかをチェックする
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateRss(Response $response): void
    {
        try {
            new SimpleXMLElement($response->body());
        } catch (\Throwable $th) {
            throw new \InvalidArgumentException('Invalid RSS feed.');
        }

        if (count(libxml_get_errors()) > 0) {
            throw new \InvalidArgumentException('Invalid RSS feed.');
        }

        libxml_clear_errors();
    }

    /**
     * RSSをパースしてtitleとlinkを格納した配列を返す
     *
     * @param int $limit
     * @return array
     * @throws \Throwable
     */
    public function fetchEntries(int $limit = 5): array
    {
        try {
            $response = Http::get($this->rss_url);

            // レスポンスが失敗した場合は例外を投げる
            if ($response->failed()) {
                throw new \InvalidArgumentException('Failed to fetch RSS URL.');
            }

            // レスポンスが成功した場合はRSSが正しい形式かどうかをチェックする
            $this->validateRss($response);

            $rss = new SimpleXMLElement($response->body());

            // 一度jsonに変換してから配列に変換する
            $rss = json_decode(json_encode($rss));

            $items = $rss->item;
            // $itemsをlimit数の数だけ取得
            $items = array_slice($items, 0, $limit);

            $hot_entries = array_map(function ($item) {
                return [
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                ];
            }, $items);

            return $hot_entries;
        } catch (\Throwable $th) {
            // 例外をキャッチしてログに出力する
            logger()->error($th);
            // TODO: slackにエラーを通知する
            throw $th;
        }
    }
}
