<?php

namespace Database\Seeders;

use App\Models\User;

/** users: demo@franchisecare.test / password123 */
class UserSeeder extends DemoSeeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'demo@franchisecare.test'], [
            'name' => 'Demo Owner', 'password' => 'password123', 'company_id' => $this->companyId(),
        ]);
    }
}
