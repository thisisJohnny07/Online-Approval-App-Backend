<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmountRange extends Model
{
    protected $table = 'tbl_amount_range_for_approval';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'range_from',
        'range_to',
        'transaction_type',
        'review_type',
        'approval_type'
    ];
}