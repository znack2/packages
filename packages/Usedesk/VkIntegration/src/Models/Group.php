<?php

namespace Usedesk\FbIntegration\models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FbGroup extends BaseModel
{
    public $timestamps = false;

    protected $table = 'groups';
    
    protected $fillable = [
         'id',
         'fb_id',
         'channel_id',
         'is_active',
         'date_create',
         'company_id',
         'token',
         'updated_time'
    ];
    /**
     * @param Company $company
     * @param bool $incoming
     * @return array
     */
    public static function addGroup($fb_id, $channel_id, $token)//Company::current()->id;
    {
        $fb_group = new Group();
        $fb_group->fb_id =$fb_id;
        $fb_group->channel_id = $channel_id;
        $fb_group->is_active = 1;
        $fb_group->date_create = Carbon::now();
        $fb_group->updated_time = Carbon::now();
        $fb_group->company_id = $companyId;
        $fb_group->token = $token;
        $fb_group->save();
        return $fb_group->id;
    }
   /**
     * @return array
     */
    public static function getGroup($companyId)//Company::current()->id;
    {
        $fb_group = new Group();
        $groups = $fb_group->where('company_id', '=', $companyId)
                ->get()
                ->toArray();
        return $groups;
    }




}
