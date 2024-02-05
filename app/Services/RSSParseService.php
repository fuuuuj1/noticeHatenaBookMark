<?php

namespace App\Services;

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
        $this->rss_url = $rss_url;
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
            $rss = new SimpleXMLElement($response->body());

            // RSSのitem数とlimitの小さい方を取得する limit数がRSSのitem数より多い場合にエラーになるのを防ぐ
            $count = min($limit, count($rss->item));

            $top_entries = [];
            for ($i = 0; $i < $count; $i++) {
                $top_entries[] = [
                    'title' => (string) $rss->item[$i]->title,
                    'link' => (string) $rss->item[$i]->link,
                ];
            }
            return $top_entries;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
