<?php
namespace UseDesk\Facebook\Page;

use Carbon\Carbon;
use UseDesk\Facebook\Page\Helpers\MessengerHelper;
use UseDesk\Facebook\Page\PageHelper;
use Client;
use CompanyEmailChannel;

class Message
{
    /**
     * @var MessengerHelper
     */
    private $helper;


    public function __construct()
    {
        $this->helper = new MessengerHelper();
    }

    public function readMessagesFromFb($entry)
    {
        foreach ($entry['messaging'] as $fbMessage) {
            $this->readMessageFromFb($fbMessage);
        }
    }

    /**
     * @param array $fbMessage
     */
    public function readMessageFromFb($fbMessage)
    {
        $time = $this->helper->getCarbonFromFbTimestamp($fbMessage['timestamp']);
        $sender = $fbMessage['sender'];
        $recipient = $fbMessage['recipient'];
        $message = $fbMessage['message'];
        if (!$recipient || !$recipient['id']) return;
        if ($usedeskChannel = $this->helper->getUsedeskChannel($recipient['id'])) {
            $this->helper->setLocale($usedeskChannel->company_id);
            $client = $this->helper->getCreateMessengerClient($sender['id'], $usedeskChannel);
            $this->addToTicket($client, $usedeskChannel, $message, $time);
        }
    }

    /**
     * @param Client $client
     * @param CompanyEmailChannel $usedeskChannel
     * @param array $message
     * @param Carbon $time
     */
    public function addToTicket($client, $usedeskChannel, $message, $time)
    {
        $ticket = $this->helper->findMessengerTicket($client, $usedeskChannel);
        if (!$ticket) {
            $subject = $this->helper->getTicketSubject($message);
            $ticket = $this->helper->createTicket($client->id, $client->messenger_user_id, $usedeskChannel, $time, $subject);
        }
        $this->helper->createMessengerTicketComment($ticket, $client, $message, $time);
        $this->helper->changeTicket($ticket, $time);
    }

}