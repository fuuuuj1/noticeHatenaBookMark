<?php

namespace App\Console\Commands;

use App\Notifications\AlertDispatch;
use App\Notifications\NewsDispatch;
use App\Services\OpenAIService;
use App\Services\RSSParseService;
use App\Services\WebContentFetchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

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
        } catch (Throwable $th) {
            $this->throwError([
                'class' => get_class($this->rss_parse_service),
                'message' => 'RSSから記事のURL取得に失敗しました',
            ], $th);
            return;
        }

        // 取得したURLを元に記事本文を取得する
        try {
            $contents = $this->web_content_fetch_service->fetchContent($urls);
        } catch (Throwable $th) {
            $this->throwError([
                'class' => get_class($this->web_content_fetch_service),
                'message' => 'スクレイピングによる記事本文の取得に失敗しました',
            ], $th);
            return;
        }

        foreach ($contents as $content) {
            try {
                // openAI apiを使用して記事を要約する
                $response = $this->openai_service->fetch($content);
            } catch (Throwable $th) {
                $this->throwError([
                    'class' => get_class($this->openai_service),
                    'message' => 'openAI apiを使用して記事の要約に失敗しました',
                ], $th);
                continue;
            }

            try {
                // 時折、文字化けにより通知失敗するので、後続処理を進めるために、try-catchにて処理
                Notification::route('slack', config('services.slack.channel'))
                    ->notify(new NewsDispatch($response));
            } catch (\Throwable $th) {
                $this->throwError([
                    'class' => NewsDispatch::class,
                    'message' => '記事要約のSlack通知に失敗しました',
                ], $th);
                // TODO: Error が発生した場合は専用のcountをインクリメント
                // 最後に例外発生のcountを元にSlack通知を行う
            }
        }
        $this->info('記事の要約処理を終了');
    }

    /**
     * エラーをログに出力する & slackに通知する
     *
     * @param array $content
     * [
     *  'class' => , // エラーが発生したクラス名
     *  'message' => , // エラーが発生した際の直前の処理内容
     * ]
     * @param Throwable $th
     * @return void
     */
    private function throwError(array $content, Throwable $th): void
    {
        logger()->error($th);
        $this->error($th->getMessage());
        Notification::route('slack', config('services.slack.alert_channel'))
            ->notify(new AlertDispatch($content, $th));
    }
}
