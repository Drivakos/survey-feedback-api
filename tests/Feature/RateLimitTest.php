<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function allows_requests_within_rate_limit()
    {
        for ($i = 1; $i <= 60; $i++) {
            $response = $this->getJson('/api/surveys');

            if ($i <= 60) {
                $response->assertStatus(200);
            }
        }
    }

    #[Test]
    public function blocks_requests_over_rate_limit()
    {
        // Make 61 requests (1 over the limit)
        for ($i = 1; $i <= 61; $i++) {
            $response = $this->getJson('/api/surveys');

            if ($i == 61) {
                $response->assertStatus(429)
                        ->assertJsonStructure([
                            'status',
                            'message',
                            'retry_after'
                        ])
                        ->assertJson([
                            'status' => 'error',
                            'message' => 'Too many requests. Please try again later.'
                        ]);
            }
        }
    }

    #[Test]
    public function includes_rate_limit_headers_in_response()
    {
        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
                ->assertHeader('X-RateLimit-Limit', '60')
                ->assertHeader('X-RateLimit-Remaining')
                ->assertHeader('X-RateLimit-Reset');
    }

    #[Test]
    public function includes_retry_after_header_when_rate_limited()
    {
        // Make 61 requests to trigger rate limit
        for ($i = 1; $i <= 61; $i++) {
            $response = $this->getJson('/api/surveys');
        }

        $response->assertStatus(429)
                ->assertHeader('Retry-After')
                ->assertHeader('X-RateLimit-Limit', '60')
                ->assertHeader('X-RateLimit-Remaining', '0');
    }

    #[Test]
    public function rate_limit_applies_to_all_endpoints()
    {
        $endpoints = [
            '/api/surveys',
            '/api/register',
            '/api/login'
        ];

        foreach ($endpoints as $endpoint) {
            // Make 61 requests to each endpoint
            for ($i = 1; $i <= 61; $i++) {
                if ($endpoint === '/api/register' || $endpoint === '/api/login') {
                    $response = $this->postJson($endpoint, [
                        'email' => 'test@example.com',
                        'password' => 'password123'
                    ]);
                } else {
                    $response = $this->getJson($endpoint);
                }

                if ($i == 61) {
                    $response->assertStatus(429);
                }
            }
        }
    }

    #[Test]
    public function rate_limit_resets_after_time_window()
    {
        // Make maximum requests
        for ($i = 1; $i <= 60; $i++) {
            $this->getJson('/api/surveys');
        }

        // Verify we're at the limit
        $response = $this->getJson('/api/surveys');
        $response->assertStatus(429);

        // Simulate time passing by clearing cache
        Cache::flush();

        // Should allow requests again
        $response = $this->getJson('/api/surveys');
        $response->assertStatus(200);
    }
}
