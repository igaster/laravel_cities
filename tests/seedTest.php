<?php

namespace Igaster\LaravelCities\Tests;

class seedTest extends abstractTest
{

    // You cam execute this test with: phpunit --filter testSeedCommand tests/seedTest

    public function testSeedCommand()
    {
        $this->markTestSkipped('Takes too long to complete'); // Comment this line to enable the test

        $this->app->bind('path.storage', function ($app) {
            return realpath(__DIR__.'/../storage');
        });

        $this->artisan('migrate');

        $this->artisan('geo:seed')
            ->assertExitCode(0);
    }

}