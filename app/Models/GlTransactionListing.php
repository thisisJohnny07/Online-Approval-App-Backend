<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlTransactionListing extends Model
{
    // Specify the table name
    protected $table = 'tbl_gl_transaction_listing';

    // If the table's primary key is different from the default "id"
    protected $primaryKey = 'id';

    // Disable auto-increment if the table doesn't use it
    public $incrementing = true;

    // Specify the type of the primary key if it's not an integer
    protected $keyType = 'int';

    // Define the fields that are mass assignable
    protected $fillable = [
        'ul_id',
        'transacting_party_id',
        'transacting_party',
        'transacting_party_type',
        'gl_module',
        'doc_type',
        'doc_no',
        'date_trans',
        'trans_type_id',
        'trans_type_description',
        'coa_complete_id',
        'cascade_code_1st',
        'cascade_code_2nd',
        'cascade_code_3rd',
        'cascade_code_4th',
        'acct_title_code',
        'acct_description',
        'sl_type',
        'sl_id',
        'sl_description',
        'sl_category_id',
        'sl_category_description',
        'debit_amount',
        'credit_amount',
        'transaction_status',
        'with_application_of_payment',
        'app_payment_date_from',
        'app_payment_date_to',
        'posting_status',
        'payment_status',
        'pos_terminal_no',
        'due_date',
        'ppe_id'
    ];

    // Define the types of the attributes
    protected $casts = [
        'date_trans' => 'date',
        'app_payment_date_from' => 'date',
        'app_payment_date_to' => 'date',
        'due_date' => 'date',
        'debit_amount' => 'decimal:3',
        'credit_amount' => 'decimal:3',
        'doc_no' => 'string',
        'ppe_id' => 'string',
    ];

    // Optionally, you can specify if timestamps are handled by Eloquent
    public $timestamps = false;
}