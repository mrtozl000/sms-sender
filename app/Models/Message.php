<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Message Eloquent model.
 *
 * Represents an SMS/message record with delivery status,
 * attempts, errors, and timestamps.
 *
 */
class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'phone_number',
        'content',
        'is_sent',
        'message_id',
        'sent_at',
        'attempts',
        'last_error'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include unsent messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnsent($query)
    {
        return $query->where('is_sent', false);
    }

    /**
     * Scope a query to only include sent messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    /**
     * Mark the message as sent and set the external message ID.
     *
     * @param string $messageId External provider message ID.
     * @return void
     */
    public function markAsSent(string $messageId): void
    {
        $this->update([
            'is_sent' => true,
            'message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Increment the number of attempts and optionally update last error.
     *
     * @param string|null $error Error message if sending failed.
     * @return void
     */
    public function incrementAttempts(?string $error = null): void
    {
        $this->increment('attempts');
        if ($error) {
            $this->update(['last_error' => $error]);
        }
    }
}
