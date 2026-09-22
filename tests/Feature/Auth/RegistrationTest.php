<?php

test('registration is disabled: /register redirects to login', function () {
    $response = $this->get('/register');

    $response->assertRedirect(route('login', absolute: false));
});

test('registration POST endpoint is closed', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Only a GET redirect route exists for /register now.
    $response->assertStatus(405);
    $this->assertGuest();
});
