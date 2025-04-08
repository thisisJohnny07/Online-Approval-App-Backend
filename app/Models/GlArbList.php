<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlArbList extends Model
{
    protected $table = 'tbl_gl_arb_list';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'ul_id',
        'transacting_party_id',
        'transacting_party',
        'doc_type',
        'doc_no',
        'date_trans',
        'trans_type_id',
        'trans_type_description',
        'total_amount',
        'transaction_status',
        'approver_remarks',
        'returned_remarks',
        'sys_type',
        'prepared_by',
        'prepared_date',
        'timestamp',
        'reviewed_by',
        'reviewed_date',
        'approved_by',
        'approved_date',
        'cancel_by',
        'cancel_date',
        'remarks',
        'terms_payment',
        'due_date',
    ];    
}
