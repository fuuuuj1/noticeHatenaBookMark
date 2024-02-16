<?php

namespace App\Console\Commands;

use App\Services\RSSParseService;
use App\Services\WebContentFetchService;
use Illuminate\Console\Command;

class ArticleSummaryNotice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:article-summary-notice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '指定したサイトRSSの記事リンクを取得。
        リンク先の記事を5つまで取得。
        chatGPT apiを使用して記事を要約。
        指定したSlackに通知する';

    private RSSParseService $rss_parse_service;

    private WebContentFetchService $web_content_fetch_service;

    /**
     * DIしたServiceクラスをプロパティに格納する
     */
    public function __construct()
    {
        parent::__construct();
        $this->rss_parse_service = new RSSParseService(config('services.rss.mentas'));
        $this->web_content_fetch_service = new WebContentFetchService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $urls = $this->rss_parse_service->fetchEntries(5);
        } catch (\Throwable $th) {
            // 通知に関してはServiceクラス内で行う
            $this->error($th->getMessage());
            return;
        }

        // 取得したURLを元に記事本文を取得する
        try {
            $contents = $this->web_content_fetch_service->fetchContent($urls);
        } catch (\Throwable $th) {
            // 通知に関してはServiceクラス内で行う
            $this->error($th->getMessage());
            return;
        }

        // ここからloopでの処理を予定
        foreach ($contents as $content) {
            // chatGPT apiを使用して記事を要約する

            // Slackに通知する
        }
    }
}
