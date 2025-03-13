<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase {
    /**
     * A basic feature test example.
     */
    public function test_it_can_login_user()
     {
        $user = User::factory()->create([
            'email'    => 'test@user.com',
            'password' => bcrypt('password'),
        ]);

        $this->postJson(route('Login'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertOk();
    }

    public function test_invalid_credential_cannot_login()
    {
        $user = User::factory()->create([
            'email'    => 'test@user.com',
            ]);

        $this->postJson(route('Login'), [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_it_can_logout_user()
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken( $token)
            ->postJson(route('Logout'))
            ->assertOk();
    }

    public function test_logged_out_token_is_invalid(){
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken( $token)
            ->postJson(route('Logout'));

        $this->withToken( $token)
            ->getJson(route('Get Data Sources'))
            ->assertUnauthorized();
    }
}
