<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createSuperAdminUser(): User
{
    Permission::firstOrCreate(['name' => 'manage roles']);
    $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $role->givePermissionTo('manage roles');

    $user = User::factory()->create([
        'status' => 'active',
        'email' => 'superadmin@example.com',
    ]);
    $user->assignRole('Super Admin');

    return $user;
}

test('guests are redirected to the login page from admin users route', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('users without manage roles permission cannot access admin users list', function () {
    $user = User::factory()->create(['status' => 'active']);
    $this->actingAs($user);

    $this->get(route('admin.users.index'))->assertForbidden();
});

test('super admin with manage roles permission can view users list', function () {
    $superAdmin = createSuperAdminUser();
    $this->actingAs($superAdmin);

    // Create a few other users to list
    User::factory()->count(3)->create();

    $response = $this->get(route('admin.users.index'));
    $response->assertStatus(200);
});

test('super admin can view a single user detail page', function () {
    $superAdmin = createSuperAdminUser();
    $this->actingAs($superAdmin);

    $member = User::factory()->create();

    $response = $this->get(route('admin.users.show', $member));
    $response->assertStatus(200);
});

test('super admin can toggle user status', function () {
    $superAdmin = createSuperAdminUser();
    $this->actingAs($superAdmin);

    $member = User::factory()->create(['status' => 'active']);

    $response = $this->patch(route('admin.users.toggle-status', $member));
    $response->assertRedirect();
    expect($member->fresh()->status)->toBe('inactive');

    $this->patch(route('admin.users.toggle-status', $member));
    expect($member->fresh()->status)->toBe('active');
});

test('super admin can restore soft deleted user', function () {
    $superAdmin = createSuperAdminUser();
    $this->actingAs($superAdmin);

    $member = User::factory()->create(['status' => 'active']);
    $member->delete(); // Soft delete

    expect($member->fresh()->deleted_at)->not->toBeNull(); // In Laravel 11, fresh() on a soft-deleted model returns the model with deleted_at set.

    $response = $this->patch(route('admin.users.restore', $member->id));
    $response->assertRedirect();
    expect($member->fresh()->deleted_at)->toBeNull();
    expect($member->fresh()->status)->toBe('active');
});

test('super admin can permanently force delete a user', function () {
    $superAdmin = createSuperAdminUser();
    $this->actingAs($superAdmin);

    $member = User::factory()->create();
    $member->delete(); // Soft delete first

    $response = $this->delete(route('admin.users.force-delete', $member->id));
    $response->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});
