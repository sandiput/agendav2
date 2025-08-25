<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'notification_type',
        'scheduled_at',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeIndividualReminders($query)
    {
        return $query->where('notification_type', 'individual_reminder');
    }

    public function scopeGroupNotifications($query)
    {
        return $query->where('notification_type', 'group_notification');
    }

    public function scopeDue($query)
    {
        return $query->where('scheduled_at', '<=', now())
                    ->where('status', 'pending');
    }
}