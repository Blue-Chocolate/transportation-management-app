<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\{Client, Driver, Vehicle, Trip};
use Illuminate\Database\Seeder;
// use App\Factories\TripFactory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset trip time tracking
        // TripFactory::resetTimeTracking();
        
        // Create companies (admin users)
        $company1 = User::factory()->admin()->create([
            'name' => 'TransCorp Solutions',
            'email' => 'admin@transcorp.com'
        ]);
        
        $company2 = User::factory()->admin()->create([
            'name' => 'City Transport Co',
            'email' => 'admin@citytransport.com'
        ]);
        
        // Create resources for Company 1
        $this->seedCompanyData($company1, 'Company 1');
        
        // Create resources for Company 2  
        $this->seedCompanyData($company2, 'Company 2');
        
        $this->command->info('Database seeded successfully with non-overlapping trips!');
    }
    
    private function seedCompanyData(User $company, string $companyName): void
    {
        $this->command->info("Seeding data for {$companyName}...");
        
        // Create clients for this company
        $clients = Client::factory()->count(5)->forUser($company->id)->create();
        
        // Create drivers for this company
        $drivers = Driver::factory()->count(3)->forUser($company->id)->create();
        
        // Create vehicles for this company
        $vehicles = Vehicle::factory()->count(4)->forUser($company->id)->create();
        
        // Assign vehicles to drivers (many-to-many relationship)
        foreach ($drivers as $driver) {
            $assignedVehicles = $vehicles->random($this->faker->numberBetween(1, 2));
            $driver->vehicles()->attach(
                $assignedVehicles->pluck('id'),
                ['user_id' => $company->id]
            );
        }
        
        // Create non-overlapping trips
        foreach ($drivers as $driver) {
            $driverVehicles = $driver->vehicles;
            
            for ($i = 0; $i < 3; $i++) { // 3 trips per driver
                $vehicle = $driverVehicles->random();
                $client = $clients->random();
                
                Trip::factory()
                    ->forUser($company->id)
                    ->nonOverlapping($driver->id, $vehicle->id)
                    ->create([
                        'client_id' => $client->id,
                        'vehicle_type' => $vehicle->vehicle_type,
                    ]);
            }
        }
        
        $this->command->info("✓ Created for {$companyName}: {$clients->count()} clients, {$drivers->count()} drivers, {$vehicles->count()} vehicles, and trips");
    }
    
    private $faker;
    
    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }
}