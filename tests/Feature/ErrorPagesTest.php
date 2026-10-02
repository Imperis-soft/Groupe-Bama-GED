<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    // Un visiteur non connecté (ex. navigateur qui réclame /sw.js) doit recevoir une vraie 404, pas une erreur 500
    public function test_guest_gets_a_404_page_instead_of_a_server_error(): void
    {
        $this->get('/sw.js')->assertNotFound();
        $this->get('/page-inexistante')->assertNotFound();
    }
}
