<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'subject',
        'department',
        'user_id',
        'created_by_name',
        'last_updated_by_name',
        'status',
        'description',
    ];

    /**
     * Get the user who created this ticket.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the replies for this ticket.
     */
    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id');
    }
}
