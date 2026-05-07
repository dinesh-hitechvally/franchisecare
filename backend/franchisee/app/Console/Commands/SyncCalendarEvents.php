<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Blockout;
use App\Models\CalendarEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-calendar-events {--company_id= : Company ID to sync for}')]
#[Description('Sync calendar events from bookings and blockouts')]
class SyncCalendarEvents extends Command
{
    public function handle()
    {
        $companyId = $this->option('company_id');

        if ($companyId) {
            $this->syncForCompany($companyId);
        } else {
            $companies = \App\Models\Company::pluck('id');
            foreach ($companies as $id) {
                $this->syncForCompany($id);
            }
        }

        $this->info('Calendar events synced successfully!');
    }

    private function syncForCompany($companyId)
    {
        // Clear existing events for this company
        CalendarEvent::where('company_id', $companyId)->delete();

        // Sync bookings
        $bookings = Booking::where('company_id', $companyId)->get();
        foreach ($bookings as $booking) {
            CalendarEvent::create([
                'company_id' => $companyId,
                'event_type' => 'booking',
                'title' => $booking->customer?->name ?? 'Booking',
                'description' => $booking->notes,
                'start_date' => $booking->start_date,
                'start_time' => $this->convertTo24Hour($booking->start_time),
                'end_date' => $booking->start_date,
                'end_time' => $this->convertTo24Hour($booking->end_time),
                'color' => $booking->calendar_color ?? '#3b82f6',
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'is_recurring' => !!$booking->recurring_id,
                'is_active' => $booking->status !== 'cancelled',
            ]);
        }

        // Sync blockouts
        $blockouts = Blockout::where('company_id', $companyId)->get();
        foreach ($blockouts as $blockout) {
            CalendarEvent::create([
                'company_id' => $companyId,
                'event_type' => 'blockout',
                'title' => $blockout->title,
                'description' => $blockout->notes,
                'start_date' => $blockout->start_date,
                'start_time' => $this->convertTo24Hour($blockout->start_time),
                'end_date' => $blockout->end_date,
                'end_time' => $this->convertTo24Hour($blockout->end_time),
                'location' => $blockout->location,
                'color' => '#9333ea',
                'blockout_id' => $blockout->id,
                'is_recurring' => $blockout->is_recurring,
                'is_active' => $blockout->active,
            ]);
        }

        $this->info("Synced calendar events for company: $companyId");
    }

    private function convertTo24Hour($time)
    {
        if (!$time) return '00:00:00';
        try {
            return \Carbon\Carbon::createFromFormat('h:i A', $time)->format('H:i:s');
        } catch (\Exception $e) {
            // Already in 24-hour format
            return $time;
        }
    }
}
