<?php

namespace Tests\Unit\Notifications;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Notifications\NewsListDispatch;
use Illuminate\Support\Facades\Notification;

/**
 * test 実行コマンド: vendor/bin/phpunit tests/Unit/Notifications/NewsListDispatchTest.php
 * sail ver 実行コマンド: sail test tests/Unit/Notifications/NewsListDispatchTest.php
 */
class NewsListDispatchTest extends TestCase
{
    public function test_オンデマンドslack通知(): void
    {
        Notification::fake();

        $content = $this->setContent();

        // Call the notify method and assert the result
        Notification::route('slack', config('services.slack.channel'))->notify(new NewsListDispatch($content));

        Notification::assertSentOnDemand(NewsListDispatch::class);
    }

    /**
     * 通知先検証 slack通知を実施しているクラスでのチャンネル指定の方法を改修したタイミングで整備する
     */
    public function test_通知先検証(): void
    {
        Notification::fake();

        $content = $this->setContent();

        // Call the notify method and assert the result
        Notification::route('slack', config('services.slack.channel'))->notify(new NewsListDispatch($content));

        Notification::assertSentOnDemand(
            NewsListDispatch::class,
            function (NewsListDispatch $notification, array $channels, $notifiable) {
                return $notifiable->routes['slack'] === config('services.slack.channel');
            }
        );
    }

    public function test_パラメータ検証OK(): void
    {
        Notification::fake();

        $content = $this->setContent();

        // Call the notify method and assert the result
        Notification::route('slack', config('services.slack.channel'))->notify(new NewsListDispatch($content));

        // expect no exception
        $this->expectNotToPerformAssertions();
    }

    public function test_パラメータ検証NG(): void
    {
        Notification::fake();

        // 記事情報が存在しない
        $content = [
            'site' => 'test site',
        ];

        // expect InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);

        // Call the notify method and assert the result
        Notification::route('slack', config('services.slack.channel'))->notify(new NewsListDispatch($content));
    }

    private function setContent(): array
    {
        return [
            'site' => 'test site',
            'articles' => [
                [
                    'title' => 'Test title',
                    'url' => 'https://example.com',
                ],
                [
                    'title' => 'Test title',
                    'url' => 'https://example.com',
                ]
            ],
        ];
    }
}
