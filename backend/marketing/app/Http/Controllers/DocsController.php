<?php

namespace App\Http\Controllers;

use App\Services\Automation;
use App\Services\FormFields;
use App\Services\History;
use App\Services\MessageRenderer;
use App\Services\WebhookDispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The documentation, as three separate pages and a hub that links to them:
 *   /                  the hub (API clients that ask for JSON still get the small JSON answer)
 *   /docs/manual       the project manual: what each screen does and how to use it
 *   /docs/developer    installation, the code, the data model, operations
 *   /docs/api          API keys (Settings → API Integrations), the integration API, webhooks, the dashboard API
 *
 * The narrative is written by hand (resources/views/docs/); everything that can be read from the app itself (every endpoint,
 * trigger, webhook event, merge tag, field type, table) is generated here, so that part cannot go out of date.
 *
 * On by default except in production (set DOCS_ENABLED=true to publish it there).
 */
class DocsController extends Controller
{
    /** What each part of the API is for. Groups that are not listed here still appear, without a description. */
    protected const GROUPS = [
        'auth' => ['Account', 'Sign up, sign in and sign out.'],
        'me' => ['Account', 'The signed-in user and their company.'],
        'dashboard' => ['Overview', 'Headline numbers for the dashboard.'],
        'people' => ['Audience', 'The people you market to: list, search, filter, create, import, export, bulk actions.'],
        'person-fields' => ['Audience', 'Custom fields: extra data kept on each person.'],
        'lists' => ['Audience', 'Lists that group people.'],
        'tags' => ['Audience', 'Coloured labels for people.'],
        'segments' => ['Audience', 'Saved groups of people that update themselves.'],
        'subscriptions' => ['Audience', 'Who is subscribed, and changing it in bulk.'],
        'campaigns' => ['Campaigns', 'Create, check, send, stop, clone and test email and SMS campaigns.'],
        'reports' => ['Campaigns', 'Per-campaign reports and the delivery list.'],
        'logs' => ['Campaigns', 'Message logs: every email and SMS that was sent or tried.'],
        'templates' => ['Content', 'Reusable starting points for emails and texts.'],
        'forms' => ['Content', 'Sign-up and contact forms, and their submissions.'],
        'submissions' => ['Content', 'Submissions of all forms together.'],
        'landing-pages' => ['Content', 'Hosted web pages.'],
        'layouts' => ['Content', 'Page layouts.'],
        'media' => ['Content', 'The media library: uploads, free online images, logo.'],
        'attachments' => ['Content', 'Files attached to emails.'],
        'workflows' => ['Automation', 'Automations: a trigger followed by steps.'],
        'automation' => ['Automation', 'Triggers and the run history of automations.'],
        'webhooks' => ['Automation', 'Send events to other systems.'],
        'webhook-deliveries' => ['Automation', 'Every webhook delivery and its result.'],
        'analytics' => ['Analytics', 'Reports on email, SMS, delivery, automation and people.'],
        'history' => ['History', 'Who changed what, and when. Read-only versions.'],
        'activity' => ['History', 'The signed-in company’s activity feed.'],
        'config' => ['Settings', 'Company profile, email and SMS services, API keys, users.'],
        'v1' => ['Integration API', 'For other systems: add or look up a person with an API key.'],
        'public' => ['Public', 'Hosted-form submissions, called by visitors’ browsers.'],
        'pages' => ['Public', 'Hosted forms and landing pages.'],
        'tracking' => ['Public', 'Open pixel, click redirect and unsubscribe links in sent messages.'],
    ];

    /** Group headings where the path would mislead (these groups hold routes with different first segments). */
    protected const TITLES = ['auth' => 'sign up and sign in', 'me' => 'current user', 'pages' => 'hosted forms and pages', 'v1' => 'integration API (/api/v1)', 'public' => 'public form submit', 'tracking' => 'tracking and unsubscribe links'];

    /** The tables that are not part of the app’s own data. */
    protected const SKIP_TABLES = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens', 'personal_access_tokens'];

    protected const TABLE_NOTES = [
        'companies' => 'One row per company (tenant): name, sender, settings.',
        'users' => 'People who sign in. A user belongs to one company.',
        'people' => 'The people you market to. Has the single subscription flag.',
        'people_lists' => 'Lists.',
        'people_list_members' => 'Which person is on which list.',
        'person_tag' => 'Which person has which tag.',
        'person_fields' => 'Definitions of custom fields.',
        'person_field_values' => 'The values of custom fields for each person.',
        'tags' => 'Tags.',
        'segments' => 'Saved rules that select people.',
        'templates' => 'Email and SMS templates (with the designer’s JSON).',
        'campaigns' => 'Campaigns, their audience and status.',
        'email_logs' => 'One row per email message.',
        'sms_logs' => 'One row per text message.',
        'click_events' => 'Each click on a tracked link.',
        'forms' => 'Forms and their fields.',
        'form_submissions' => 'Each filled-in form.',
        'landing_pages' => 'Hosted pages.',
        'layouts' => 'Page layouts.',
        'media' => 'Media library files.',
        'workflows' => 'Automations.',
        'workflow_runs' => 'One row per person going through a workflow.',
        'automation_history' => 'Step-by-step log of workflow runs.',
        'webhooks' => 'Webhook endpoints.',
        'webhook_deliveries' => 'Each webhook call and its result.',
        'activity_logs' => 'Who did what in the dashboard (My Activities).',
    ];

    protected function enabled(): bool
    {
        return (bool) config('app.docs_enabled', ! app()->isProduction());
    }

