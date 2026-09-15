<?php

use Usamamuneerchaudhary\Commentify\Models\User;
use Usamamuneerchaudhary\Commentify\Support\IntegerAuthId;

class IntegerAuthIdTest extends TestCase
{
    public function test_it_returns_integer_ids(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertSame($user->id, IntegerAuthId::get());
    }

    public function test_it_ignores_uuid_keyed_users(): void
    {
        $user = new class extends Illuminate\Foundation\Auth\User
        {
            public $incrementing = false;

            protected $keyType = 'string';

            protected $guarded = [];
        };
        $user->forceFill([
            'id' => '8523f9c3-9b12-4577-afe5-aa3300bdb640',
            'name' => 'Statamic',
            'email' => 'cp@example.com',
            'password' => 'secret',
        ]);

        $this->actingAs($user);

        $this->assertNull(IntegerAuthId::get());
    }

    public function test_it_returns_null_for_guests(): void
    {
        $this->assertNull(IntegerAuthId::get());
    }
}
