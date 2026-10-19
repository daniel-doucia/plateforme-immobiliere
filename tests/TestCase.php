<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // La règle « au moins une photo » est testée explicitement dans PhotoObligatoireTest
        config(['annonces.photo_obligatoire' => false]);
    }
}
