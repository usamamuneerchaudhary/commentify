<?php

use Usamamuneerchaudhary\Commentify\Support\LoginUrl;

class LoginUrlTest extends TestCase
{
    public function test_it_returns_the_named_login_route(): void
    {
        Route::get('commentify-login', fn () => 'ok')->name('commentify.login');
        config(['commentify.login_route' => 'commentify.login']);

        $this->assertNotNull(LoginUrl::url());
        $this->assertStringContainsString('commentify-login', (string) LoginUrl::url());
    }

    public function test_it_returns_null_when_the_route_is_missing(): void
    {
        config(['commentify.login_route' => 'does-not-exist']);

        $this->assertNull(LoginUrl::url());
    }

    public function test_it_returns_null_when_login_route_is_blank(): void
    {
        config(['commentify.login_route' => '']);

        $this->assertNull(LoginUrl::url());
    }
}
