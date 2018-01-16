<?php
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use FbExtention\models\FbGroup as FbGroup;
use FbExtention\PageController as PageController;
use FbExtention\services\webhook\WebhookController as WebhookController;
class FbChannelsController extends BaseController
{
    public function __construct()
    {

    }

    public function addFbChannel()
    {
        return $this->getMainView('user.company_email_channels.addFbChannel', [
            'userFbGroups' => Input::get('groups'),
            'userFbId' => Input::get('id')
        ]);
    }

    public function createFbChannel() {
        CompanyIntegration::check(Integration::TYPE_FACEBOOK);
        $fb = new FbExtention\Facebook([
            'app_id' => $_ENV['services.facebook.id'],
            'app_secret' => $_ENV['services.facebook.secret'],
            'default_graph_version' => 'v2.10',
        ]);
        $oAuth2Client = $fb->getOAuth2Client();
        $token = $oAuth2Client->getLongLivedAccessToken(Input::get('token'));
        $fbGroup = FbGroup::select('fb_groups.id', 'company_email_channels.company_id')
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


    protected function updateFbGroupToken($usedeskId, $companyId, $token)
    {
        if ($companyId != $this->CurrentCompany->id) return;
        $fbPage = new PageController();
        if ($fbPage->createNewSubscription(Input::get('fid'), $token)) {
            FbGroup::where('id', $usedeskId)->update([
                'token' => $token
            ]);
        }
    }

    public function checkFbGroup($id)
    {
        $groupExists = FbGroup::join('company_email_channels', 'company_email_channels.id', '=', 'fb_groups.channel_id')
            ->where('fb_groups.fb_id', $id)
            ->where('company_email_channels.deleted', false)
            ->exists();
        return ['response' => intval($groupExists)];
    }

    public function getFbCallback()
    {
        $fbWebhookController = new WebhookController(Input::all());
        return $fbWebhookController->parseMessage();
    }

}
