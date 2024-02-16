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
    public function test_fetchContent_withValidUrls_returnsContentArray(): void
    {
        // Arrange
        $urls = [
            'https://example.com/page1',
            'https://example.com/page2',
        ];

        $expectedResponse = [
            [
                'url' => 'https://example.com/page1',
                'title' => 'Page 1 title',
                'content' => 'Page 1 content',
            ],
            [
                'url' => 'https://example.com/page2',
                'title' => 'Page 2 title',
                'content' => 'Page 2 content',
            ]
        ];

        Http::fake([
            config('services.lambda.endpoint') => Http::response($expectedResponse),
        ]);

        $service = new WebContentFetchService();

        // Act
        $response = $service->fetchContent($urls);

        // Assert
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * URLが空の場合は例外を投げる
     *
     * @return void
     */
    public function test_fetchContent_withEmptyUrls_throwsException(): void
    {
        // Arrange
        $urls = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URLs are empty.');

        $service = new WebContentFetchService();

        // Act
        $service->fetchContent($urls);
    }

    /**
     * apiをcallして、失敗した場合は例外を投げる
     *
     * @return void
     */
    public function test_fetchContent_withFailedResponse_throwsException(): void
    {
        // Arrange
        $urls = [
            'https://example.com/page1',
            'https://example.com/page2',
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
        $service->fetchContent($urls);
    }
}
