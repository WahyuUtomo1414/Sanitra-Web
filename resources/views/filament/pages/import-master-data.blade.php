<x-filament-panels::page>
    {{ $this->form }}

    @if ($importResult)
        <x-filament::section>
            <x-slot name="heading">Hasil Import</x-slot>

            <div class="flex gap-6 mb-4">
                <div class="text-sm">
                    <span class="font-semibold text-gray-500">Berhasil:</span>
                    <span class="ml-1 font-bold text-success-600">{{ $importResult['success_count'] }}</span>
                </div>
                <div class="text-sm">
                    <span class="font-semibold text-gray-500">Gagal:</span>
                    <span class="ml-1 font-bold text-danger-600">{{ count($importResult['errors']) }}</span>
                </div>
                <div class="text-sm">
                    <span class="font-semibold text-gray-500">Status:</span>
                    <span class="ml-1 font-bold
                        @if($importResult['status'] === 'done') text-success-600
                        @elseif($importResult['status'] === 'partial') text-warning-600
                        @else text-danger-600
                        @endif">
                        {{ match($importResult['status']) {
                            'done' => 'Selesai',
                            'partial' => 'Sebagian',
                            'failed' => 'Gagal',
                        } }}
                    </span>
                </div>
            </div>

            @if (count($importResult['errors']) > 0)
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-danger-600">Detail kesalahan:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($importResult['errors'] as $error)
                            <li class="text-sm text-danger-700">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
