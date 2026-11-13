<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Services\Integrations\Providers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A company's connection to an online platform. Its customers are imported as People. */
class Integration extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    /** How often a connection may sync by itself, in minutes (0 = only when asked). */
    public const SYNC_EVERY = [0, 15, 60, 360, 1440];

    protected $guarded = ['id', 'company_id'];

    /** The credentials never leave the server: not in responses, and only the last characters are ever shown (secretHints). */
    protected $hidden = ['secrets'];

    protected function casts(): array
    {
        return ['status' => \App\Casts\Upper::class, 'secrets' => 'encrypted:array', 'settings' => 'array', 'last_synced_at' => 'datetime'];
    }

    public function auditLabel(): string
    {
        return (Providers::find($this->provider)?->label() ?? $this->provider).' · '.$this->name;
    }

    public function syncs()
    {
        return $this->hasMany(IntegrationSync::class);
    }

    /** One setting, with a default. */
    public function setting(string $key, mixed $default = null): mixed
    {
        return ($this->settings ?? [])[$key] ?? $default;
    }

    /** What to show instead of a credential: the last four characters, so a person can tell which one is stored. */
    public function secretHints(): array
    {
        return collect($this->secrets ?? [])->map(fn ($v) => is_string($v) && $v !== '' ? '••••'.mb_substr($v, -4) : null)->all();
    }
}
