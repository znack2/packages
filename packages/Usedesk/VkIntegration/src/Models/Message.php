<?php

namespace Usedesk\FbIntegration\models;

class Message extends BaseModel
{
    public $timestamps = false;

    protected $table = 'messages';
    
    protected $fillable = [
		 'id',
	     'message',
	     'company_id',
	     'company_email_channel_id',
	     'read'
	];

}
