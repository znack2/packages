<?php

namespace Usedesk\FbIntegration\models;

class Channel extends BaseModel
{
    public $timestamps = false;

    protected $table = 'channels';

    protected $fillable = [
    	 'id',
    	 'company_email_channel_id',
    	 'company_id',
    	 'vk_group_id',
    	 'secret_key',
         'vk_submit_string',
         'group_key',
         'user_key',
         'vk_user_id'
     ];
}