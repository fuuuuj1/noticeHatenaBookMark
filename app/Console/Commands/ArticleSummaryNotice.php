<?php

namespace App\Console\Commands;

use App\Notifications\NewsDispatch;
use App\Services\OpenAIService;
use App\Services\RSSParseService;
use App\Services\WebContentFetchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

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

    private OpenAIService $openai_service;

    /**
     * DIしたServiceクラスをプロパティに格納する
     */
    public function __construct()
    {
        parent::__construct();
        $this->rss_parse_service = new RSSParseService(config('services.rss.mentas'));
        $this->web_content_fetch_service = new WebContentFetchService();
        $this->openai_service = new OpenAIService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('記事の要約処理を開始');
        try {
            $urls = $this->rss_parse_service->fetchEntries();
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

        foreach ($contents as $content) {
            try {
                // chatGPT apiを使用して記事を要約する
                $response = $this->openai_service->fetch($content);
                // 時折、文字化けにより通知失敗するので、後続処理を進めるために、try-catchにて処理
                Notification::route('slack', config('services.slack.channel'))
                    ->notify(new NewsDispatch($response));
            } catch (\Throwable $th) {
                $this->error($th->getMessage());
                // TODO: Error が発生した場合は専用のcountをインクリメント
                // 最後に例外発生のcountを元にSlack通知を行う
                continue;
            }
        }
        $this->info('記事の要約処理を終了');
    }
}
