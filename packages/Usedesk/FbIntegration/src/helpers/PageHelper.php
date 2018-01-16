<?php
namespace UseDesk\Facebook\Page\Helpers;

use UseDesk\Facebook\FbController;
use Carbon\Carbon;
use CompanyEmailChannel;
use Client;
use Ticket;
use TicketComment;
use TicketCommentFile;
use ClientSocialNetwork;
use DB;
use Log;

class PageHelper extends FbController
{

    /**
     * @param string $fbGroupId
     * @return CompanyEmailChannel|null
     */
    public function getUsedeskChannel($fbGroupId)
    {
        $channel = CompanyEmailChannel::select('company_email_channels.id', 'company_email_channels.name',
            'company_email_channels.company_id', 'fb_groups.fb_id', 'fb_groups.token')
            ->join('fb_groups', 'fb_groups.channel_id', '=', 'company_email_channels.id')
            ->where('company_email_channels.deleted', false)
            ->where('fb_groups.fb_id', $fbGroupId)
            ->first();

        if (!$this->checkCompany($channel->company_id)) return null;

        return $channel;
    }

    /**
     * @param int $companyId
     * @return bool
     */
    protected function checkCompany($companyId)
    {
        return \Company::where('id', $companyId)
            ->where('blocked', '!=', \Company::BLOCK_FULL)
            ->exists();
    }

    /**
     * @param CompanyEmailChannel $usedeskChannel
     * @return Client
     */
    public function getCreateClientFromChannel(CompanyEmailChannel $usedeskChannel)
    {
        $fbGroup = $usedeskChannel->fb_group;
        $client = $this->getUsedeskClient($fbGroup->fb_id, $usedeskChannel->company_id);
        if (!$client) {

            $client = $this->createClientByFbId($fbGroup->fb_id, $usedeskChannel->name, $usedeskChannel->company_id);
        }

        return $client;
    }

    /**
     * @param int|string $fbId
     * @param CompanyEmailChannel $usedeskChannel
     * @return Client
     */
    public function getCreateClientByFbId($fbId, $usedeskChannel)
    {
        $client = $this->getUsedeskClient($fbId, $usedeskChannel->company_id);
        if (!$client) {
            $userInfo = $this->getUserInfo($fbId, $usedeskChannel->fb_group->token);
            $client = $this->createClientByFbId($fbId, $userInfo['name'], $usedeskChannel->company_id);
        }

        return $client;
    }

    /**
     * @param int $companyId
     * @param string|int $fbId
     * @return null|Client
     */
    public function getUsedeskClient($fbId, $companyId)
    {
        $client = Client::select('clients.*')
            ->join('client_social_networks', 'client_social_networks.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->where('client_social_networks.uid', $fbId)
            ->where('client_social_networks.type', ClientSocialNetwork::TYPE_FACEBOOK)
            ->first();

        return $client;
    }

    /**
     * @param string|int $fbId
     * @param string $name
     * @param int $companyId
     * @return Client
     */
    public function createClientByFbId($fbId, $name, $companyId)
    {
        $client = Client::create([
            'name' => $name,
            'company_id' => $companyId
        ]);
        $url = 'https://facebook.com/' . $fbId;
        ClientSocialNetwork::create([
            'type' => ClientSocialNetwork::TYPE_FACEBOOK,
            'client_id' => $client->id,
            'uid' => $fbId,
            'url' => $url
        ]);
        return $client;
    }


    /**
     * @param string|int $psid
     * @param string $token
     * @param array $params
     * @return \Facebook\FacebookResponse
     */
    public function getUserInfo($psid, $token, $params = [])
    {
        return $this->get($psid, $token, $params);

    }

    /**
     * @param int $clientId
     * @param string $socialId
     * @param CompanyEmailChannel $usedeskChannel
     * @param Carbon $time
     * @param string $subject
     * @param string|null $ticketType
     * @return Ticket
     */
    public function createTicket($clientId, $socialId, $usedeskChannel, $time, $subject, $ticketType = null )
    {
        $ticket = Ticket::create([
            'subject' => $subject,
            'email_channel_id' => $usedeskChannel->id,
            'channel' => Ticket::CHANNEL_FB,
            'published_at' => $time,
            'last_updated_at' => $time,
            'status_updated_at' => $time,
            'client_id' => $clientId,
            'contact' => $socialId,
            'social_id' => $socialId,
            'company_id' => $usedeskChannel->company_id,
            'status_id' => \TicketStatus::getByKey(\TicketStatus::SYSTEM_NEW)->id,
            'ticket_type' => $ticketType
        ]);

        return $ticket;
    }

    /**
     * @param string $link
     * @param string $filePath
     * @return string
     */
    public function createImgLinkFromPath($link, $filePath)
    {
        $name = basename($filePath);
        $link = 'https://' . \Config::get('app.secure_domain') . '.' . \Config::get('app.domain') . '/' . $link . '/' . $name;
        return '<img src="' . $link . '" >';
    }

    /**
     * @param $path
     * @param $url
     * @return int|string
     */
    public function saveFileToPath($path, $url)
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
    public function get($endpoint, $token, $params = [])
    {
        $response = $this->fb->sendRequest('GET', $endpoint, $params, $token)->getBody();
        return json_decode($response, true);
    }

    /**
     * @param $fbTimestamp
     * @return Carbon
     */
    public function getCarbonFromFbTimestamp($fbTimestamp)
    {
        return Carbon::createFromTimestamp($fbTimestamp);
    }

    /**
     * @param Ticket $ticket
     * @param Carbon|null $date
     */
    public function changeTicket(Ticket $ticket, $date = null)
    {
        $new = \TicketStatus::getByKey(\TicketStatus::SYSTEM_NEW)->id;
        if ($ticket->status_id != $new) {
            $opened = \TicketStatus::getByKey(\TicketStatus::SYSTEM_OPENED)->id;
            $ticket->status_id = $opened;
        }

        if ($date && $date->gt($ticket->last_updated_at)) {
            $ticket->last_updated_at = $date;
        } else {
            $ticket->last_updated_at = $this->now;
        }
        $ticket->save();
        \Trigger::checkAndRunAuto($ticket);
    }

    public function setLocale($companyId) {
        $lang = \Company::select('lang')->where('id', $companyId)->first()->lang;
        \App::setLocale($lang);
    }
}