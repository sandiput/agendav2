<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class WhatsappNotification extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'meeting_id',
        'recipient_type',
        'recipient_number',
        'message_content',
        'status',
        'sent_at',
        'error_message',
        'whatsapp_message_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeIndividual($query)
    {
        return $query->where('recipient_type', 'individual');
    }

    public function scopeGroup($query)
    {
        return $query->where('recipient_type', 'group');
    }

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(30));
    }

    /**
     * Prepare the model for pruning.
     */
    protected function pruning()
    {
        // Log the pruning action
        \Log::info('Pruning WhatsApp notification', [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'created_at' => $this->created_at,
        ]);
    }
}