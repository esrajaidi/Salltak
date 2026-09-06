<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register(): void
    {
        $response = $this->post('/register', [
            'name'=>'أحمد','email'=>'ahmed@example.com','phone'=>'0911111111',
            'password'=>'Password@123','password_confirmation'=>'Password@123',
        ]);
        $response->assertRedirect('/my-carts');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users',['email'=>'ahmed@example.com','role'=>'customer']);
    }
}
