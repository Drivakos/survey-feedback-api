<?php

namespace Tests\Unit\Models;

use App\Models\Answer;
use App\Models\Responder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Contracts\JWTSubject;
use PHPUnit\Framework\Attributes\Test;

class ResponderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function responder_implements_jwt_subject()
    {
        $responder = Responder::factory()->create();

        $this->assertInstanceOf(JWTSubject::class, $responder);
    }

    #[Test]
    public function responder_has_fillable_attributes()
    {
        $responder = Responder::create([
            'email' => 'test@example.com',
            'password' => 'test_password'
        ]);

        $this->assertEquals('test@example.com', $responder->email);
        // Password should be hashed by the mutator
        $this->assertTrue(password_verify('test_password', $responder->password));
    }

    #[Test]
    public function responder_password_mutator_hashes_passwords()
    {
        $responder = new Responder();
        $responder->password = 'plain_password';

        // Password should be hashed automatically
        $this->assertNotEquals('plain_password', $responder->password);
        $this->assertTrue(password_verify('plain_password', $responder->password));
    }

    #[Test]
    public function responder_hides_password_attribute()
    {
        $responder = Responder::factory()->create();

        $array = $responder->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    #[Test]
    public function responder_has_answers_relationship()
    {
        $responder = Responder::factory()->create();
        $answer = Answer::factory()->create(['responder_id' => $responder->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $responder->answers);
        $this->assertCount(1, $responder->answers);
        $this->assertEquals($answer->id, $responder->answers->first()->id);
    }

    #[Test]
    public function responder_returns_correct_jwt_identifier()
    {
        $responder = Responder::factory()->create();

        $this->assertEquals($responder->getKey(), $responder->getJWTIdentifier());
    }

    #[Test]
    public function responder_returns_empty_jwt_custom_claims()
    {
        $responder = Responder::factory()->create();

        $this->assertEquals([], $responder->getJWTCustomClaims());
    }

    #[Test]
    public function responder_email_must_be_unique()
    {
        Responder::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Responder::factory()->create(['email' => 'test@example.com']);
    }
}
