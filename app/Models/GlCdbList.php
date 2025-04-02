<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlCdbList extends Model
{
    protected $table = 'tbl_gl_cdb_list'; // Define the table name

    protected $primaryKey = 'id'; // Set the primary key

    public $timestamps = false; // Disable timestamps if not needed

    protected $fillable = [
        'ul_id',
        'transacting_party_id',
        'transacting_party',
        'doc_type',
        'doc_no',
        'date_trans',
        'trans_type_id',
        'trans_type_description',
        'cash_account_id',
        'check_date',
        'check_no',
        'check_drawee_bank',
        'check_amount',
        'check_payee',
        'transaction_status',
        'online_processing_status',
        'approver_remarks',
        'returned_remarks',
        'online_process_date',
        'notification',
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
    ];
}
