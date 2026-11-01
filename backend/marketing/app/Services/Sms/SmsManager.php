<?php

namespace App\Services\Sms;

use App\Models\Company;
use App\Services\SmsSettings;

class SmsManager
{
    /** The driver of the one SMS service that is in use (Log only: written to the log, nothing is sent). */
    public function forCompany(Company $company): SmsDriver
    {
        $active = SmsSettings::active($company);

        return $active ? SmsProviders::driver($active['key'], $active['conn']) : new LogDriver;
    }
}
