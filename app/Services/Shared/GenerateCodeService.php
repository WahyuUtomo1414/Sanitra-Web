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

    /**
     * Turunkan `last_number` di `sys_code_sequence` **hanya** kalau nomor di `$code`
     * benar-benar nomor terakhir untuk (prefix, year, month). Dipakai saat Draft
     * di-discard (force-delete) supaya kodenya bisa dipakai ulang. Tidak memaksa
     * decrement kalau kode yang dihapus bukan nomor terakhir — mencegah tabrakan
     * dengan record lain di periode yang sama.
     */
    public function releaseIfLatest(AutoCodePrefix $prefix, string $code): bool
    {
        return DB::transaction(function () use ($prefix, $code): bool {
            $parsed = $this->parseCode($prefix, $code);

            $sequence = CodeSequence::where('prefix', $prefix->value)
                ->where('year', $parsed['year'])
                ->where('month', $parsed['month'])
                ->lockForUpdate()
                ->first();

            if (! $sequence || $sequence->last_number !== $parsed['number']) {
                return false;
            }

            $sequence->decrement('last_number');

            return true;
        });
    }

    /**
     * @return array{year: ?int, month: ?int, number: int}
     */
    private function parseCode(AutoCodePrefix $prefix, string $code): array
    {
        $format = $prefix->format();
        $prefixValue = preg_quote($prefix->value, '#');

        if ($format === CodeFormat::Transaction
            && preg_match('#^'.$prefixValue.'/(\d{2})/(\d{2})/(\d{4})$#', $code, $m)) {
            return [
                'year' => 2000 + (int) $m[1],
                'month' => (int) $m[2],
                'number' => (int) $m[3],
            ];
        }

        if ($format === CodeFormat::Master
            && preg_match('#^'.$prefixValue.'(\d{4})$#', $code, $m)) {
            return [
                'year' => null,
                'month' => null,
                'number' => (int) $m[1],
            ];
        }

        throw new \DomainException("Format kode tidak dikenal untuk prefix {$prefix->value}: {$code}");
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
