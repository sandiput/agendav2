<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MeetingAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'attachment_type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function getUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function scopeDocuments($query)
    {
        return $query->where('attachment_type', 'document');
    }

    public function scopePhotos($query)
    {
        return $query->where('attachment_type', 'photo');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            // Delete file from storage when model is deleted
            if (Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }
        });
    }
}