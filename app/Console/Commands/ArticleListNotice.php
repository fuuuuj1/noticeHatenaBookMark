<?php

namespace App\Console\Commands;

use App\Notifications\AlertDispatch;
use App\Notifications\NewsListDispatch;
use App\Services\RSSParseService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ArticleListNotice extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:article-list-notice {site*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '指定したサイトのRSS記事リストを取得し、Slackに通知する';

    private array $sites = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the missing arguments for the command.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'site' => 'サイト名を指定してください。複数のサイトを指定する場合はカンマ区切りで指定してください。',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // コマンドの引数を取得 複数のサイトを指定可能
        $this->sites = $this->argument('site');

        // コマンド引数のバリデーション
        if (count($this->sites) === 0) {
            $this->error('引数にサイト名を指定してください');
            return;
        }

        // 指定したサイトのRSSを取得 & 通知
        foreach ($this->sites as $site) {
            $rss_url = $this->mapSiteNameToRSSUrl($site);
            try {
                $rss_parse_service = new RSSParseService($rss_url);
                $articles = $rss_parse_service->fetchEntries(10);
            } catch (\Throwable $th) {
                $this->throwError([
                    'class' => get_class($rss_parse_service),
                    'message' => 'RSSから記事のURL取得に失敗しました',
                ], $th);
                return;
            }


            // Slackに通知
            // 取得した記事のタイトルとlinkを通知
            $content = [
                'site' => $site,
                'articles' => $articles,
            ];

            try {
                Notification::route('slack', config('services.slack.notifications.news_channel'))
                    ->notify(new NewsListDispatch($content));
            } catch (\Throwable $th) {
                $this->throwError([
                    'class' => NewsListDispatch::class,
                    'message' => '記事リストお知らせSlack通知に失敗しました',
                ], $th);
                return;
            }
        }
        $this->info('記事リストの取得と通知が完了しました');
    }

    /**
     * サイト名をRSSのURLにマッピング
     *
     * @param string $site
     * @return string
     * @throws Exception
     */
    private function mapSiteNameToRSSUrl($site): string
    {
        $rss_url = '';
        switch ($site) {
            case 'qiita':
                $rss_url = config('services.rss.qiita');
                break;
            case 'zenn':
                $rss_url = config('services.rss.zenn');
                break;
            case 'hatena_hotentry':
                $rss_url = config('services.rss.hatena_hotentry');
                break;
            default:
                $this->error('指定したサイトは存在しません');
                throw new Exception('指定したサイトは存在しません');
        }
        return $rss_url;
    }

    /**
     * エラーをログに出力する & slackに通知する
     * TODO: 共通処理として切り出す
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
        Notification::route('slack', config('services.slack.notifications.alert_channel'))
            ->notify(new AlertDispatch($content, $th));
    }
}
