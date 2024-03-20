<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Services\WebContentFetchService;
use Illuminate\Support\Facades\Http;

/**
 * test 実行コマンド: vendor/bin/phpunit tests/Unit/Services/WebContentFetchServiceTest.php
 * sail ver 実行コマンド: sail test tests/Unit/Services/WebContentFetchServiceTest.php
 */
class WebContentFetchServiceTest extends TestCase
{
    /**
     * apiをcallして、正しい形式のレスポンスが返ってくるかをテストする
     *
     * @return void
     */
    public function test_記事の本文取得成功(): void
    {
        // Arrange
        $articles = [
            [
                'url' => 'https://example.com/page1',
                'title' => 'Page 1',
            ],
            [
                'url' => 'https://example.com/page2',
                'title' => 'Page 2',
            ]
        ];

        $expectedResponse = [
            [
                'url' => 'https://example.com/page1',
                'title' => 'Page 1',
                'content' => 'Page 1 content',
            ],
            [
                'url' => 'https://example.com/page2',
                'title' => 'Page 2',
                'content' => 'Page 2 content',
            ]
        ];

        Http::fake([
            config('services.lambda.endpoint') => Http::response($expectedResponse),
        ]);

        $service = new WebContentFetchService();

        // Act
        $response = $service->fetchContent($articles);

        // Assert
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * URLが空の場合は例外を投げる
     *
     * @return void
     */
    public function test_記事の本文取得_パラメータ不正による例外発生(): void
    {
        // Arrange
        $articles = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URLs are empty.');

        $service = new WebContentFetchService();

        // Act
        $service->fetchContent($articles);
    }

    /**
     * apiをcallして、失敗した場合は例外を投げる
     *
     * @return void
     */
    public function test_記事の本文取得_通信失敗による例外発生(): void
    {
        // Arrange
        $articles = [
            [
                'url' => 'https://example.com/page1',
                'title' => 'Page 1',
            ],
            [
                'url' => 'https://example.com/page2',
                'title' => 'Page 2',
            ]
        ];

        $expectedResponse = 'Failed to fetch content. Error message';

        Http::fake([
            config('services.lambda.endpoint') => Http::response($expectedResponse, 500),
        ]);

        $this->expectException(\RuntimeException::class);
        // 場合によるので、エラーメッセージの検証は省略
        // $this->expectExceptionMessage($expectedResponse);

        $service = new WebContentFetchService();

        // Act
        $service->fetchContent($articles);
    }
}
