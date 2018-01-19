<?php

namespace Usedesk\FbIntegration\models;

class FbRequestLog extends BaseModel
{
    public $timestamps = false;

    protected $table = 'request_log';
    
    protected $fillable = [
    	 'request',
	     'created_at',
	     'company_id',
	     'success'
	     'webhook_id',
	     'url',
	     'payload_format',
	     'payload',
	     'status',
	     'response',
	     'response_format'
 	];

	public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
