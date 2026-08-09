<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    protected $fillable = ['import_jobs_id', 'row_number', 'row_data', 'error_message'];


    protected $casts = [
        'row_data' => 'array', 
    ];
}
