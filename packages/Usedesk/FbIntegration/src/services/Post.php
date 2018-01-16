<?php
namespace UseDesk\Facebook\Page;

use CompanyEmailChannel;
use Client;
use Ticket;
use TicketComment;
use TicketCommentFile;
use UseDesk\Facebook\Page\Helpers\PostHelper;

class Post
{
    const FIELD_FEED = 'feed';

    const ITEM_STATUS = 'status';
    const ITEM_POST = 'status';
    const ITEM_COMMENT = 'comment';
    const ITEM_PHOTO = 'photo';

    /**
     * @var PostHelper
     */
    private $helper;

    /**
     * Post constructor.
     */
    public function __construct()
    {
        $this->helper = new PostHelper();
    }

    /**
     * @param array $entry
     */
    public function readChangesFromFb($entry)
    {
        $pageId = $entry['id'];
        if ($usedeskChannel = $this->helper->getUsedeskChannel($pageId)) {
            $this->helper->setLocale($usedeskChannel->company_id);
            foreach ($entry['changes'] as $fbChange) {
                $this->readChangeFromFb($fbChange, $usedeskChannel);
            }
        }

    }

    /**
     * @param array $fbChange
     * @param CompanyEmailChannel $usedeskChannel
     */
    public function readChangeFromFb($fbChange, $usedeskChannel)
    {
        if ($fbChange['field'] == self::FIELD_FEED) {
            $value = $fbChange['value'];
            switch ($value['item']) {
                case self::ITEM_COMMENT:
                    $this->newComment($value, $usedeskChannel);
                    break;
                case self::ITEM_STATUS:
                case self::ITEM_POST:
                case self::ITEM_PHOTO:
                    //$this->newPost($value, $usedeskChannel);
                    break;
            }
        }
    }

    /**
     * @param array $value
     * @param CompanyEmailChannel $usedeskChannel
     */
    private function newPost($value, $usedeskChannel)
    {
        if ($this->helper->postExists($value['post_id'], $usedeskChannel->id)) return;
        $time = $this->helper->getCarbonFromFbTimestamp($value['created_time']);
        $client = $this->getClient($value['sender_id'], $usedeskChannel);
        $ticket = $this->helper->getCreatePostTicket($client, $value['post_id'], $usedeskChannel, $time, $value);
        $this->helper->createPostTicketComment($ticket, $client, $value, $time);
        $this->helper->changeTicket($ticket, $time);
    }

    /**
     * @param array $value
     * @param CompanyEmailChannel $usedeskChannel
     */
    private function newComment($value, $usedeskChannel)
    {
        if ($this->helper->commentExists($value['post_id'], $value['comment_id'])) return;
        $time = $this->helper->getCarbonFromFbTimestamp($value['created_time']);
        $client = $this->getClient($value['sender_id'], $usedeskChannel);
        $ticket = $this->helper->getCreatePostTicket($client, $value, $usedeskChannel, $time);
        $this->helper->createPostTicketComment($ticket, $client, $value, $time);
        $this->helper->changeTicket($ticket, $time);
    }

    /**
     * @param $fbSenderId
     * @param CompanyEmailChannel $usedeskChannel
     * @return Client
     */
    private function getClient($fbSenderId, CompanyEmailChannel $usedeskChannel)
    {
        if ($this->helper->isUsedeskChannel($fbSenderId, $usedeskChannel)) {
            $client = $this->helper->getCreateClientFromChannel($usedeskChannel);
        } else {
            $client = $this->helper->getCreateClientByFbId($fbSenderId, $usedeskChannel);
        }

        return $client;
    }

    /**
     * @param TicketComment $ticketComment
     * @param Ticket $ticket
     * @param CompanyEmailChannel $channel
     * @param int|null $parentTicketCommentId
     * @return mixed
     */
    public function sendCommentToFb(TicketComment $ticketComment, Ticket $ticket, CompanyEmailChannel $channel, $parentTicketCommentId = null)
    {
        return $this->helper->sendCommentToFb($ticketComment, $ticket, $channel, $parentTicketCommentId);
    }

}