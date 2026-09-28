<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic smoke test — the public home (menu) page loads.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
