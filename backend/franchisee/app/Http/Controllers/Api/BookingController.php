<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRecurring;
use App\Models\Service;
use Illuminate\Http\Request;
use PDF;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Booking::with(['customer', 'details.item', 'details.service']);

        // Filter by authenticated user's company_id
        if (auth()->check() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('dateFrom')) {
            $query->where('start_date', '>=', $request->dateFrom);
        }

        if ($request->has('dateTo')) {
            $query->where('start_date', '<=', $request->dateTo);
        }

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';

            $query->where(function ($q) use ($term) {
                $q->where('notes', 'like', $term);

                $q->orWhereHas('customer', function ($customerQuery) use ($term) {
                    $customerQuery->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$term]);
                });

                $q->orWhereHas('details.service', function ($serviceQuery) use ($term) {
                    $serviceQuery->where('name', 'like', $term);
                });

                $q->orWhereHas('details.item', function ($itemQuery) use ($term) {
                    $itemQuery->where('name', 'like', $term);
                });
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        if ($request->filled('page')) {
            $page = max(1, (int) $request->input('page'));
            $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'calendar_color' => 'nullable|string',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'status' => 'required|in:active,cancelled,completed,archived',
            'total' => 'required|numeric',
            'duration' => 'required|integer',
            'notes' => 'nullable|string',
            'services' => 'required|array',
            'services.*.item_id' => 'required|exists:customer_items,id',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.service_price' => 'required|numeric',
        ]);

        // Set company_id from authenticated user
        $validated['company_id'] = auth()->user()->company_id;

        $booking = Booking::create($validated);

        // Get unique service IDs to fetch duration
        $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->toArray();
        $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

        // Create booking details from services array
        foreach ($validated['services'] as $serviceItem) {
            $serviceId = $serviceItem['service_id'];
            $booking->details()->create([
                'company_id' => auth()->user()->company_id,
                'item_id' => $serviceItem['item_id'],
                'service_id' => $serviceId,
                'price' => $serviceItem['service_price'],
                'duration' => $services->has($serviceId) ? $services[$serviceId]->duration : 0,
            ]);
        }

        return response()->json($booking->fresh(['customer', 'details.item', 'details.service']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        return response()->json($booking->load(['customer', 'details.item', 'details.service']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes',
            'end_time' => 'nullable',
            'calendar_color' => 'nullable|string',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'status' => 'sometimes|in:active,cancelled,completed,archived',
            'total' => 'sometimes|numeric',
            'duration' => 'sometimes|integer',
            'notes' => 'nullable|string',
            'services' => 'sometimes|array',
            'services.*.item_id' => 'required_with:services|exists:customer_items,id',
            'services.*.service_id' => 'required_with:services|exists:services,id',
            'services.*.service_price' => 'required_with:services|numeric',
        ]);

        $booking->update($validated);

        if (isset($validated['services'])) {
            $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->toArray();
            $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

            $booking->details()->delete();
            foreach ($validated['services'] as $serviceItem) {
                $serviceId = $serviceItem['service_id'];
                $booking->details()->create([
                    'company_id' => $booking->company_id,
                    'item_id' => $serviceItem['item_id'],
                    'service_id' => $serviceId,
                    'price' => $serviceItem['service_price'],
                    'duration' => $services->has($serviceId) ? $services[$serviceId]->duration : 0,
                ]);
            }
        }

        return response()->json($booking->fresh(['customer', 'details.item', 'details.service']));
    }

    /**
     * Update status only.
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,cancelled,completed,archived',
        ]);

        $booking->update(['status' => $validated['status']]);

        return response()->json($booking->load(['customer', 'details.item', 'details.service']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(null, 204);
    }

    /**
     * Generate invoice PDF for the booking.
     */
    public function generateInvoice(Booking $booking)
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        
        $pdf = PDF::loadView('bookings.invoice', compact('booking'));
        
        return $pdf->download("invoice-{$booking->id}.pdf");
    }

    /**
     * Generate receipt PDF for the booking.
     */
    public function generateReceipt(Booking $booking)
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        
        $pdf = PDF::loadView('bookings.receipt', compact('booking'));
        
        return $pdf->download("receipt-{$booking->id}.pdf");
    }
}
