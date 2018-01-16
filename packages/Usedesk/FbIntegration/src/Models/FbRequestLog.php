<?php

use Carbon\Carbon;

/**
 * @property int $id
 * @property string $request
 * @property Carbon $created_at
 * @property int $company_id
 * @property bool $success
 */
class FbRequestLog extends BaseModel
{
    public $timestamps = false;

    protected $table = 'facebook_request_log';
    protected $fillable = ['request', 'created_at', 'company_id', 'success'];
}
