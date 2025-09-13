<x-filament::page>
    <div class="space-y-6">
        {{-- Form --}}
        <form wire:submit.prevent="submit" class="space-y-4">
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
                            <li>{{ $driver->name }} (ID: {{ $driver->id }})</li>
                        @empty
                            <li>No drivers available for {{ $name ? "name '$name' in " : '' }}time range</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Available Vehicles --}}
                <div>
                    <h2 class="text-lg font-bold mb-2">Available Vehicles</h2>
                    <ul class="list-disc pl-6">
                        @forelse($this->getAvailableVehicles() as $vehicle)
                            <li>{{ $vehicle->name }} ({{ $vehicle->registration_number }}) (ID: {{ $vehicle->id }})</li>
                        @empty
                            <li>No vehicles available for {{ $name ? "name '$name' in " : '' }}time range</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @else
            <p class="text-gray-500">Please select a time range and optionally a name to check availability.</p>
        @endif
    </div>
</x-filament::page>