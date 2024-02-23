<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Throwable;

class AlertDispatch extends Notification
{
    /**
     * @var array<string, mixed>
     */
    private array $content;

    /**
     * Create a new notification instance.
     *
     * @param array $content
     * [
     *  'class' => '例外発生クラス名',
     *  'message' => '処理内容'
     * ]
     * @param Throwable $th
     */
    public function __construct(array $content, Throwable $th)
    {
        $this->content = $this->createMessage($content, $th);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    /**
     * 通知に使用するメッセージの作成
     *
     * @param array $content
     * @return array
     */
    private function createMessage(array $content, Throwable $th): array
    {
        return [
            'title' => $content['class'] . 'でエラーが発生しました',
            'error' => $content['message'],
            'message' => '【詳細】' . PHP_EOL . $th->getMessage() . PHP_EOL . $th->getTraceAsString(),
        ];
    }

    /**
     * Slackへの通知
     *
     * @return SlackMessage
     */
    public function toSlack(): SlackMessage
    {
        return (new SlackMessage)
            ->headerBlock($this->content['title'])
            ->sectionBlock(function (SectionBlock $section) {
                $section->text($this->content['error'])
                    ->markdown();
            })
            ->dividerBlock()
            ->contextBlock(function (ContextBlock $context) {
                $context->text($this->content['message'])
                    ->markdown();
            });
    }
}
