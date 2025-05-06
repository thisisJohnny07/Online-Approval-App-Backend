<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStatus extends Model
{
    protected $table = 'tbl_transaction_approval_status';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'doc_type',
        'doc_no',
        'transaction_type',
        'approval_type',
        'approver',
        'approval_status'
    ];
}
