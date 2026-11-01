<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use App\Services\FormFields;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    protected $guarded = ['id', 'company_id', 'slug', 'views', 'submissions'];

    protected function casts(): array
    {
        return [
            'fields' => 'array', 'style' => 'array', 'list_ids' => 'array', 'tag_ids' => 'array', 'notify_emails' => 'array',
            'active' => 'boolean', 'save_contact' => 'boolean', 'subscribe' => 'boolean', 'autoreply' => 'boolean', 'autoreply_attachments' => 'array',
        ];
    }

    /** Fields in the current builder shape (upgrades forms saved before the builder existed). */
    public function builderFields(): array
    {
        return FormFields::normalize($this->fields);
    }

    public function resolvedStyle(): array
    {
        return FormFields::style($this->style);
    }
}
