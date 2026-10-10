<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

abstract class SanitraSeeder extends Seeder
{
    public function __construct(protected readonly GenerateCodeService $codeService) {}

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $modelClass
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    protected function coded(string $modelClass, AutoCodePrefix $prefix, array $identity, array $attributes): Model
    {
        return $modelClass::withTrashed()->where($identity)->first()
            ?? $modelClass::create([...$identity, ...$attributes, 'code' => $this->codeService->next($prefix)]);
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $modelClass
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    protected function uncoded(string $modelClass, array $identity, array $attributes): Model
    {
        return $modelClass::withTrashed()->where($identity)->first()
            ?? $modelClass::create([...$identity, ...$attributes]);
    }
}
