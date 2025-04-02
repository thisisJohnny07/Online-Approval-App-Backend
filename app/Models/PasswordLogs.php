<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordLogs extends Model
{
    protected $table = 'tbl_main_user_password_logs';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_code',
        'device_id',
        'timestamp',
    ];
}