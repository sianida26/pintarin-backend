<?php
namespace Tests\Feature;

use App\Models\User;

// uses(RefreshDatabase::class);

const REGISTER_URL = '/api/auth/register';

// beforeEach(fn () => $this->seed());

afterEach(function(){
    $user = User::firstWhere('email', 'test@test.com');
    if ($user) {
        $user->tokens()->delete();
        $user->forceDelete();
    }

    $user = User::firstWhere('name', 'test');
    if ($user){
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
   
    $response->assertJsonStructure(['token']);
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
        'role' => 'siswa'
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
   
    $response->assertJsonPath('name', 'test');
});

it("Should return 422 with 'Harus diisi' when there's empty", function(){
    $response = $this->postJson(REGISTER_URL, [
        'name' => '',
        'email' => '',
        'TTL' => '',
        'role' => '',
        'password' => '',
        'password_confirmation' => ''
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.name.0', 'Harus diisi');
    $response->assertJsonPath('errors.email.0', 'Harus diisi');
    $response->assertJsonPath('errors.TTL.0', 'Harus diisi');
});

it("Should return 422 with 'Password tidak sama' when password not match", function() {
    $response = $this->postJson(REGISTER_URL, [
        'name' => 'test',
        'email' => 'test@test.com',
        'TTL' => 'Malang, 8 September 2022',
        'role' => 'Siswa',
        'password' => 'test12345',
        'password_confirmation' => 'test123456'
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.password.0', 'Password tidak sama');
});

it("Should return 422 with 'Password harus lebih dari 5 karakter' when password less than 5 characters", function(){
    $response = $this->postJson(REGISTER_URL, [
        'name' => 'test',
        'email' => 'test@test.com',
        'TTL' => 'Malang, 8 September 2022',
        'role' => 'Siswa',
        'password' => 'test1',
        'password_confirmation' => 'test1'
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.password.0', 'Password harus terdiri dari minimal 6 karakter');
});

it("Should return 422 with 'Email tidak sesuai format' when email not match format", function() {
    $response = $this->postJson(REGISTER_URL, [
        'name' => 'test',
        'email' => 'test',
        'TTL' => 'Malang, 8 September 2022',
        'role' => 'Siswa',
        'password' => 'test12345',
        'password_confirmation' => 'test12345'
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.email.0', 'Email tidak sesuai format');
});
