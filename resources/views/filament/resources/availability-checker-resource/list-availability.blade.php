<x-filament::page>
    <div class="space-y-6">
        {{ $this->table }}

        @if($start_time && $end_time)
            <div class="mt-6">
                <h2 class="text-lg font-bold mb-2">Available Vehicles</h2>
                <ul class="list-disc pl-6">
                    @forelse($availableVehicles as $vehicle)
                        <li>{{ $vehicle->name }} ({{ $vehicle->registration_number }})</li>
                    @empty
                        <li>No vehicles available</li>
                    @endforelse
                </ul>
            </div>
        @endif
    </div>
</x-filament::page>