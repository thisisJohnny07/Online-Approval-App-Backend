<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class userDeviceInfo extends Model
{
    protected $table = 'tbl_main_users_device_info';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_code',
        'device_model',
        'device_brand',
        'device_sys_name',
        'device_sys_version',
        'device_id',
        'active_status',
    ];
}
