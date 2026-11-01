<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * One-off repair for databases written before the MySQL connection was pinned to UTC.
 *
 * PHP always produced UTC strings, but MySQL read them in the *server's* time zone, so every TIMESTAMP column holds an
 * instant shifted by the server's offset. With the connection now on UTC those values read wrongly; this converts each
 * of them back to what PHP meant. Run once, after the "timezone" => "+00:00" setting is in place.
 */
class FixUtcTimestamps extends Command
{
    protected $signature = 'timestamps:fix-utc {--dry-run : only show what would change} {--force : run again even though it already ran}';

    protected $description = 'Convert TIMESTAMP values written under a non-UTC MySQL session so they are true UTC';

    /** Written by MySQL itself (DEFAULT CURRENT_TIMESTAMP), so already correct. */
    protected const SKIP = ['click_events.created_at'];

    public function handle(): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->info('Not a MySQL database: nothing to do.');

            return self::SUCCESS;
        }
        $marker = storage_path('app/private/timestamps-fixed-utc.txt');
        if (File::exists($marker) && ! $this->option('force')) {
            $this->warn('Already done on '.trim(File::get($marker)).'. Use --force to run again (only if you are sure).');

            return self::FAILURE;
        }
        if (DB::selectOne('select @@session.time_zone tz')->tz !== '+00:00') {
            $this->error('The connection is not on UTC. Set "timezone" => "+00:00" for the mysql connection first.');

            return self::FAILURE;
        }

        $system = DB::selectOne('select @@system_time_zone tz')->tz;
        $this->line("Server time zone: {$system}. Converting values that were stored as if the server clock were UTC.");

        $cols = DB::select("select table_name t, column_name c from information_schema.columns where table_schema = database() and data_type = 'timestamp' order by table_name, ordinal_position");
        $byTable = [];
        foreach ($cols as $col) {
            if (! in_array("{$col->t}.{$col->c}", self::SKIP, true)) {
                $byTable[$col->t][] = $col->c;
            }
        }

        $dry = (bool) $this->option('dry-run');
        DB::transaction(function () use ($byTable, $dry) {
            foreach ($byTable as $table => $columns) {
                $rows = DB::table($table)->count();
                $set = implode(', ', array_map(fn ($c) => "`{$c}` = CONVERT_TZ(`{$c}`, '+00:00', 'SYSTEM')", $columns));
                if (! $dry && $rows) {
                    DB::update("update `{$table}` set {$set}");
                }
                $this->line(str_pad($table, 34).str_pad((string) $rows, 7).' rows, '.count($columns).' columns');
            }
        });

        if ($dry) {
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }
        File::put($marker, now()->toDateTimeString().' UTC');
        $this->info('Done. Every timestamp is now stored in UTC.');

        return self::SUCCESS;
    }
}
