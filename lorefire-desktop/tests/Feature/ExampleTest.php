<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_redirects_to_onboarding_when_setup_is_incomplete(): void
    {
        $this->get('/')->assertRedirect('/onboarding');
    }
}
