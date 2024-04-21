<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\Validator;

class NewsListDispatch extends Notification
{
    /**
     * 通知内容
     * [
     *  'site' => 'サイト名',
     *  'articles' => [
     *      [
     *          'title' => '記事のタイトル',
     *          'url' => '記事のURL',
     *      ],
     *      [
     *          'title' => '記事のタイトル',
     *          'url' => '記事のURL',
     *      ],
     *      ... (通常10件まで)
     *  ]
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

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
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
            'site' => 'required|string',
            'articles' => 'required|array',
            'articles.*.title' => 'required|string',
            'articles.*.url' => 'required|url',
        ];

        // バリデーションエラーがある場合は例外をスロー
        $validator = Validator::make($content, $rules);
        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }
    }

    /**
     * Slack通知の通知タイトルを作成
     *
     * @param string $site
     * @param int $hour
     * @return string
     */
    private function getNoticeTitle(string $site, int $hour): string
    {
        // サイト名、通知の時間帯を取得
        return $hour . '時の' . $site . 'の記事ですよ';
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
        $hour = (int) date('H');

        return (new SlackMessage)
            ->headerBlock($this->getNoticeTitle($this->content['site'], $hour))
            ->sectionBlock(function (SectionBlock $section) {
                $text = '';
                foreach ($this->content['articles'] as $value) {
                    $text .= $value['title'] . "\n";
                    $text .= $value['url'] . "\n\n";
                }
                $section->text($text)->markdown();
            });
    }
}
