<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLogs extends Model
{
    protected $table = 'tbl_main_users_device_logs';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_code',
        'device_model',
        'date_time',
        'log_status',
        'location'
    ];
}
