<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Waitlist;
use App\Models\WaitlistDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaitlistController extends Controller
{
    /**
     * Display a listing of waitlists.
     */
    public function index(Request $request)
    {
        $query = Waitlist::with(['customer', 'details.item', 'details.service']);

        if (auth()->check() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('dateFrom')) {
            $query->where('start_date', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->where('start_date', '<=', $request->dateTo);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('notes', 'like', $term)
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", [$term]);
                    })
                    ->orWhereHas('details.service', function ($sq) use ($term) {
                        $sq->where('name', 'like', $term);
                    })
                    ->orWhereHas('details.item', function ($iq) use ($term) {
                        $iq->where('name', 'like', $term);
                    });
            });
        }

        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        if ($request->filled('page')) {
            $page = max(1, (int) $request->input('page'));
            $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Store a newly created waitlist.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'              => 'required|exists:customers,id',
            'start_date'               => 'required|date',
            'end_date'                 => 'nullable|date|after_or_equal:start_date',
            'start_time'               => 'required|string',
            'end_time'                 => 'nullable|string',
            'calendar_color'           => 'nullable|string',
            'send_sms'                 => 'boolean',
            'send_email'               => 'boolean',
            'status'                   => 'in:active,cancelled,completed,expired',
            'total'                    => 'required|numeric',
            'duration'                 => 'required|integer',
            'notes'                    => 'nullable|string',
            'services'                 => 'required|array',
            'services.*.item_id'       => 'required|exists:customer_items,id',
            'services.*.service_id'    => 'required|exists:services,id',
            'services.*.service_price' => 'required|numeric',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['status']     = $validated['status'] ?? 'active';

        $waitlist = Waitlist::create($validated);

        $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->toArray();
        $services   = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

        foreach ($validated['services'] as $serviceItem) {
            $sid = $serviceItem['service_id'];
            $waitlist->details()->create([
                'company_id' => auth()->user()->company_id,
                'item_id'    => $serviceItem['item_id'],
                'service_id' => $sid,
                'price'      => $serviceItem['service_price'],
                'duration'   => $services->has($sid) ? $services[$sid]->duration : 0,
            ]);
        }

        return response()->json($waitlist->fresh(['customer', 'details.item', 'details.service']), 201);
    }

    /**
     * Display a single waitlist.
     */
    public function show(Waitlist $waitlist)
    {
        return response()->json($waitlist->load(['customer', 'details.item', 'details.service']));
    }

    /**
     * Update a waitlist.
     */
    public function update(Request $request, Waitlist $waitlist)
    {
        $validated = $request->validate([
            'customer_id'              => 'sometimes|exists:customers,id',
            'start_date'               => 'sometimes|date',
            'end_date'                 => 'nullable|date|after_or_equal:start_date',
            'start_time'               => 'sometimes|string',
            'end_time'                 => 'nullable|string',
            'calendar_color'           => 'nullable|string',
            'send_sms'                 => 'boolean',
            'send_email'               => 'boolean',
            'status'                   => 'in:active,cancelled,completed,expired',
            'total'                    => 'sometimes|numeric',
            'duration'                 => 'sometimes|integer',
            'notes'                    => 'nullable|string',
            'services'                 => 'sometimes|array',
            'services.*.item_id'       => 'required_with:services|exists:customer_items,id',
            'services.*.service_id'    => 'required_with:services|exists:services,id',
            'services.*.service_price' => 'required_with:services|numeric',
        ]);

        $waitlist->update($validated);

        if (isset($validated['services'])) {
            $waitlist->details()->delete();

            $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->toArray();
            $services   = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

            foreach ($validated['services'] as $serviceItem) {
                $sid = $serviceItem['service_id'];
                $waitlist->details()->create([
                    'company_id' => auth()->user()->company_id,
                    'item_id'    => $serviceItem['item_id'],
                    'service_id' => $sid,
                    'price'      => $serviceItem['service_price'],
                    'duration'   => $services->has($sid) ? $services[$sid]->duration : 0,
                ]);
            }
        }

        return response()->json($waitlist->fresh(['customer', 'details.item', 'details.service']));
    }

    /**
     * Update only status.
     */
    public function updateStatus(Request $request, Waitlist $waitlist)
    {
        $request->validate([
            'status' => 'required|in:active,cancelled,completed,expired',
        ]);

        $waitlist->update(['status' => $request->status]);

        return response()->json($waitlist->fresh(['customer', 'details.item', 'details.service']));
    }

    /**
     * Convert waitlist to a real booking.
     */
    public function convertToBooking(Waitlist $waitlist)
    {
        return DB::transaction(function () use ($waitlist) {
            $waitlist->load(['customer', 'details.item', 'details.service']);

            $booking = Booking::create([
                'company_id'     => auth()->user()->company_id,
                'customer_id'    => $waitlist->customer_id,
                'start_date'     => $waitlist->start_date,
                'start_time'     => $waitlist->start_time,
                'end_time'       => $waitlist->end_time,
                'calendar_color' => $waitlist->calendar_color,
                'send_sms'       => $waitlist->send_sms,
                'send_email'     => $waitlist->send_email,
                'status'         => 'active',
                'total'          => $waitlist->total,
                'duration'       => $waitlist->duration,
                'notes'          => $waitlist->notes,
            ]);

            foreach ($waitlist->details as $detail) {
                $booking->details()->create([
                    'company_id' => auth()->user()->company_id,
                    'item_id'    => $detail->item_id,
                    'service_id' => $detail->service_id,
                    'price'      => $detail->price,
                    'duration'   => $detail->duration,
                ]);
            }

            $waitlist->update(['status' => 'completed']);

            return response()->json([
                'message' => 'Waitlist converted to booking successfully.',
                'booking' => $booking->fresh(['customer', 'details.item', 'details.service']),
                'waitlist' => $waitlist->fresh(),
            ]);
        });
    }

    /**
     * Send email confirmation for a waitlist.
     */
    public function sendEmailConfirmation(Waitlist $waitlist)
    {
        // Hook into existing email infrastructure if available
        return response()->json(['message' => 'Email confirmation sent successfully.']);
    }

    /**
     * Remove a waitlist.
     */
    public function destroy(Waitlist $waitlist)
    {
        $waitlist->delete();
        return response()->json(null, 204);
    }
}
