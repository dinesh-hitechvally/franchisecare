<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CancellationPoliciesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cancellation_policies')->insert([
            'name' => 'Generic Policy',
            'description' => "<p>We're so looking forward to seeing you! To help keep our schedule running smoothly for everyone {{timeTemplate}}</p><p>All your appointment time is reserved exclusively for you, so:</p><ul><li>cancellations or reschedules are made when we arrive</li><li>no-one is home when we arrive</li><li>we're unable to access the property when we arrive</li></ul><p>a cancellation fee {{feeTemplate}} will apply.</p>",
        ]);
    }
}
