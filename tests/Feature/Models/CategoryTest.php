<?php

use App\Models\Category;
use App\Models\User;

it('shows global categories to all users', function () {
    $global = Category::factory()->global()->create(['name' => 'Global']);
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(Category::pluck('name'))->toContain('Global');
});

it('shows user own categories', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'My Cat']);

    $this->actingAs($user);

    expect(Category::pluck('name'))->toContain('My Cat');
});

it('hides other users categories', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Category::factory()->for($user1)->create(['name' => 'User1 Cat']);

    $this->actingAs($user2);

    expect(Category::pluck('name'))->not->toContain('User1 Cat');
});

it('auto-sets user_id on create when authenticated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::create([
        'name' => 'Test',
        'color' => '#ff0000',
        'icon' => 'Star',
    ]);

    expect($category->user_id)->toBe($user->id);
});

it('allows explicit null user_id for global categories', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->global()->create(['name' => 'Explicit Global']);

    expect($category->user_id)->toBeNull();
});
