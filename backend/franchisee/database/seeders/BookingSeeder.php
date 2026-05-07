<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Customer;
use App\Models\Service;
use App\Models\CustomerItem;
use App\Models\ServiceCategory;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('slug', 'mate-franchise-care')->first();
        if (!$company) {
            return;
        }
        // 1. Create Service Categories
        $categories = [
            ['name' => 'Grooming', 'description' => 'Complete grooming services'],
            ['name' => 'Bath', 'description' => 'Bathing and drying services'],
            ['name' => 'Add-on', 'description' => 'Extra services like nail trimming'],
            ['name' => 'Treatment', 'description' => 'Specialized treatments'],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['name']] = ServiceCategory::create($cat);
        }

        // 2. Create Services
        $servicesData = [
            ['name' => 'Full Groom - Small', 'category' => 'Grooming', 'price' => 65.00, 'duration' => 60, 'size' => 'small'],
            ['name' => 'Full Groom - Medium', 'category' => 'Grooming', 'price' => 85.00, 'duration' => 90, 'size' => 'medium'],
            ['name' => 'Full Groom - Large', 'category' => 'Grooming', 'price' => 110.00, 'duration' => 120, 'size' => 'large'],
            ['name' => 'Bath & Dry', 'category' => 'Bath', 'price' => 45.00, 'duration' => 45, 'size' => 'medium'],
            ['name' => 'Nail Trim', 'category' => 'Add-on', 'price' => 15.00, 'duration' => 15, 'size' => 'small'],
            ['name' => 'Ear Cleaning', 'category' => 'Add-on', 'price' => 10.00, 'duration' => 10, 'size' => 'small'],
            ['name' => 'Puppy Cut', 'category' => 'Grooming', 'price' => 60.00, 'duration' => 60, 'size' => 'small'],
            ['name' => 'Deshedding Treatment', 'category' => 'Treatment', 'price' => 25.00, 'duration' => 30, 'size' => 'large'],
        ];

        foreach ($servicesData as $data) {
            $catName = $data['category'];
            unset($data['category']);
            $data['category_id'] = $categoryModels[$catName]->id;
            $data['status'] = 1;
            Service::create($data);
        }

        $allServices = Service::all();

        // 3. Create Customers & Items
        $customerData = [
            [
                'first_name' => 'Alice', 'last_name' => 'Johnson', 'email' => 'alice@example.com', 'phone' => '0412345678', 
                'street_address' => '123 Bark Lane', 'suburb' => 'North Melbourne', 'postcode' => '3051', 'state' => 'VIC',
                'items' => [
                    ['name' => 'Max', 'breed' => 'Golden Retriever', 'size' => 'large', 'gender' => 'male'],
                    ['name' => 'Luna', 'breed' => 'Border Collie', 'size' => 'medium', 'gender' => 'female'],
                ]
            ],
            [
                'first_name' => 'Bob', 'last_name' => 'Smith', 'email' => 'bob@example.com', 'phone' => '0422333444', 
                'street_address' => '45 Paws St', 'suburb' => 'Richmond', 'postcode' => '3121', 'state' => 'VIC',
                'items' => [
                    ['name' => 'Bella', 'breed' => 'French Bulldog', 'size' => 'small', 'gender' => 'female'],
                ]
            ],
            [
                'first_name' => 'Carol', 'last_name' => 'White', 'email' => 'carol@example.com', 'phone' => '0433555666', 
                'street_address' => '78 Woof Ave', 'suburb' => 'Brunswick', 'postcode' => '3056', 'state' => 'VIC',
                'items' => [
                    ['name' => 'Charlie', 'breed' => 'Poodle', 'size' => 'medium', 'gender' => 'male'],
                ]
            ],
        ];

        foreach ($customerData as $data) {

            $items = $data['items'];
            unset($data['items']);
            $data['company_id'] = $company->id;
            $data['address'] = $data['street_address'] . ', ' . $data['suburb'] . ' ' . $data['postcode'] . ' ' . $data['state'];
            $customer = Customer::create($data);

            foreach ($items as $item) {
                $item['customer_id'] = $customer->id;
                $item['company_id'] = $company->id;
                $item['is_active'] = true;
                CustomerItem::create($item);
            }

        }

        $allCustomers = Customer::all();
        $allItems = CustomerItem::all();

        // 4. Create Bookings
        $statuses = ['active', 'cancelled', 'completed', 'archived'];
        $times = ['09:00', '10:30', '12:00', '14:30', '16:00'];
        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

        for ($i = 0; $i < 10; $i++) {
            $customer = $allCustomers->random();
            $customerItems = $customer->customerItems;
            if ($customerItems->isEmpty()) continue;

            $start_time = $times[array_rand($times)];

            $booking = Booking::create([
                'customer_id' => $customer->id,
                'company_id' => $company->id,
                'start_date' => Carbon::now()->addDays(rand(-10, 15))->toDateString(),
                'start_time' => $start_time,
                'end_time' => Carbon::parse($start_time)->addMinutes(rand(60, 150))->format('H:i'),
                'status' => $statuses[array_rand($statuses)],
                'total' => 0, // Will update after adding details
                'duration' => rand(60, 150),
                'calendar_color' => $colors[array_rand($colors)],
                'notes' => 'Sample booking notes for ' . $customer->first_name,
            ]);

            // Create Booking Details (1-2 items, 1 service each)
            $selectedItems = $customerItems->random(rand(1, min(2, count($customerItems))));
            $bookingTotal = 0;

            foreach ($selectedItems as $item) {

                $service = $allServices->random();

                BookingDetail::create([
                    'booking_id' => $booking->id,
                    'item_id' => $item->id,
                    'service_id' => $service->id,
                    'price' => $service->price,
                    'duration' => $service->duration,
                ]);
                $bookingTotal += $service->price;

            }

            $booking->update(['total' => $bookingTotal]);

        }
    }
}
