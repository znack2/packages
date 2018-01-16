<?php
namespace UseDesk\Facebook\Page\Helpers;

use Client;
use Ticket;
use TicketComment;
use TicketCommentFile;
use Carbon\Carbon;
use DB;
use Log;

class MessengerHelper extends PageHelper
{

    /**
     * @param $psid
     * @param $usedeskChannel
     * @return Client|null
     */
    public function getCreateMessengerClient($psid, $usedeskChannel)
    {
        $client = $this->getMessengerClient($psid, $usedeskChannel);
        if (!$client) {
            $client = $this->createMessengerClient($psid, $usedeskChannel);
        }

        return $client;
    }

    /**
     * @param $psid
     * @param $usedeskChannel
     * @return Client|null
     */
    public function getMessengerClient($psid, $usedeskChannel)
    {
        return Client::select('clients.id', 'client_fb_messenger.messenger_user_id')
            ->join('client_fb_messenger', 'client_fb_messenger.client_id', '=', 'clients.id')
            ->where('client_fb_messenger.messenger_user_id', $psid)
            ->where('client_fb_messenger.fb_page_id', $usedeskChannel->fb_id)
            ->where('clients.company_id', $usedeskChannel->company_id)
            ->first();
    }

    /**
     * @param $psid
     * @param $usedeskChannel
     * @return Client
     */
    public function createMessengerClient($psid, $usedeskChannel)
    {
        $params = [
            'fields' => 'first_name,last_name,profile_pic'
        ];
        $userInfo = $this->getUserInfo($psid, $usedeskChannel->token, $params);

        $name = $userInfo['first_name'];

        if (isset($userInfo['last_name'])) $name .= ' ' . $userInfo['last_name'];

        $client = Client::create([
            'name' => $name,
            'company_id' => $usedeskChannel->company_id
        ]);
        $client->messenger_user_id = $psid;
        DB::table('client_fb_messenger')->insert([
            'client_id' => $client->id,
            'messenger_user_id' => $psid,
            'fb_page_id' => $usedeskChannel->fb_id,
        ]);

        return $client;
    }

    /**
     * @param $client
     * @param $usedeskChannel
     * @return mixed
     */
    public function findMessengerTicket($client, $usedeskChannel)
    {
        return Ticket::where('client_id', $client->id)
            ->where('email_channel_id', $usedeskChannel->id)
            ->where('company_id', $usedeskChannel->company_id)
            ->first();
    }

    /**
     * @param $ticket
     * @param $client
     * @param $message
     * @param $time
     * @return TicketComment
     */
    public function createMessengerTicketComment($ticket, $client, $message, $time)
    {
        $this->addImagesToMessage($message, $ticket->id, $ticket->company_id);

        $ticketComment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'message' => isset($message['text']) ? $message['text'] : '',
            'client_id' => $client->id,
            'from' => TicketComment::FROM_CLIENT,
            'published_at' => $time,
            'is_html' => 1
        ]);

        $this->saveFiles($message, $ticket->company_id, $ticketComment->id);

        return $ticketComment;
    }

    /**
     * @param $message
     * @param $ticketId
     * @param $companyId
     */
    public function addImagesToMessage(&$message, $ticketId, $companyId)
    {
        if (isset($message['attachments'])) {
            $link = 'upload/gimages/' . $companyId . '/' . $ticketId;
            $path = public_path($link);
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            $message['text'] = '';
            foreach ($message['attachments'] as $attachment) {
                if ($attachment['type'] == self::ATTACHMENT_IMAGE) {
                    $url = $attachment['payload']['url'];
                    $filePath = $this->saveFileToPath($path, $url);
                    if ($filePath !== false) {
                        $message['text'] .= $this->createImgLinkFromPath($link, $filePath);
                    }
                }

            }
        }
    }

    /**
     * @param $message
     * @param $companyId
     * @param $ticketCommentId
     */
    public function saveFiles($message, $companyId, $ticketCommentId)
    {
        $tempDir = sys_get_temp_dir() . '/' . $companyId . '/' . $ticketCommentId;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        if (isset($message['attachments'])) {
            $message['text'] = '';
            foreach ($message['attachments'] as $attachment) {
                if ($attachment['type'] == self::ATTACHMENT_FILE) {
                    $url = $attachment['payload']['url'];
                    $filePath = $this->saveFileToPath($tempDir, $url);
                    if ($filePath !== false) {
                        $ticketCommentFile = new TicketCommentFile(['ticket_comment_id' => $ticketCommentId]);
                        $ticketCommentFile->setFile($filePath);
                        $ticketCommentFile->save();
                    }
                }
            }
        }
    }

    /**
     * @param $fbTimestamp
     * @return Carbon
     */
    public function getCarbonFromFbTimestamp($fbTimestamp)
    {
        $timestamp = substr($fbTimestamp, 0, -3);
        return Carbon::createFromTimestamp($timestamp);
    }

    /**
     * @param array $message
     * @return string
     */
    public function getTicketSubject($message)
    {
        if (isset($message['text']) && $message['text']) {
            $subject = substr($message['text'], 0, 100);
        } else {
            $subject = trans('text._facebook.new_message');
        }

        return $subject;
    }
}