<?php

namespace Usedesk\FbIntegration\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Usedesk\FbIntegration\Models\Group;
use Usedesk\FbIntegration\PageController;
use Usedesk\FbIntegration\services\webhook\WebhookController;

class ChannelsController extends BaseController
{
    public function __construct()
    {

    }

    public function addChannel($data)
    {
        return $this->getMainView('user.company_email_channels.addFbChannel', [
            'userFbGroups' => $data['groups'],
            'userFbId' => $data['id']
        ]);
    }

    public function createChannel() {
        CompanyIntegration::check(Integration::TYPE_FACEBOOK);
        $fb = new Facebook([
            'app_id' => $_ENV['services.facebook.id'],
            'app_secret' => $_ENV['services.facebook.secret'],
            'default_graph_version' => 'v2.10',
        ]);
        $oAuth2Client = $fb->getOAuth2Client();
        $token = $oAuth2Client->getLongLivedAccessToken(Input::get('token'));
        $fbGroup = Group::select('fb_groups.id', 'company_email_channels.company_id')
            ->join('company_email_channels', 'company_email_channels.id', '=', 'fb_groups.channel_id')
            ->where('fb_groups.fb_id', Input::get('fid'))
            ->where('company_email_channels.deleted', false)
            ->first();
        if (!$fbGroup) {
            try {
                DB::beginTransaction();
                $fbPage = new PageController();
                if ($fbPage->createNewSubscription(Input::get('fid'), $token)) {
                    $channel = new CompanyEmailChannel();
                    $channel->type = 'facebook';
                    $channel->company_id = Company::current()->id;
                    $channel->name = Input::get('name');
                    $channel->auto_reply = 0;
                    $channel->from_name = $channel->name;
                    $channel->save();
                    FbGroup::addFbGroup(Input::get('fid'), $channel->id, $token);
                }
                DB::commit();
            } catch (Exception $e) {
                Log::error($e);
                DB::rollBack();
            }
        } else {
            $this->updateFbGroupToken($fbGroup->id, $fbGroup->company_id, $token);
        }
        $companyChannelsController = new CompanyChannelsController();
        return $companyChannelsController->getIndex();
    }


    public function getFbCallback($data)
    {
        $fbWebhookController = new WebhookController($data);
        return $fbWebhookController->parseMessage();
    }

}
