<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

/**
 * OpenAIとの連携を行う
 */
class OpenAIService
{
    private string $use_model = 'gpt-3.5-turbo-0125';

    /**
     * OpenAIのAPIを叩いて、GPT-3.5のモデルでの応答を取得する
     *
     * @array $content
     * @return array
     * @throws \Throwable
     */
    public function fetch(array $content): array
    {
        $this->validate($content);

        try {
            $result = OpenAI::chat()->create([
                'model' => $this->use_model,
                'temperature' => 0.0,
                'messages' => $this->setMessage($content),
                'functions' => $this->setFunction(),
            ]);
            return $this->parseResponse($result, $content);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * openAIに送るメッセージのバリデーション
     *
     * @param array $content
     * [
     *  'title' => '記事のタイトル',
     *  'content' => '記事の内容',
     *  'url' => '記事のURL'
     * ]
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validate(array $content): void
    {
        $rules = [
            'title' => 'required|string',
            'content' => 'required|string',
            'url' => 'required|url',
        ];
        $validator = validator($content, $rules);
        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
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
        $texts = [];

        // 要約した内容を配列に変換する
        $summaries = json_decode($result->message->functionCall->arguments, true);
        foreach ($summaries['summary'] as $summary) {
            $texts[] = $summary;
        }

        return [
            'title' => $content['title'],
            'content' => $texts,
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
        // 配列をjson形式に変換する
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

