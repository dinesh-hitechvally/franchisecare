<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use SoftDeletes, Auditable;

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
