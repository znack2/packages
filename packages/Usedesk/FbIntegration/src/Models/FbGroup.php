<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property bigint $fb_id
 * @property int $channel_id
 * @property tinyint $is_active
 * @property datetime $date_create
 * @property datetime $updated_time
 * @property string $company_id
 */
namespace FbExtention\models;
class FbGroup extends BaseModel
{
    public $timestamps = false;

    protected $table = 'fb_groups';
    protected $fillable = ['id', 'fb_id', 'channel_id', 'is_active', 'date_create', 'company_id', 'token', 'updated_time'];


    /**
     * @param Company $company
     * @param bool $incoming
     * @return array
     */
    public static function addFbGroup($fb_id, $channel_id, $token)
    {
        $fb_group = new FbGroup();
        $fb_group->fb_id =$fb_id;
        $fb_group->channel_id = $channel_id;
        $fb_group->is_active = 1;
        $fb_group->date_create = Carbon::now();
        $fb_group->updated_time = Carbon::now();
        $fb_group->company_id = Company::current()->id;
        $fb_group->token = $token;
        $fb_group->save();
        return $fb_group->id;
    }

    /**
     * @return array
     */
    public static function getFbGroup()
    {
        $fb_group = new FbGroup();
        $groups = $fb_group->where('company_id', '=', Company::current()->id)
                ->get()
                ->toArray();
        return $groups;
    }

    public static function getFbAccountWhereCompany($companyId)
    {
        $fb_group = new FbGroup();
        $groups = $fb_group->where('company_id', '=', $companyId)
                ->get()
                ->toArray();
        return $groups;
    }




}
