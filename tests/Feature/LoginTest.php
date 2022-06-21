<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

const LOGIN_URL = '/api/auth/login';

afterEach(function(){
    $user = User::firstWhere('email', 'test@test.com');
    if ($user) {
        $user->tokens()->delete();
        $user->forceDelete();
    }
});

it('Should return 405 other than POST method', function () {
    
    $this->get(LOGIN_URL)->assertStatus(405);
    $this->put(LOGIN_URL)->assertStatus(405);
    $this->delete(LOGIN_URL)->assertStatus(405);
});

it('Return 200 when succesful login', function () {
        
    $user = User::factory()->create([
        'email' => 'test@test.com',
        'password' => Hash::make('test12345'),
    ]);

    $response = $this->postJson(LOGIN_URL, [
        'email' => 'test@test.com',
        'password' => 'test12345',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', [
        'email' => 'test@test.com',
    ]);
});

it('Return 401 when login failed', function() {
    $response = $this->postJson(LOGIN_URL, [
        'email' => 'test@test.com',
        'password' => 'test12345',
    ]);

    $response->assertStatus(401);
});

it("Return user's token when success", function() {
    $user = User::factory()->create([
        'email' => 'test@test.com',
        'password' => Hash::make('test12345'),
    ]);

    $response = $this->postJson(LOGIN_URL, [
        'email' => 'test@test.com',
        'password' => 'test12345',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['token']);
});

it("Return user's name when success", function(){
    $user = User::factory()->create([
        'email' => 'test@test.com',
        'password' => Hash::make('test12345'),
        'name' => 'test',
    ]);

    $response = $this->postJson(LOGIN_URL, [
        'email' => 'test@test.com',
        'password' => 'test12345',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('name', 'test');
});

it("Return user's role when success" , function(){
    $user = User::factory()->create([
        'email' => 'test@test.com',
        'password' => Hash::make('test12345'),
        'name' => 'test',
    ]);

    $user->assignRole('siswa');

    $response = $this->postJson(LOGIN_URL, [
        'email' => 'test@test.com',
        'password' => 'test12345',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('role', 'siswa');
});
