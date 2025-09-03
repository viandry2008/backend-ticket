<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'university_id', 'title', 'description', 'status', 'assigned_to', 'created_by'
    ];

    public function conversations()
    {
        return $this->hasMany(TicketConversation::class);
    }
}
