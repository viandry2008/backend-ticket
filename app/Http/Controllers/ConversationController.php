<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Ably\AblyRest;

class ConversationController extends Controller
{
    protected $ably;

    public function __construct()
    {
        $this->ably = new AblyRest(env('ABLY_API_KEY'));
    }

    // GET /api/tickets/{id}/conversations
    public function index($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $conversations = $ticket->conversations()->with('sender:id,name,email') ->orderBy('created_at', 'asc')->get();

        return response()->json($conversations);
    }

    // POST /api/tickets/{id}/conversations
    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'attachment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $conversation = TicketConversation::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'message' => $request->message,
            'attachment' => $request->attachment
        ]);

         $conversation->load('sender:id,name,email');

        // Broadcast via Ably
        $this->ably->channel("ticket.$ticketId")->publish('ticket.message', $conversation->toArray());

        return response()->json($conversation, 201);
    }
}
