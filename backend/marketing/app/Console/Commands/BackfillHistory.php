<?php

namespace App\Console\Commands;

use App\Models;
use App\Services\History;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Gives rows that existed before history was switched on a first copy ("baseline"), so every row has a history. */
class BackfillHistory extends Command
{
    protected $signature = 'history:backfill';

    protected $description = 'Write a baseline history copy for every existing row that has none yet';

    /** source table => model */
    protected const MODELS = [
        'companies' => Models\Company::class,
        'users' => Models\User::class,
        'people' => Models\Person::class,
        'people_lists' => Models\PeopleList::class,
        'tags' => Models\Tag::class,
        'segments' => Models\Segment::class,
        'templates' => Models\Template::class,
        'layouts' => Models\Layout::class,
        'campaigns' => Models\Campaign::class,
        'forms' => Models\Form::class,
        'landing_pages' => Models\LandingPage::class,
        'workflows' => Models\Workflow::class,
        'webhooks' => Models\Webhook::class,
        'person_fields' => Models\PersonField::class,
        'person_field_values' => Models\PersonFieldValue::class,
        'form_submissions' => Models\FormSubmission::class,
        'people_list_members' => Models\PeopleListMember::class,
        'person_tag' => Models\PersonTag::class,
    ];

    public function handle(): int
    {
        foreach (self::MODELS as $table => $class) {
            $hist = History::historyTable($table);
            $pivot = is_subclass_of($class, \Illuminate\Database\Eloquent\Relations\Pivot::class);
            $n = 0;

            DB::table($table)->orderBy($pivot ? DB::raw('1') : 'id')->get()->each(function ($row) use ($class, $hist, $pivot, &$n) {
                $row = (array) $row;
                if ($pivot) {
                    $keys = array_slice($row, 0, 2, true);
                    if (DB::table($hist)->where($keys)->exists()) {
                        return;
                    }
                } elseif (DB::table($hist)->where('id', $row['id'])->exists()) {
                    return;
                }
                $model = (new $class)->forceFill($row);
                $model->exists = true;
                History::record('baseline', $model);
                $n++;
            });

            $this->line(str_pad($table, 24).$n.' baseline copies');
        }

        return self::SUCCESS;
    }
}
