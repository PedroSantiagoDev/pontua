<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('allows admin to access the list page', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertOk();
});

it('prevents manager from accessing the list page', function () {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    Livewire::test(ListUsers::class)
        ->assertForbidden();
});

it('prevents employee from accessing the list page', function () {
    $employee = User::factory()->employee()->create();

    actingAs($employee);

    Livewire::test(ListUsers::class)
        ->assertForbidden();
});

it('displays users in the table', function () {
    $admin = User::factory()->admin()->create();
    $users = User::factory()->count(3)->create();

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('can search users by name', function () {
    $admin = User::factory()->admin()->create();
    $users = User::factory()->count(5)->create();

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->where('name', $users->first()->name))
        ->assertCanNotSeeTableRecords($users->where('name', '!=', $users->first()->name));
});

it('can search users by email', function () {
    $admin = User::factory()->admin()->create();
    $users = User::factory()->count(5)->create();

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->searchTable($users->first()->email)
        ->assertCanSeeTableRecords($users->where('email', $users->first()->email));
});

it('allows admin to access the create page', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    Livewire::test(CreateUser::class)
        ->assertOk();
});

it('can create a user', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    $newUser = User::factory()->make();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => $newUser->name,
            'email' => $newUser->email,
            'password' => 'password123',
            'role' => UserRole::Manager->value,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => $newUser->name,
        'email' => $newUser->email,
        'role' => UserRole::Manager->value,
    ]);
});

it('validates required fields on create', function (array $data, array $errors) {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    $newUser = User::factory()->make();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => $newUser->name,
            'email' => $newUser->email,
            'password' => 'password123',
            'role' => UserRole::Employee->value,
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`email` is required' => [['email' => null], ['email' => 'required']],
    '`email` must be valid' => [['email' => 'invalid'], ['email' => 'email']],
    '`password` is required on create' => [['password' => null], ['password' => 'required']],
    '`role` is required' => [['role' => null], ['role' => 'required']],
]);

it('validates unique email on create', function () {
    $admin = User::factory()->admin()->create();
    $existingUser = User::factory()->create();

    actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test',
            'email' => $existingUser->email,
            'password' => 'password123',
            'role' => UserRole::Employee->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});

it('allows admin to access the edit page', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->assertOk();
});

it('can update a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => UserRole::Manager->value,
        ])
        ->call('save')
        ->assertNotified();

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('updated@example.com')
        ->and($user->role)->toBe(UserRole::Manager);
});

it('allows updating without changing password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $originalPassword = $user->password;

    actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'name' => 'Updated Name',
            'email' => $user->email,
            'password' => '',
            'role' => $user->role->value,
        ])
        ->call('save')
        ->assertNotified();

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->password)->toBe($originalPassword);
});

it('can delete a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->callAction('delete')
        ->assertNotified()
        ->assertRedirect();

    expect(User::find($user->id))->toBeNull();
});

it('prevents admin from deleting themselves', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $admin->id])
        ->assertActionHidden('delete');
});
