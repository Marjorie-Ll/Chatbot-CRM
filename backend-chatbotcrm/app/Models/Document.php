<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'type',
        'file_path',
        'content',
        'embeddings',
        'processed',
        'processed_at',
        'uploaded_by'
    ];

    protected $casts = [
        'embeddings' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function getStatusAttribute()
    {
        if ($this->processed) {
            return 'processed';
        } else if ($this->created_at->diffInMinutes() > 10) {
            return 'failed';
        } else {
            return 'processing';
        }
    }

    public function getSizeAttribute()
    {
        if ($this->file_path && \Storage::exists($this->file_path)) {
            return \Storage::size($this->file_path);
        }
        return 0;
    }
}
