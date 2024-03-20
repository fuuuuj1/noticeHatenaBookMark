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
     * 特定のサイトであるならば、取得しないようにする
     * github, speakerdeck
     * 認証が必要なサイトや、文字列を取得することが困難なサイトは取得しない
     * TODO: 他にも取得しないサイトがあれば追加できるようにコード管理をしない体制を整える
     * dynamodbに取得しないサイトを保存しておくとか？
     *
     * @param string $link
     * @return bool
     */
    private function checkLinkString(string $link): bool
    {
        if (strpos($link, 'github.com') !== false) {
            return false;
        }

        if (strpos($link, 'speakerdeck.com') !== false) {
            return false;
        }

        if (strpos($link, 'togetter.com') !== false) {
            return false;
        }

        return true;
    }

    /**
     * RSSをパースしてURLと記事のタイトルを格納した配列を返す
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

            // Memo コメントにあるように、効率的な方法があるのかを検討する
            // json_decode(json_encode($rss))を使用してオブジェクトを配列に変換している部分については、より効率的な方法を検討する価値があります。
            // 例えば、SimpleXMLElementオブジェクトを直接操作して必要なデータを抽出する方法などです。
            $rss = new SimpleXMLElement($response->body());

            // 一度jsonに変換してから配列に変換する
            $rss = json_decode(json_encode($rss));

            // itemがchannelの中にある場合とない場合があるので、それぞれの場合で処理を分ける
            $items = $rss->channel->item ?? $rss->item;

            // 取得したRSSの中からURLとタイトルを取得する
            $articles = [];
            foreach ($items as $item) {
                if ($this->checkLinkString($item->link)) {
                    $articles[] = [
                        'title' => (string) $item->title,
                        'url' => (string) $item->link,
                    ];
                }
                if (count($articles) >= $limit) {
                    break;
                }
            }
            return $articles;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
