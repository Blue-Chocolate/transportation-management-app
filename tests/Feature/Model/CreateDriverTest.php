<?php

use App\Models\Driver;

it('driver factory creates a valid driver', function () {
    $driver = Driver::factory()->create();
    expect($driver)->toBeInstanceOf(Driver::class)
        ->and($driver->name)->not()->toBeEmpty()
        ->and($driver->user_id)->not()->toBeNull();
});
