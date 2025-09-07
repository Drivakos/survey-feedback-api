<?php

namespace Tests\Feature;

use App\Models\Responder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register()
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'responder' => ['id', 'email'],
                        'token'
                    ]
                ]);

        $this->assertDatabaseHas('responders', [
            'email' => 'test@example.com'
        ]);
    }

    #[Test]
    public function registration_requires_valid_email()
    {
        $userData = [
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function registration_requires_password_confirmation()
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function user_can_login_with_valid_credentials()
    {
        $user = Responder::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123' // Will be auto-hashed by model mutator
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'responder' => ['id', 'email'],
                        'token'
                    ]
                ]);
    }

    #[Test]
    public function login_fails_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ]);
    }

    #[Test]
    public function email_must_be_unique_during_registration()
    {
        Responder::factory()->create(['email' => 'test@example.com']);

        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function registration_requires_all_fields()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Data provider for invalid email formats
     */
    #[DataProvider('invalidEmailProvider')]
    public static function invalidEmailProvider(): array
    {
        return [
            ['invalid-email'],
            ['@example.com'],
            ['user@'],
            ['user.example.com'],
            [''],
            [null],
        ];
    }

    #[Test]
    #[DataProvider('invalidEmailProvider')]
    public function registration_validates_email_format($invalidEmail)
    {
        $userData = [
            'email' => $invalidEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Data provider for password validation
     */
    #[DataProvider('invalidPasswordProvider')]
    public static function invalidPasswordProvider(): array
    {
        return [
            ['', ''],
            ['pass', 'pass'], // too short
            ['password123', 'different'], // confirmation mismatch
            [null, null],
        ];
    }

    #[Test]
    #[DataProvider('invalidPasswordProvider')]
    public function registration_validates_password_requirements($password, $confirmation)
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => $password,
            'password_confirmation' => $confirmation
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }
}
