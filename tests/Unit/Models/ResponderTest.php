<?php

namespace Tests\Unit\Models;

use App\Models\Answer;
use App\Models\Responder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Contracts\JWTSubject;

class ResponderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function responder_implements_jwt_subject()
    {
        $responder = Responder::factory()->create();

        $this->assertInstanceOf(JWTSubject::class, $responder);
    }

    /** @test */
    public function responder_has_fillable_attributes()
    {
        $responder = Responder::create([
            'email' => 'test@example.com',
            'password' => 'hashed_password'
        ]);

        $this->assertEquals('test@example.com', $responder->email);
        $this->assertEquals('hashed_password', $responder->password);
    }

    /** @test */
    public function responder_hides_password_attribute()
    {
        $responder = Responder::factory()->create();

        $array = $responder->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    /** @test */
    public function responder_has_answers_relationship()
    {
        $responder = Responder::factory()->create();
        $answer = Answer::factory()->create(['responder_id' => $responder->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $responder->answers);
        $this->assertCount(1, $responder->answers);
        $this->assertEquals($answer->id, $responder->answers->first()->id);
    }

    /** @test */
    public function responder_returns_correct_jwt_identifier()
    {
        $responder = Responder::factory()->create();

        $this->assertEquals($responder->getKey(), $responder->getJWTIdentifier());
    }

    /** @test */
    public function responder_returns_empty_jwt_custom_claims()
    {
        $responder = Responder::factory()->create();

        $this->assertEquals([], $responder->getJWTCustomClaims());
    }

    /** @test */
    public function responder_email_must_be_unique()
    {
        Responder::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Responder::factory()->create(['email' => 'test@example.com']);
    }
}
