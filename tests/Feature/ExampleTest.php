<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_application_redirects_root_to_login_and_uses_isolated_test_db(): void
    {
        $this->assertSame('assignment02_crud_testing', DB::connection()->getDatabaseName());

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
