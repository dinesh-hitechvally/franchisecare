<?php

namespace Database\Seeders;

use App\Models\Company;

/** companies */
class CompanySeeder extends DemoSeeder
{
    public function run(): void
    {
        Company::firstOrCreate(['name' => self::COMPANY], [
            'from_name' => 'Demo Company',
            'from_email' => 'hello@demo.test',
            'address' => '1 Example Street, Sydney NSW 2000',
        ]);
    }
}
