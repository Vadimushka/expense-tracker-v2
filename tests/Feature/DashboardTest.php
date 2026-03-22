<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/summary');
    $response->assertRedirect(route('login'));
});

test('dashboard redirects to summary', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertRedirect('/summary');
});
