<?php

use Illuminate\Support\Facades\Route;
use Usamamuneerchaudhary\Commentify\Models\User;
use Usamamuneerchaudhary\Commentify\Support\UserProfileUrl;

class UserProfileUrlTest extends TestCase
{
    public function test_it_builds_a_name_based_profile_path_by_default(): void
    {
        $user = User::factory()->create(['name' => 'usama']);

        $this->assertSame('/users/usama', UserProfileUrl::for($user));
    }

    public function test_it_builds_an_id_based_profile_path(): void
    {
        config(['commentify.users_route_key' => 'id']);

        $user = User::factory()->create(['name' => 'usama']);

        $this->assertSame('/users/'.$user->id, UserProfileUrl::for($user));
    }

    public function test_it_prefers_a_named_profile_route(): void
    {
        Route::get('members/{user}', fn () => 'ok')->name('users.show');
        config(['commentify.user_profile_route' => 'users.show']);

        $user = User::factory()->create(['name' => 'usama']);

        $this->assertStringContainsString('/members/'.$user->id, (string) UserProfileUrl::for($user));
    }

    public function test_it_falls_back_when_the_named_route_is_missing(): void
    {
        config([
            'commentify.user_profile_route' => 'does-not-exist',
            'commentify.users_route_key' => 'id',
        ]);

        $user = User::factory()->create(['name' => 'usama']);

        $this->assertSame('/users/'.$user->id, UserProfileUrl::for($user));
    }

    public function test_it_returns_null_when_the_prefix_is_blank(): void
    {
        config(['commentify.users_route_prefix' => '']);

        $user = User::factory()->create(['name' => 'usama']);

        $this->assertNull(UserProfileUrl::for($user));
    }
}
