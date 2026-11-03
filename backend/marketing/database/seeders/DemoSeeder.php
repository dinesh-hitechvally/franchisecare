<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

/** Base of the demo-data seeders: each subclass fills ONE table, for the demo company, and is safe to re-run. */
abstract class DemoSeeder extends Seeder
{
    public const COMPANY = 'Demo Company';

    protected function companyId(): int
    {
        return (int) Company::where('name', self::COMPANY)->value('id');
    }
}
