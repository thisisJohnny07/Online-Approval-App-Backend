<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApproverTagging extends Model
{
    protected $table = 'tbl_approver_tagging';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_type',
        'approval_type',
        'condition'
    ];
}
