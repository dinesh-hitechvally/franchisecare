<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['franchise:id,name,code', 'assignedTo:id,name']);

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($franchiseId = $request->get('franchise_id')) {
            $query->where('franchise_id', $franchiseId);
        }

        if ($assignedTo = $request->get('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        $perPage = $request->get('per_page', 15);
        
        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function show(SupportTicket $supportTicket)
    {
        $supportTicket->load([
            'franchise:id,name,code',
            'assignedTo:id,name',
            'replies' => function($q) {
                $q->orderBy('created_at', 'asc');
            }
        ]);

        return response()->json($supportTicket);
    }

    public function update(Request $request, SupportTicket $supportTicket)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:open,in_progress,waiting,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'resolved') {
            $validated['resolved_at'] = now();
        }

        $supportTicket->update($validated);

        return response()->json($supportTicket->load(['franchise:id,name', 'assignedTo:id,name']));
    }

    public function reply(Request $request, SupportTicket $supportTicket)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $reply = SupportTicketReply::create([
            'ticket_id' => $supportTicket->id,
            'user_id' => auth()->id(),
            'user_type' => 'admin',
            'message' => $request->message,
        ]);

        // Update ticket status if it was waiting
        if ($supportTicket->status === 'waiting') {
            $supportTicket->update(['status' => 'in_progress']);
        }

        return response()->json($reply, 201);
    }

    public function assign(Request $request, SupportTicket $supportTicket)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $supportTicket->update([
            'assigned_to' => $request->assigned_to,
            'status' => 'in_progress',
        ]);

        return response()->json($supportTicket->load('assignedTo:id,name'));
    }

    public function resolve(SupportTicket $supportTicket)
    {
        $supportTicket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return response()->json($supportTicket);
    }

    public function stats()
    {
        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'waiting' => SupportTicket::where('status', 'waiting')->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
            'by_priority' => [
                'urgent' => SupportTicket::where('priority', 'urgent')->where('status', '!=', 'resolved')->count(),
                'high' => SupportTicket::where('priority', 'high')->where('status', '!=', 'resolved')->count(),
                'medium' => SupportTicket::where('priority', 'medium')->where('status', '!=', 'resolved')->count(),
                'low' => SupportTicket::where('priority', 'low')->where('status', '!=', 'resolved')->count(),
            ],
        ];

        return response()->json($stats);
    }
}
