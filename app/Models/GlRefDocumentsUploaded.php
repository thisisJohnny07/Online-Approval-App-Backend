<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlRefDocumentsUploaded extends Model
{
    protected $table = 'tbl_gl_ref_documents_uploaded'; // Specify the table name

    protected $primaryKey = 'id'; // Define primary key

    public $timestamps = false; // Disable timestamps if not present

    protected $fillable = [
        'doc_type',
        'doc_no',
        'file_name',
        'file_path',
        'uploaded_by',
        'date_uploaded'
    ];
}