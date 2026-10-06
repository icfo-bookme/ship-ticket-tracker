<?php

test('operations guide renders with its section navigation', function () {
    $user = \App\Models\User::factory()->create();
    $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('documentation.view', 'web'));
    $this->actingAs($user);

    $response = $this->get('/documentation');

    $response->assertOk()
        ->assertSee('Ship Booking User Guide')
        ->assertSee('href="#manual-purpose"', false)
        ->assertSee('id="manual-purpose"', false)
        ->assertSee('id="cash-collection"', false)
        ->assertSee('Collected', false);
});
