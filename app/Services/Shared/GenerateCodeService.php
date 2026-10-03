<?php

namespace App\Services\Shared;

use App\Enums\AutoCodePrefix;
use App\Enums\CodeFormat;
use App\Models\CodeSequence;
use Illuminate\Support\Facades\DB;

class GenerateCodeService
{
    public function next(AutoCodePrefix $prefix): string
    {
        return DB::transaction(function () use ($prefix) {
            $format = $prefix->format();

            [$year, $month] = $format === CodeFormat::Transaction
                ? [(int) now()->format('Y'), (int) now()->format('n')]
                : [null, null];

            $sequence = $this->lockOrCreateSequence($prefix->value, $year, $month);
            $sequence->increment('last_number');

            return $this->formatCode($format, $prefix->value, $year, $month, $sequence->last_number);
        });
    }

    protected function lockOrCreateSequence(string $prefix, ?int $year, ?int $month): CodeSequence
    {
        $sequence = CodeSequence::where('prefix', $prefix)
            ->where('year', $year)
            ->where('month', $month)
            ->lockForUpdate()
            ->first();

        if ($sequence) {
            return $sequence;
        }

        return CodeSequence::create([
            'prefix' => $prefix,
            'year' => $year,
            'month' => $month,
            'last_number' => 0,
        ]);
    }

    protected function formatCode(CodeFormat $format, string $prefix, ?int $year, ?int $month, int $number): string
    {
        return match ($format) {
            CodeFormat::Master => sprintf('%s%04d', $prefix, $number),
            CodeFormat::Transaction => sprintf(
                '%s/%s/%02d/%04d',
                $prefix,
                substr((string) $year, -2),
                $month,
                $number,
            ),
        };
    }
}
