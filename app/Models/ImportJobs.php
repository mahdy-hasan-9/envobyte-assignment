<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJobs extends Model
{
    protected $fillable = ['account_id', 'user_id', 'vault_id' , 'filename', 'file_path', 'total_rows', 'processed_rows', 'failed_rows', 'status', 'failure_message', 'started_at', 'completed_at'];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];


    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function errors()
    {
        return $this->hasMany(ImportError::class);
    }


    public function getProgressPctAttribute(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }
}
