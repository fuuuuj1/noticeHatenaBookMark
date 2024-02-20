<?php

namespace Tests\Unit\Notifications;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Notifications\NewsDispatch;
use Illuminate\Support\Facades\Notification;

/**
 * test 実行コマンド: vendor/bin/phpunit tests/Unit/Notifications/NewsDispatchTest.php
 * sail ver 実行コマンド: sail test tests/Unit/Notifications/NewsDispatchTest.php
 */
class NewsDispatchTest extends TestCase
{
    public function test_オンデマンドslack通知(): void
    {
        Notification::fake();

        $content = $this->setContent();

        // Call the notify method and assert the result
        Notification::route('slack', config('services.slack.channel'))->notify(new NewsDispatch($content));

        Notification::assertSentOnDemand(NewsDispatch::class);
    }

    /**
     * 通知先検証 slack通知を実施しているクラスでのチャンネル指定の方法を改修したタイミングで整備する
     */
    public function test_通知先検証(): void
    {
        Notification::fake();

        $content = $this->setContent();

        // Call the notify method and assert the result
        Notification::route('slack', config('services.slack.channel'))->notify(new NewsDispatch($content));

        Notification::assertSentOnDemand(
            NewsDispatch::class,
            function (NewsDispatch $notification, array $channels, $notifiable) {
                return $notifiable->routes['slack'] === config('services.slack.channel');
            }
        );
    }

    public function test_パラメータ検証OK(): void
    {
        Notification::fake();

        $content = $this->setContent();

        // Call the notify method and assert the result
        Notification::route('slack', config('services.slack.channel'))->notify(new NewsDispatch($content));

        // expect no exception
        $this->expectNotToPerformAssertions();
    }

    public function test_パラメータ検証NG(): void
    {
        Notification::fake();

        // 要約内容が存在しない
        $content = [
            'title' => 'Test title',
            'url' => 'https://example.com',
            'token' => '100',
        ];

        // expect InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);

        // Call the notify method and assert the result
        Notification::route('slack', config('services.slack.channel'))->notify(new NewsDispatch($content));
    }

    private function setContent(): array
    {
        return [
            'title' => 'Test title',
            'content' => [
                'Test content 1',
                'Test content 2',
                'Test content 3',
                'Test content 4',
            ],
            'url' => 'https://example.com',
            'token' => 100,
        ];
    }
}
