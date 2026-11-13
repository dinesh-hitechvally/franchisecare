<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\IntegrationSync;
use App\Models\PeopleList;
use App\Services\Integrations\IntegrationException;
use App\Services\Integrations\MessagingProvider;
use App\Services\Integrations\Provider;
use App\Services\Integrations\Providers;
use App\Services\Integrations\Syncs;
use App\Services\Integrations\WordPressProvider;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Connections to online platforms (Shopify, WooCommerce, WordPress, Magento) whose customers become People. */
class IntegrationController extends Controller
{
    /** The platforms that can be connected, with the form to fill in and how to get the details. */
    public function providers()
    {
        return collect(Providers::all())->map(fn (Provider $p) => $p->describe())->values();
    }

    /** The connections. With include_disconnected=1 the ones that were disconnected are listed too (after the others), so they can be found and connected again. */
    public function index(Request $request)
    {
        return Integration::when($request->boolean('include_disconnected'), fn ($q) => $q->withTrashed())
            ->orderByRaw('deleted_at is not null')->latest()->get()->map(fn (Integration $i) => $this->present($i))->values();
    }

    public function store(Request $request)
    {
        $provider = Providers::find($request->input('provider'));
        abort_unless($provider, 422, 'Choose a platform to connect.');
        $data = $request->validate($this->rules($provider, true));

        $integration = new Integration(['provider' => $provider->key(), 'settings' => $this->settings($provider, $data)]);
        $this->applyConnection($integration, $provider, $data);
        $this->ensureNew($integration, $provider);
        $detected = $this->test($integration, $provider);
        $integration->forceFill(['name' => trim((string) ($data['name'] ?? '')) ?: $detected, 'status' => 'connected', 'last_error' => null])->save();

        $imports = $request->boolean('import_now', true) && ! ($provider instanceof MessagingProvider); // a messaging account has no customers to import
        if ($imports) {
            Syncs::start($integration, 'connect', true);
        }

        return ApiResponse::created($this->present($integration->fresh()), $imports ? 'Connected. Importing customers now.' : 'Connected.');
    }

    public function update(Request $request, Integration $integration)
    {
        $provider = Providers::find($integration->provider);
        abort_unless($provider, 422, 'This platform is no longer supported.');
        $data = $request->validate($this->rules($provider, false));

        $integration->settings = $this->settings($provider, $data, $integration->settings ?? []);
        if (! empty($data['name'])) {
            $integration->name = $data['name'];
        }
        // new credentials or a new address: prove they work before replacing the old ones
        if ($this->changesConnection($provider, $data)) {
            $this->applyConnection($integration, $provider, $data);
            $this->ensureNew($integration, $provider, $integration->id);
            $this->test($integration, $provider);
            $integration->status = 'connected';
            $integration->last_error = null;
        }
        $integration->save();

        return $this->present($integration->fresh());
    }

    public function destroy(Integration $integration)
    {
        // the people that were imported stay: only the link is removed
        IntegrationSync::where('integration_id', $integration->id)->where('status', 'RUNNING')->update(['status' => 'FAILED', 'error' => 'Disconnected.', 'finished_at' => now()]);
        $integration->forceFill(['secrets' => []])->save(); // the credentials are not kept once it is disconnected
        $integration->delete();

        return response()->noContent();
    }

    /** Checks the connection again (for example after a password changed). */
    public function check(Integration $integration)
    {
        $provider = Providers::find($integration->provider);
        abort_unless($provider, 422, 'This platform is no longer supported.');

        try {
            $integration->forceFill(['name' => $provider->test($integration), 'status' => 'connected', 'last_error' => null])->save();
        } catch (IntegrationException $e) {
            $integration->forceFill(['status' => 'error', 'last_error' => $e->getMessage()])->save();
            abort(422, $e->getMessage());
        }

        return ApiResponse::ok($this->present($integration->fresh()), 'The connection works.');
    }

    /** Imports now. Only customers changed since the last sync, unless full=true. */
    public function sync(Request $request, Integration $integration)
    {
        [$sync, $started] = Syncs::start($integration, 'manual', $request->boolean('full'));

        return ApiResponse::accepted($started ? 'Import started.' : 'An import is already running for this connection.', $sync);
    }

    /** The import runs of one connection, newest first. */
    public function syncs(Integration $integration)
    {
        return IntegrationSync::where('integration_id', $integration->id)->latest('id')->paginate(20);
    }

    /** The import runs of all connections, with filters. */
    public function allSyncs(Request $request)
    {
        $page = IntegrationSync::with('integration:id,name,provider,store_url')
            ->when($request->query('integration_id'), fn ($q, $v) => $q->where('integration_id', $v))
            ->when(in_array($request->query('status'), ['running', 'success', 'failed'], true), fn ($q) => $q->where('status', strtoupper($request->query('status'))))
            ->latest('id')->paginate(min((int) $request->query('per_page', 20), 100));
        $page->getCollection()->each(fn ($s) => $s->integration?->setAttribute('provider_label', Providers::find($s->integration->provider)?->label()));

        return $page;
    }

    // ------------------------------------------------------------------

