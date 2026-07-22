<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'user_name',
        'message',
        'attachment_path',
    ];

    /**
     * Get the ticket this reply belongs to.
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }
}
