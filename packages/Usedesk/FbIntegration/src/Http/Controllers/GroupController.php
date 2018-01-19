<?php

namespace Usedesk\FbIntegration\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Usedesk\FbIntegration\Models\Group;
use Usedesk\FbIntegration\PageController;
use Usedesk\FbIntegration\services\webhook\WebhookController;

class GroupController extends BaseController
{
    public function __construct()
    {

    }

    protected function updateGroupToken($usedeskId, $companyId, $token)
    {
        if ($companyId != $this->CurrentCompany->id) return;
        $fbPage = new PageController();
        if ($fbPage->createNewSubscription(Input::get('fid'), $token)) {
            Group::where('id', $usedeskId)->update([
                'token' => $token
            ]);
        }
    }

    public function checkGroup($id)
    {
        $groupExists = Group::join('company_email_channels', 'company_email_channels.id', '=', 'fb_groups.channel_id')
            ->where('fb_groups.fb_id', $id)
            ->where('company_email_channels.deleted', false)
            ->exists();
        return ['response' => intval($groupExists)];
    }
}