<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** A custom data field a company keeps on its contacts (e.g. "Company name", "Plan"). */
class ContactField extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    public const TYPES = ['text', 'textarea', 'number', 'date', 'dropdown', 'radio', 'checkbox'];

    protected $guarded = ['id', 'company_id', 'key'];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    public function values()
    {
        return $this->hasMany(ContactFieldValue::class);
    }

    /** Null when $value is acceptable for this field, otherwise a message saying why not. */
    public function problemWith(string $value): ?string
    {
        return match ($this->type) {
            'number' => is_numeric($value) ? null : "{$this->label} must be a number.",
            'date' => strtotime($value) !== false ? null : "{$this->label} must be a date.",
            'dropdown', 'radio' => in_array($value, $this->options ?? [], true) ? null : "{$this->label} must be one of the listed options.",
            'checkbox' => in_array($value, ['Yes', 'No'], true) ? null : "{$this->label} must be Yes or No.",
            default => mb_strlen($value) > 2000 ? "{$this->label} is too long." : null,
        };
    }
}
