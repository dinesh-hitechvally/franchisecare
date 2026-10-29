<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['settings' => 'encrypted:array'];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /** Read a nested setting, e.g. setting('sms.twilio_sid'). */
    public function setting(string $key, $default = null)
    {
        return data_get($this->settings ?? [], $key, $default);
    }
}
