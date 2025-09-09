<x-filament::page>
    <div class="space-y-6">
        {{-- Form --}}
        <form wire:submit.prevent="submit">
            {{ $this->form }}
            <x-filament::button type="submit" color="primary">Check Availability</x-filament::button>
        </form>

        @if($start_time && $end_time)
            <div class="grid grid-cols-2 gap-6">
                {{-- Available Drivers --}}
                <div>
                    <h2 class="text-lg font-bold mb-2">Available Drivers</h2>
                    <ul class="list-disc pl-6">
                        @forelse($this->getAvailableDrivers() as $driver)
                            <li>{{ $driver->name }}</li>
                        @empty
                            <li>No drivers available</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Available Vehicles --}}
                <div>
                    <h2 class="text-lg font-bold mb-2">Available Vehicles</h2>
                    <ul class="list-disc pl-6">
                        @forelse($this->getAvailableVehicles() as $vehicle)
                            <li>{{ $vehicle->name }} ({{ $vehicle->registration_number }})</li>
                        @empty
                            <li>No vehicles available</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endif
    </div>
</x-filament::page>
