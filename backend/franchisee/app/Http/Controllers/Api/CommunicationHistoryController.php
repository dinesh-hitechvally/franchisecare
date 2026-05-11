<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsHistory;
use App\Models\EmailHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommunicationHistoryController extends Controller
{
    // ─── SMS History ────────────────────────────────────────────────────────────

    public function smsIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 25);
        $status  = $request->input('status'); // 'sent' or 'queued'

        $query = SmsHistory::query()->orderByDesc('created_at');

        if ($status === 'sent') {
            $query->where('status', 'sent');
        } elseif ($status === 'queued') {
            $query->where('status', 'queued');
        }

        $data = $query->paginate($perPage);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ],
        ]);
    }

    public function smsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_number'        => 'required|string|max:30',
            'customer_name'    => 'nullable|string|max:255',
            'message'          => 'required|string',
            'status'           => 'nullable|in:queued,sent,failed',
            'gateway_response' => 'nullable|string|max:500',
            'sent_at'          => 'nullable|date',
        ]);

        $validated['user_id'] = $request->user()?->id;
        $validated['status']  = $validated['status'] ?? 'queued';

        $record = SmsHistory::create($validated);

        return response()->json($record, 201);
    }

    public function smsShow(SmsHistory $smsHistory): JsonResponse
    {
        return response()->json($smsHistory);
    }

    public function smsDestroy(SmsHistory $smsHistory): JsonResponse
    {
        $smsHistory->delete();

        return response()->json(null, 204);
    }

    // ─── Email History ───────────────────────────────────────────────────────────

    public function emailIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 25);
        $status  = $request->input('status'); // 'sent' or 'queued'

        $query = EmailHistory::query()->orderByDesc('created_at');

        if ($status === 'sent') {
            $query->where('status', 'sent');
        } elseif ($status === 'queued') {
            $query->where('status', 'queued');
        }

        $data = $query->paginate($perPage);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ],
        ]);
    }

    public function emailStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_email'      => 'required|email|max:255',
            'to_email'        => 'required|email|max:255',
            'subject'         => 'required|string|max:500',
            'body'            => 'nullable|string',
            'status'          => 'nullable|in:queued,sent,failed',
            'mailer_response' => 'nullable|string|max:500',
            'sent_at'         => 'nullable|date',
        ]);

        $validated['user_id'] = $request->user()?->id;
        $validated['status']  = $validated['status'] ?? 'queued';

        $record = EmailHistory::create($validated);

        return response()->json($record, 201);
    }

    public function emailShow(EmailHistory $emailHistory): JsonResponse
    {
        return response()->json($emailHistory);
    }

    public function emailDestroy(EmailHistory $emailHistory): JsonResponse
    {
        $emailHistory->delete();

        return response()->json(null, 204);
    }
}
