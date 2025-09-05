<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Ably\AblyRest;

class TicketController extends Controller
{
    // Ably client
    protected $ably;

    public function __construct()
    {
        $this->ably = new AblyRest(env('ABLY_API_KEY'));
    }

    // GET /api/tickets
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin_university') {
            $tickets = Ticket::with(['university', 'assigned'])->where('created_by', $user->id)->get();
        } elseif ($user->role === 'support_staff') {
            $tickets = Ticket::with(['university', 'assigned'])->where('assigned_to', $user->id)->orWhereNull('assigned_to')->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($tickets);
    }

    // POST /api/tickets
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'university_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $ticket = Ticket::create([
            'university_id' => $request->university_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'open',
            'created_by' => Auth::id(),
        ]);

        // Broadcast via Ably
        $this->ably->channel("ticket.$ticket->id")->publish('ticket.created', $ticket->toArray());

        return response()->json($ticket, 201);
    }

    // GET /api/tickets/{id}
    public function show($id)
    {
        $ticket = Ticket::with(['createdBy.university','assigned','conversations.sender'])->findOrFail($id);
        return response()->json($ticket);
    }

    // PUT /api/tickets/users/assign
    public function getSupportStaff()
    {
        $users = User::where('role', 'support_staff')
            // ->where('id', '!=', Auth::id())
            ->select('id', 'name', 'email','role')
            ->get();

        return response()->json([
            'message' => 'List support staff retrieved successfully',
            'data'    => $users
        ]);
    }

    // PUT /api/tickets/{id}/assign
    public function assign(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $ticket->update(['assigned_to' => $request->assigned_to, 'status' => 'assigned']);

        // Broadcast
        $this->ably->channel("ticket.$ticket->id")->publish('ticket.assigned', $ticket->toArray());

        return response()->json($ticket);
    }

    // PUT /api/tickets/{id}/status
    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:open,assigned,in_progress,resolved,closed'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $ticket->update(['status' => $request->status]);

        // Broadcast
        $this->ably->channel("ticket.$ticket->id")->publish('ticket.updated', $ticket->toArray());

        return response()->json($ticket);
    }

    // DELETE /api/tickets/{id}
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted']);
    }
}
