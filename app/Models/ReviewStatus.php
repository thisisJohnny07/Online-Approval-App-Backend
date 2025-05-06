<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewStatus extends Model
{
    protected $table = 'tbl_transaction_review_status';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'doc_type',
        'doc_no',
        'transaction_type',
        'review_type',
        'reviewer',
        'review_status'
    ];
}
