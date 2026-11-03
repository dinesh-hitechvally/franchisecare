<?php

namespace App\Http\Controllers\Concerns;

/** Small helpers the report controllers share. */
trait ReportMath
{
    protected function pct(int|float $a, int|float $b): float
    {
        return $b > 0 ? round($a / $b * 100, 1) : 0.0;
    }

    /** Fill a {date => count} map into a continuous list of the last $days days. */
    protected function series($rows, int $days): array
    {
        $map = collect($rows)->pluck('c', 'd');

        return collect(range($days - 1, 0))->map(fn ($i) => [
            'date' => ($d = now()->subDays($i)->toDateString()),
            'value' => (int) ($map[$d] ?? 0),
        ])->all();
    }
}
