<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One file in a company's media library. The file itself is in private storage; it is served by its unguessable token. */
class Media extends Model
{
    use Auditable, BelongsToCompany, SoftDeletes;

    protected $table = 'media';

    protected $guarded = ['id', 'company_id', 'token'];

    protected $hidden = ['path', 'token'];

    protected $appends = ['url'];

    public const IMAGES = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    public const DOCUMENTS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'zip'];

    public const MAX_KB = 8192;

    protected function casts(): array
    {
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    /** The public address of the file: no sign-in needed, protected by the long random token. */
    public function getUrlAttribute(): string
    {
        return url('/api/m/'.$this->token.'/'.rawurlencode($this->file_name));
    }

    /** The company's chosen logo (Settings → Company profile) as a public address, or null. */
    public static function logoUrl(Company $company): ?string
    {
        $id = $company->setting('profile.logo_media_id');

        return $id ? self::withoutGlobalScope('company')->where('company_id', $company->id)->where('kind', 'image')->find($id)?->url : null;
    }
}
