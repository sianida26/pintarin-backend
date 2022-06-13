<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

// uses(RefreshDatabase::class);

const REGISTER_URL = '/api/auth/register';

// beforeEach(fn () => $this->seed());

afterEach(function(){
    $user = User::firstWhere('email', 'test@test.com');
    if ($user) {
        $user->tokens()->delete();
        $user->forceDelete();
    }
});

it('Should return 405 other than POST method', function () {
    
    $this->get(REGISTER_URL)->assertStatus(405);
    $this->put(REGISTER_URL)->assertStatus(405);
    $this->delete(REGISTER_URL)->assertStatus(405);
});


it('Should return 200 if success', function() {

    $response = $this->postJson(REGISTER_URL, [
        'name' => 'test',
        'email' => 'test@test.com',
        'TTL' => 'Malang, 8 September 2022',
        'role' => 'Siswa',
        'password' => 'test12345',
        'password_confirmation' => 'test12345'
    ]);
    
    $response->assertStatus(200);
    $this->assertDatabaseHas('users', [
        'email' => 'test@test.com',
    ]);
});

it('Should return "Email telah terdaftar" when email already exists', function () {
    $user = User::factory()->create([
        'email' => 'test@test.com'
    ]);

    $response = $this->postJson(REGISTER_URL, [
        'name' => 'test',
        'email' => 'test@test.com',
        'TTL' => 'Malang, 8 September 2022',
        'role' => 'Siswa',
        'password' => 'test12345',
        'password_confirmation' => 'test12345'
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.email.0', 'Email telah terdaftar');
});

it("Should return user's token if success", function(){
    $response = $this->postJson(REGISTER_URL, [
        'name' => 'test',
        'email' => 'test@test.com',
        'TTL' => 'Malang, 8 September 2022',
        'role' => 'Siswa',
        'password' => 'test12345',
        'password_confirmation' => 'test12345'
    ]);

    $response->assertStatus(200);
   
    $response->assertJson(fn (AssertableJson $json) => $json->hasKey('token'));
});

it("Should return user's role if success", function(){
    $response = $this->postJson(REGISTER_URL, [
        'name' => 'test',
        'email' => 'test@test.com',
        'TTL' => 'Malang, 8 September 2022',
        'role' => 'Siswa',
        'password' => 'test12345',
        'password_confirmation' => 'test12345'
    ]);

    $response->assertStatus(200);
   
    $response->assertJson([
        'role' => 'Siswa'
    ]);
});

it("Should return user's name if success", function(){
    $response = $this->postJson(REGISTER_URL, [
        'name' => 'test',
        'email' => 'test@test.com',
        'TTL' => 'Malang, 8 September 2022',
        'role' => 'Siswa',
        'password' => 'test12345',
        'password_confirmation' => 'test12345'
    ]);

    $response->assertStatus(200);
   
    $response->assertJson([
        'name' => 'test',
    ]);
});

it("Should return 422 with 'Harus diisi' when tehre's empty");

it("Should return 422 with 'Password tidak sama' when password not match");

it("Should return 422 with 'Password harus lebih dari 5 karakter' when password less than 5 characters");

it("Should return 422 with 'Email tidak sesuai format' when email not match format");
