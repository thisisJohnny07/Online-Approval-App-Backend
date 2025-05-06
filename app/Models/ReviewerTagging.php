<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewerTagging extends Model
{
    protected $table = 'tbl_reviewer_tagging';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_type',
        'approval_type',
        'condition'
    ];
}