    /** The hub: three cards. API clients that ask for JSON (or a switched-off page) still get the small JSON answer. */
    public function hub(Request $request)
    {
        if (! $this->enabled() || $request->expectsJson()) {
            return response()->json(['app' => 'FranchiseCare Marketing API'] + ($this->enabled() ? ['docs' => url('/')] : []));
        }

        return view('docs.index', ['doc' => 'hub', 'env' => $this->env()]);
    }

    /** The project manual: for the people who use the app. */
    public function manual()
    {
        abort_unless($this->enabled(), 404);

        return view('docs.manual', [
            'doc' => 'manual',
            'env' => $this->env(),
            'triggers' => collect(Automation::TRIGGERS)->groupBy('group', true),
            'tags' => MessageRenderer::TAGS,
            'fieldTypes' => FormFields::types(),
            'formTypes' => FormFields::FORM_TYPES,
        ]);
    }

    /** Installation, the code and how it fits together: for developers. */
    public function developer()
    {
        abort_unless($this->enabled(), 404);

        $tables = $this->tables();
        $endpoints = $this->endpoints();

        return view('docs.developer', [
            'doc' => 'developer',
            'env' => $this->env(),
            'tables' => $tables,
            'historyTypes' => array_keys(History::TYPES),
            'events' => WebhookDispatcher::EVENTS,
            'triggerCount' => count(Automation::TRIGGERS),
            'endpointCount' => collect($endpoints)->sum(fn ($g) => count($g['routes'])),
        ]);
    }

    /** API keys, the integration API, webhooks, public endpoints and the dashboard API. */
    public function api()
    {
        abort_unless($this->enabled(), 404);

        $endpoints = $this->endpoints();

        return view('docs.api', [
            'doc' => 'api',
            'env' => $this->env(),
            'endpoints' => $endpoints,
            'endpointCount' => collect($endpoints)->sum(fn ($g) => count($g['routes'])),
            'events' => WebhookDispatcher::EVENTS,
        ]);
    }

    protected function env(): array
    {
        return ['laravel' => app()->version(), 'php' => PHP_VERSION, 'url' => url('/'), 'generated' => now()->format('Y-m-d H:i').' UTC'];
    }

    /** Every route of the API and the hosted pages, grouped by the part of the app it belongs to. */
    protected function endpoints(): array
    {
        $groups = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            $hosted = Str::startsWith($uri, ['f/', 'p/']);
            if (! $hosted && ! Str::startsWith($uri, 'api/')) {
                continue;
            }
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            $key = $hosted ? 'pages' : $this->groupOf($uri);

            $groups[$key]['name'] = $key;
            $groups[$key]['title'] = self::TITLES[$key] ?? '/api/'.$key;
            $groups[$key]['area'] = self::GROUPS[$key][0] ?? 'Other';
            $groups[$key]['about'] = self::GROUPS[$key][1] ?? '';
            $groups[$key]['routes'][] = [
                'method' => implode('|', $methods),
                'uri' => '/'.$uri,
                'what' => $this->describe($route),
                'auth' => $this->auth($route, $uri, $hosted),
                'limit' => $this->limit($route),
            ];
        }

        $order = array_keys(self::GROUPS);
        uksort($groups, fn ($a, $b) => (array_search($a, $order, true) === false ? 999 : array_search($a, $order, true)) <=> (array_search($b, $order, true) === false ? 999 : array_search($b, $order, true)));

        return array_values($groups);
    }

    protected function groupOf(string $uri): string
    {
        $parts = explode('/', $uri);
        $first = $parts[1] ?? 'api';

        return match ($first) {
            'register', 'login', 'logout' => 'auth',
            't', 'unsubscribe', 'm', 'oauth' => 'tracking',
            default => $first,
        };
    }

    protected function describe(LaravelRoute $route): string
    {
        $action = $route->getActionName();
        if (! str_contains($action, '@')) {
            return $action === 'Closure' ? 'Closure' : Str::afterLast($action, '\\');
        }
        [$class, $method] = explode('@', $action);
        $named = ['index' => 'List', 'store' => 'Create', 'show' => 'Show', 'update' => 'Update', 'destroy' => 'Delete'];

        return ($named[$method] ?? Str::headline($method)).' · '.Str::replaceLast('Controller', '', Str::afterLast($class, '\\'));
    }

    protected function auth(LaravelRoute $route, string $uri, bool $hosted): string
    {
        if ($hosted) {
            return 'public';
        }
        $middleware = implode(' ', $route->gatherMiddleware());
        if (str_contains($middleware, 'auth:sanctum')) {
            return Str::startsWith($uri, 'api/v1/') ? 'api key' : 'token';
        }

        return 'public';
    }

    protected function limit(LaravelRoute $route): ?string
    {
        foreach ($route->gatherMiddleware() as $m) {
            if (preg_match('/(?:throttle|ThrottleRequests):(\d+),(\d+)/i', (string) $m, $x)) {
                return $x[1].'/min';
            }
        }

        return null;
    }

    /** The app’s own tables (not the history mirrors, not the framework’s). */
    protected function tables(): array
    {
        $db = DB::getDatabaseName();
        $names = collect(Schema::getTables())
            ->filter(fn ($t) => ! isset($t['schema']) || in_array($t['schema'], [$db, 'main', 'public'], true))
            ->pluck('name')->unique()->values();

        $histories = $names->filter(fn ($n) => Str::endsWith($n, '_history') && $n !== 'automation_history')->count();
        $own = $names->reject(fn ($n) => in_array($n, self::SKIP_TABLES, true) || (Str::endsWith($n, '_history') && $n !== 'automation_history'))->sort()->values();

        return [
            'tables' => $own->map(fn ($n) => ['name' => $n, 'note' => self::TABLE_NOTES[$n] ?? ''])->all(),
            'histories' => $histories,
        ];
    }
}
