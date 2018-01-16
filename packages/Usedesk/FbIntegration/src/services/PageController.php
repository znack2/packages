<?php
namespace FbExtention;

use UseDesk\Facebook\FbController;
use Carbon\Carbon;
use CompanyEmailChannel;
use Client;
use Ticket;
use TicketComment;
use TicketCommentFile;
use DB;
use Log;

class PageController extends FbController
{

    protected $messages;

    protected $post;

    public function __construct()
    {
        parent::__construct();
        $this->messages = new Message();
        $this->post = new Post();
    }

    /**
     * @param array $data
     */
    public function parseData($data)
    {
        foreach ($data['entry'] as $entry) {
            if (isset($entry['messaging'])) {
                $this->messages->readMessagesFromFb($entry);
            }
            if (isset($entry['changes'])) {
                $this->post->readChangesFromFb($entry);
            }
        }
    }

    /**
     * @param string $fbGroupId
     * @return CompanyEmailChannel|null
     */
    private function getUsedeskChannel($fbGroupId)
    {
        $channel = CompanyEmailChannel::select('company_email_channels.id', 'company_email_channels.company_id',
            'fb_groups.fb_id', 'fb_groups.token')
            ->join('fb_groups', 'fb_groups.channel_id', '=', 'company_email_channels.id')
            ->where('company_email_channels.deleted', false)
            ->where('fb_groups.fb_id', $fbGroupId)
            ->first();
        return $channel;
    }

    /**
     * @param $psid
     * @param $usedeskChannel
     * @return Client|null
     */
    private function getCreateClient($psid, $usedeskChannel)
    {
        $client = $this->findUsedeskClient($psid, $usedeskChannel);
        if (!$client) {
            $client = $this->createUsedeskClient($psid, $usedeskChannel);
        }

        return $client;
    }

    /**
     * @param $psid
     * @param $usedeskChannel
     * @return Client|null
     */
    private function findUsedeskClient($psid, $usedeskChannel)
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
    private function createUsedeskClient($psid, $usedeskChannel)
    {
        $userInfo = $this->getUserInfo($psid, $usedeskChannel->token);

        $name = $userInfo['first_name'];

        if (isset($userInfo['last_name'])) $name .= ' ' . $userInfo['last_name'];

        $client = Client::create([
            'name' => $name,
            'company_id' => $usedeskChannel->company_id
        ]);

        DB::table('client_fb_messenger')->insert([
            'client_id' => $client->id,
            'messenger_user_id' => $psid,
            'fb_page_id' => $usedeskChannel->fb_id,
        ]);

        return $client;
    }

    /**
     * @param string|int $psid
     * @param string $token
     * @return \Facebook\FacebookResponse
     */
    private function getUserInfo($psid, $token)
    {
        $params = [
            'fields' => 'first_name,last_name,profile_pic'
        ];

        return $this->get($psid, $token, $params);

    }

    /**
     * @param Client $client
     * @param CompanyEmailChannel $usedeskChannel
     * @param array $message
     * @param Carbon $time
     */
    private function addToTicket($client, $usedeskChannel, $message, $time)
    {
        $ticket = $this->findTicket($client, $usedeskChannel);
        if (!$ticket) {
            $ticket = $this->createTicket($client, $usedeskChannel, $time);
        }
        $this->createTicketComment($ticket, $client, $message, $time);
    }

    /**
     * @param $client
     * @param $usedeskChannel
     * @return mixed
     */
    private function findTicket($client, $usedeskChannel)
    {
        return Ticket::where('client_id', $client->id)
            ->where('email_channel_id', $usedeskChannel->id)
            ->where('company_id', $usedeskChannel->company_id)
            ->first();
    }

    /**
     * @param $client
     * @param $usedeskChannel
     * @param $time
     * @return mixed
     */
    private function createTicket($client, $usedeskChannel, $time)
    {
        $ticket = Ticket::create([
            'subject' => 'facebook',
            'email_channel_id' => $usedeskChannel->id,
            'channel' => Ticket::CHANNEL_FB,
            'published_at' => $time,
            'last_updated_at' => $time,
            'status_updated_at' => $time,
            'client_id' => $client->id,
            'contact' => $client->messenger_user_id,
            'social_id' => $client->messenger_user_id,
            'company_id' => $usedeskChannel->company_id,
            'status_id' => \TicketStatus::getByKey(\TicketStatus::SYSTEM_NEW)->id
        ]);

        return $ticket;
    }

    /**
     * @param $ticket
     * @param $client
     * @param $message
     * @param $time
     * @return mixed
     */
    private function createTicketComment($ticket, $client, $message, $time)
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

    private function addImagesToMessage(&$message, $ticketId, $companyId)
    {
        $link = 'upload/gimages/' . $companyId . '/' . $ticketId;
        $path = public_path($link);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (isset($message['attachments'])) {
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

    private function createImgLinkFromPath($link, $filePath)
    {
        $name = basename($filePath);
        $link = 'https://' . \Config::get('app.secure_domain') . '.' . \Config::get('app.domain') . '/' . $link . '/' . $name;
        return '<img src="' . $link . '" >';
    }


    private function saveFiles($message, $companyId, $ticketCommentId)
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

    private function saveFileToPath($path, $url)
    {
        $filename = parse_url($url, PHP_URL_PATH);
        $filename = basename($filename);
        $newPath = $path . '/' . $filename;
        $bytes = file_put_contents($newPath, file_get_contents($url));
        if ($bytes !== false) {
            return $newPath;
        }

        return $bytes;
    }

    /**
     * @param $endpoint
     * @param $token
     * @param array $params
     * @return array
     */
    private function get($endpoint, $token, $params = [])
    {
        $response = $this->fb->sendRequest('GET', $endpoint, $params, $token)->getBody();
        return json_decode($response, true);
    }

    private function getCarbonFromFbTimestamp($fbTimestamp)
    {
        $timestamp = substr($fbTimestamp, 0, -3);
        return Carbon::createFromTimestamp($timestamp);
    }

    public function createNewSubscription($pageId, $token)
    {
        $response = $this->fb->post(
            '/' . $pageId . '/subscribed_apps',
            [],
            $token
        );
        $graphNode = $response->getGraphNode();
        $res = $graphNode->uncastItems();
        return $res['success'];
    }
}