<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_renders_the_admin_layout(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Welcome to HRIS');
    }
}
