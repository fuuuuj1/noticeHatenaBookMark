<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

/**
 * OpenAIとの連携を行う
 */
class OpenAIService
{
    private string $model = 'gpt-3.5-turbo-16k';

    /**
     * OpenAIのAPIを叩いて、GPT-3.5のモデルでの応答を取得する
     *
     * @array $content
     * @return array
     * @throws \Throwable
     */
    public static function fetch(array $content): array
    {
        if (empty($content)) {
            throw new \InvalidArgumentException('Content is empty.');
        }

        try {
            $result = OpenAI::chat()->create([
                'model' => self::$model,
                'temperature' => 0.0,
                'json_format' => ['type' => 'json_object'],
                'messages' => self::setMessage($content),
                'functions' => self::setFunction(),
            ]);
            return self::parseResponse($result, $content);
        } catch (\Throwable $th) {
            // TODO: slackにエラーを通知する
            logger()->error($th);
            throw $th;
        }
    }

    /**
     * openAIからのレスポンスを整形する
     *
     * @param CreateResponse $res
     * @param array $content
     * @return array
     */
    private function parseResponse(CreateResponse $res, array $content): array
    {
        $result = $res->choices[0];
        return [
            'title' => $content['title'],
            'content' => json_decode($result->message->functionCall->arguments, true),
            'url' => $content['url'],
            'token' => $res->usage->totalTokens,
        ];
    }

    /**
     * openAIに送るメッセージをセットする
     *
     * @param array $content
     * @return array
     */
    private function setMessage(array $content): array
    {
        $title = $content['title'];
        $text = $content['content'];

        $message = [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant.'
            ],
            [
                'role' => 'assistant',
                'content' => "[Title] $title \n### [Text] $text"
            ],
            [
                'role' => 'user',
                'content' => 'この記事の内容について、技術的な視点で要約をしてください。'
            ]
        ];
        return $message;
    }

    /**
     * openAIに送る関数をセットする
     *
     * @return array
     */
    private function setFunction(): array
    {
        $functions = [
            [
                'name' => 'generate_json',
                'description' => '記事の要約を生成する',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'summary' => [
                            'type' => 'object',
                            'description' => '4つの文章で構成される要約です',
                            'properties' => [
                                'summary1' => [
                                    'type' => 'string',
                                    'description' => '箇条書きで構成された要約文の1文目です。'
                                ],
                                'summary2' => [
                                    'type' => 'string',
                                    'description' => '箇条書きで構成された要約文の2文目です。'
                                ],
                                'summary3' => [
                                    'type' => 'string',
                                    'description' => '箇条書きで構成された要約文の3文目です。'
                                ],
                                'summary4' => [
                                    'type' => 'string',
                                    'description' => '箇条書きで構成された要約文の4文目です。'
                                ]
                            ]
                        ]
                    ],
                    'required' => ['summary']
                ]
            ]
        ];
        return $functions;
    }
}

