<?php

namespace Usedesk\FbIntegration\models;

use Carbon\Carbon;

class Account extends BaseModel
{
    public $timestamps = false;

    protected $table = 'accounts';
    
    protected $fillable = [
         'id',
         'user_id',
         'channel_id',
         'is_active',
         'date_create',
         'company_id',
         'token',
         'updated_time',
         'vk_user_id'
    ];

    /**
     * @param $user_id
     * @param $channel_id
     * @param $token
     * @param $vk_user_id
     * @return int
     */
    public static function addAccount($user_id, $channel_id, $token, $vk_user_id,$company_id) // Company::current()->id;
    {
        $vk = new Account();
        $vk->user_id = $user_id;
        $vk->channel_id = $channel_id;
        $vk->is_active = 1;
        $vk->date_create = Carbon::now();
        $vk->updated_time = Carbon::now();
        $vk->company_id = $company_id;
        $vk->token = $token;
        $vk->vk_user_id = $vk_user_id;
        $vk->save();
        return $vk->id;
    }

    /**
     * @param $company_id
     * @return array
     */
    public static function getAccount($company_id)//Company::current()->id
    {
        $vk = new Account();
        $accounts = $vk->where('company_id', '=', $company_id)
            ->orderByRaw("RAND()")
            ->first()
            ->toArray();
        return $accounts;
    }
}
