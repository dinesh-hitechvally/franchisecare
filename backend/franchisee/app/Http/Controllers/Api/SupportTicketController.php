<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SupportTicket::query();

        if ($department = $request->input('department')) {
            if ($department === 'CLOSED') {
                $query->where('status', 'CLOSED');
            } else {
                $query->where('department', $department)->where('status', '!=', 'CLOSED');
            }
        }

        $tickets = $query->orderBy('id', 'desc')->get();

        $mapped = $tickets->map(function ($ticket) {
            return [
                'id' => (string) $ticket->id,
                'ticketId' => $ticket->ticket_id,
                'subject' => $ticket->subject,
                'department' => $ticket->department,
                'createdBy' => $ticket->created_by_name,
                'lastUpdatedBy' => $ticket->last_updated_by_name,
                'created' => $ticket->created_at ? $ticket->created_at->toIso8601String() : now()->toIso8601String(),
                'status' => $ticket->status,
                'description' => $ticket->description,
            ];
        });

        return response()->json($mapped);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|in:BUGS,ENHANCEMENT,ADMIN,URGENT',
            'description' => 'required|string',
        ]);

        $user = $request->user();
        $userName = $user ? $user->name : 'Mate Support';

        $ticket = SupportTicket::create([
            'ticket_id' => Str::random(13),
            'subject' => $validated['subject'],
            'department' => $validated['department'],
            'user_id' => $user ? $user->id : 1,
            'created_by_name' => $userName,
            'last_updated_by_name' => $userName,
            'status' => 'OPEN',
            'description' => $validated['description'],
        ]);

        return response()->json($this->mapTicket($ticket), 201);
    }

    /**
     * Display the specified resource with its replies.
     */
    public function show(Request $request, $id)
    {
        $ticket = SupportTicket::with('replies')->findOrFail($id);

        $mapped = $this->mapTicket($ticket);
        $mapped['replies'] = $ticket->replies->map(function ($reply) {
            return [
                'id' => (string) $reply->id,
                'userName' => $reply->user_name,
                'message' => $reply->message,
                'created' => $reply->created_at ? $reply->created_at->toIso8601String() : now()->toIso8601String(),
                'attachmentPath' => $reply->attachment_path,
            ];
        })->toArray();

        return response()->json($mapped);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        
        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:OPEN,IN-PROGRESS,CLOSED',
            'department' => 'sometimes|in:BUGS,ENHANCEMENT,ADMIN,URGENT',
        ]);

        $user = $request->user();
        $userName = $user ? $user->name : 'Mate Support';
        
        $validated['last_updated_by_name'] = $userName;

        $ticket->update($validated);

        return response()->json($this->mapTicket($ticket));
    }

    /**
     * Store a reply to the ticket.
     */
    public function addReply(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string',
            'close_ticket' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $userName = $user ? $user->name : 'Mate Support';

        $reply = SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user ? $user->id : 1,
            'user_name' => $userName,
            'message' => $validated['message'],
        ]);

        // If the user checked "Check to Close Ticket"
        if ($request->input('close_ticket')) {
            $ticket->status = 'CLOSED';
        } else {
            $ticket->status = 'IN-PROGRESS';
        }
        $ticket->last_updated_by_name = $userName;
        $ticket->save();

        return response()->json([
            'id' => (string) $reply->id,
            'userName' => $reply->user_name,
            'message' => $reply->message,
            'created' => $reply->created_at->toIso8601String(),
            'attachmentPath' => $reply->attachment_path,
            'ticketStatus' => $ticket->status,
        ], 201);
    }

    private function mapTicket(SupportTicket $ticket)
    {
        return [
            'id' => (string) $ticket->id,
            'ticketId' => $ticket->ticket_id,
            'subject' => $ticket->subject,
            'department' => $ticket->department,
            'createdBy' => $ticket->created_by_name,
            'lastUpdatedBy' => $ticket->last_updated_by_name,
            'created' => $ticket->created_at ? $ticket->created_at->toIso8601String() : now()->toIso8601String(),
            'status' => $ticket->status,
            'description' => $ticket->description,
        ];
    }
}
