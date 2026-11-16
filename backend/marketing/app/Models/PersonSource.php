<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Services\Integrations\Providers;
use Illuminate\Database\Eloquent\Model;

/** One place a person comes from or lives on (a platform, a form, a CSV file…) and their id there. Table: person_sources. */
class PersonSource extends Model
{
    use BelongsToCompany;

    /** Names for the sources that are not a connected platform. */
    public const BUILTIN = ['manual' => 'Added manually', 'import' => 'CSV import', 'form' => 'Online form', 'api' => 'API', 'messenger' => 'Messenger', 'x' => 'X'];

    protected $guarded = ['id', 'company_id'];

    protected $appends = ['platform_label'];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_synced_at' => 'datetime'];
    }

    public function getPlatformLabelAttribute(): string
    {
        return self::labelFor((string) $this->platform);
    }

    public static function labelFor(string $platform): string
    {
        return Providers::find($platform)?->label() ?? self::BUILTIN[$platform] ?? ucfirst($platform);
    }
}
