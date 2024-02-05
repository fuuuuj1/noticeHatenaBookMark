<?php

namespace App\Console\Commands;

use App\Services\RSSParseService;
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
    protected $description = 'はてなブックマークのテクノロジーカテゴリのホットエントリーを取得。
        5つまでの記事を取得。
        chatGPT apiを使用して記事を要約。
        指定したSlackに通知する';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // はてなブックマークのテクノロジーカテゴリのホットエントリーを取得する
        // こちらの処理は専用のServiceクラスに切り出す
        // 5つまでの記事URLを取得する
        $parse_service = new RSSParseService();
        $hot_entries = $parse_service->fetchEntries(5);

        // ここからloopでの処理を予定

        // 取得したリンク先の記事本文を取得するクラスを呼び出す

        // chatGPT apiを使用して記事を要約する

        // Slackに通知する
    }
}
