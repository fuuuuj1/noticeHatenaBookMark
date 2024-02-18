<?php

namespace Tests\Unit\Services;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Services\OpenAIService;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

/**
 * test 実行コマンド: vendor/bin/phpunit tests/Unit/Services/OpenAIServiceTest.php
 * sail ver 実行コマンド: sail test tests/Unit/Services/OpenAIServiceTest.php
 */
class OpenAIServiceTest extends TestCase
{
    /**
     * Test OpenAI chat.
     */
    public function test_OpenAIテキスト要約成功(): void
    {
        // Create an instance of the OpenAIService
        $openai_service = new OpenAIService();

        // Mock the OpenAI chat response
        $this->createOpenAIMock();

        // Call the fetch method and assert the result
        $content = [
            'title' => 'Test title',
            'content' => 'Test content',
            'url' => 'https://example.com',
        ];
        $response = $openai_service->fetch($content);

        $expected_data = [
            'title' => 'Test title',
            'content' => 'This is a test response.',
            'url' => 'https://example.com',
            'token' => 100,
        ];

        $this->assertEquals($expected_data, $response);
    }

    public function test_OpenAIテキスト要約_パラメータエラー(): void
    {
        // Create an instance of the OpenAIService
        $openai_service = new OpenAIService();

        // Mock the OpenAI chat response
        $this->createOpenAIMock();

        // Call the fetch method and assert the result
        $content = [
            'title' => 'Test title',
            'content' => '',
            'url' => 'https://example.com',
        ];

        // expect InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);

        $openai_service->fetch($content);
    }

    private function createOpenAIMock(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'text' => 'This is a test response.',
                        'message' => [
                            'function_call' => [
                                'name' => 'text_summary',
                                'arguments' => json_encode('This is a test response.')
                            ],
                        ],
                        'finish_reason' => 'function_call',
                    ],
                ],
                'usage' => [
                    'total_tokens' => 100,
                ],
            ]),
        ]);
    }
}
