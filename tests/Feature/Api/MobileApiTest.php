<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createMemberUser(): User
{
    Role::create(['name' => 'Library Members', 'guard_name' => 'web', 'type' => 'member']);

    $user = User::factory()->create([
        'status' => 'active',
        'email' => 'member@example.com',
    ]);

    $user->assignRole('Library Members');

    Member::create([
        'user_id' => $user->id,
        'library_card_number' => 'LIB000001',
        'membership_start_date' => now(),
        'membership_expiry_date' => now()->addYear(),
    ]);

    return $user;
}

it('allows members to login via api and receive a token', function () {
    createMemberUser();

    $response = $this->postJson('/api/login', [
        'email' => 'member@example.com',
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'is_member']]);
});

it('rejects invalid api login credentials', function () {
    createMemberUser();

    $this->postJson('/api/login', [
        'email' => 'member@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable();
});

it('returns member dashboard data for authenticated members', function () {
    $user = createMemberUser();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/member/dashboard');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'stats' => ['active_loans', 'overdue_loans', 'available_rooms', 'total_fines'],
            'recent_activities',
            'announcements',
        ]);
});

it('forbids staff users from member api routes', function () {
    Role::create(['name' => 'Admin', 'guard_name' => 'web', 'type' => 'staff']);

    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole('Admin');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/member/dashboard')
        ->assertForbidden();
});

it('requires authentication for member routes', function () {
    $this->getJson('/api/member/dashboard')->assertUnauthorized();
});
