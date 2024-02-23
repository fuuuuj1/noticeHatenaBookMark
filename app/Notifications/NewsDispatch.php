<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NewsDispatch extends Notification
{
    /**
     * 通知内容
     * [
     *  'title' => '記事のタイトル',
     *  'content' => ['要約1', '要約2', '要約3', '要約4'],
     *  'url' => '記事のURL',
     *  'token' => int  // 記事の要約に使用したトークン数
     * ]
     * @var array
     */
    private array $content;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $content)
    {
        // パラメータのバリデーション
        $this->validate($content);

        $this->content = $content;
    }

    public function via(): array
    {
        return ['slack'];
    }

    /**
     * 通知に使用するメッセージの検証
     *
     * @param array $content
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validate(array $content): void
    {
        // パラメータのバリデーション
        $rules = [
            'title' => 'required|string',
            'content' => 'required|array',
            'url' => 'required|url',
            'token' => 'required|integer',
        ];

        // バリデーションエラーがある場合は例外をスロー
        $validator = Validator::make($content, $rules);
        if ($validator->fails()) {
            // ログ出力は呼び出し元で行う
            // TODO: 例外発生でのSlack通知を行う

            throw new \InvalidArgumentException($validator->errors()->first());
        }
    }

    /**
     * Slack通知のメッセージを作成
     *
     * @param array $content
     *
     * @return SlackMessage
     */
    public function toSlack(): SlackMessage
    {
        return (new SlackMessage)
            ->headerBlock($this->content['title'])
            ->sectionBlock(function (SectionBlock $section) {
                // 4つの文章で構成されている要約をコードブロックで表示
                $text = '';
                foreach ($this->content['content'] as $value) {
                    $text .= '• ' . $value . "\n";
                }
                // 要約をコードブロックで表示
                $section->text($text)->markdown();
            })
            ->dividerBlock()
            ->sectionBlock(function (SectionBlock $section) {
                $section->text($this->content['url'])->markdown();
            })
            ->contextBlock(function (ContextBlock $context) {
                $context->text('token: ' . $this->content['token']);
            });
    }
}
