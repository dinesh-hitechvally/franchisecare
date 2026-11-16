<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** One line of a person's sync history: arrived from a platform, or was sent to one. Table: person_sync_events. */
class PersonSyncEvent extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    protected $guarded = ['id', 'company_id'];

    protected $appends = ['platform_label'];

    protected function casts(): array
    {
        return ['direction' => \App\Casts\Upper::class, 'action' => \App\Casts\Upper::class];
    }

    public function getPlatformLabelAttribute(): string
    {
        return PersonSource::labelFor((string) $this->platform);
    }
}