    /** Validation: the platform's own form fields, and the import settings. */
    public function rules(Provider $provider, bool $creating): array
    {
        $rules = [
            'name' => 'nullable|string|max:120',
            'list_id' => 'nullable|integer',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:60',
            'subscribe_mode' => ['nullable', Rule::in(['consent', 'all'])],
            'confirm_permission' => 'nullable|boolean|accepted_if:subscribe_mode,all', // "everyone" is the person's word that they may email them
            'sync_every' => ['nullable', 'integer', Rule::in(Integration::SYNC_EVERY)],
            'run_automations' => 'nullable|boolean',
            'direction' => ['nullable', Rule::in(['import', 'export', 'both'])],
            'export_list_id' => 'nullable|integer',
            'roles' => 'nullable|array',
            'roles.*' => ['string', Rule::in(array_keys(WordPressProvider::ROLES))],
            'import_now' => 'nullable|boolean',
        ];
        foreach ($provider->fields() as $f) {
            $rules[$f['key']] = ($creating && ($f['required'] ?? true) ? 'required' : 'nullable').'|string|max:500';
        }

        return $rules;
    }

    /** The import settings: what was typed now, else what is already stored, else the defaults. */
    public function settings(Provider $provider, array $data, array $existing = []): array
    {
        $list = $data['list_id'] ?? null;
        abort_if($list && ! PeopleList::whereKey($list)->exists(), 422, 'That list does not exist.');

        $settings = array_filter([
            'tags' => array_key_exists('tags', $data) ? array_values(array_unique(array_filter(array_map('trim', (array) $data['tags'])))) : null,
            'subscribe_mode' => $data['subscribe_mode'] ?? null,
            'sync_every' => $data['sync_every'] ?? null,
            'run_automations' => $data['run_automations'] ?? null,
            'roles' => $provider->key() === 'wordpress' ? ($data['roles'] ?? null) : null,
            'direction' => $provider->supportsExport() ? ($data['direction'] ?? null) : null,
        ], fn ($v) => $v !== null);
        if ($provider->supportsExport() && array_key_exists('export_list_id', $data)) {
            abort_if($data['export_list_id'] && ! PeopleList::whereKey($data['export_list_id'])->exists(), 422, 'That list does not exist.');
            $settings['export_list_id'] = $data['export_list_id'] ?: null; // empty = everyone
        }

        if (array_key_exists('list_id', $data)) {
            $settings['list_id'] = $list ?: null; // an empty choice clears the list
        }
        $config = [];
        foreach ($provider->fields() as $f) {
            if (! $f['secret'] && $f['key'] !== 'url' && array_key_exists($f['key'], $data)) {
                $config[$f['key']] = trim((string) $data[$f['key']]);
            }
        }
        if ($config) {
            $settings['config'] = $config + ($existing['config'] ?? []);
        }

        $final = $settings + $existing + ['subscribe_mode' => 'consent', 'sync_every' => 60, 'run_automations' => false];
        abort_if(
            $provider->supportsExport() && in_array($final['direction'] ?? 'import', ['export', 'both'], true) && blank($final['config']['audience_id'] ?? null),
            422, 'To send people to '.$provider->label().', choose the audience to send them to (Audience ID).',
        );

        return $final;
    }

    protected function changesConnection(Provider $provider, array $data): bool
    {
        return collect($provider->fields())->contains(fn ($f) => filled($data[$f['key']] ?? null));
    }

    /** Puts the typed address and credentials on the connection (blank fields keep what is stored). */
    protected function applyConnection(Integration $integration, Provider $provider, array $data): void
    {
        try {
            if (filled($data['url'] ?? null)) {
                $integration->store_url = $provider->normalizeUrl($data['url']);
            }
        } catch (IntegrationException $e) {
            abort(422, $e->getMessage());
        }
        $secrets = $integration->secrets ?? [];
        foreach ($provider->fields() as $f) {
            if ($f['secret'] && filled($data[$f['key']] ?? null)) {
                $secrets[$f['key']] = trim($data[$f['key']]);
            }
        }
        $integration->secrets = $secrets;

        if (! $provider->usesUrl()) {
            try {
                $integration->store_url = $provider->baseUrl($integration);
            } catch (IntegrationException $e) {
                abort(422, $e->getMessage());
            }
        }
    }

    /** The same store or account cannot be connected twice (two different ones of the same platform can). */
    protected function ensureNew(Integration $integration, Provider $provider, ?int $exceptId = null): void
    {
        $identity = $provider->identity($integration);
        $same = Integration::where('provider', $provider->key())->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->get()
            ->first(fn (Integration $other) => $provider->identity($other) === $identity);

        abort_if($same, 422, 'This '.$provider->label().($provider->usesUrl() ? ' store' : ' account').' is already connected as “'.$same?->name.'”. Open its Settings to change it, or connect a different one.');
    }

    /** Asks the platform to confirm the connection; its own words become the answer when it will not. Returns the store's name. */
    protected function test(Integration $integration, Provider $provider): string
    {
        try {
            return $provider->test($integration);
        } catch (IntegrationException $e) {
            abort(422, $e->getMessage());
        }
    }

    protected function present(Integration $i): array
    {
        $provider = Providers::find($i->provider);
        $last = IntegrationSync::where('integration_id', $i->id)->latest('id')->first();
        $running = Syncs::running($i);

        return [
            'id' => $i->id, 'provider' => $i->provider, 'provider_label' => $provider?->label() ?? $i->provider, 'name' => $i->name, 'store_url' => $i->store_url,
            'category' => $provider?->category(), 'channel' => $provider instanceof MessagingProvider ? $provider->channel() : null, 'uses_url' => $provider?->usesUrl() ?? true, 'status' => $i->trashed() ? 'disconnected' : $i->status, 'disconnected_at' => $i->deleted_at, 'last_error' => $i->trashed() ? null : $i->last_error, 'last_synced_at' => $i->last_synced_at,
            'settings' => $i->settings, 'secret_hints' => $i->secretHints(), 'running' => (bool) $running,
            'last_sync' => $last, 'imported' => (int) IntegrationSync::where('integration_id', $i->id)->sum('created'), 'created_at' => $i->created_at,
        ];
    }
}
