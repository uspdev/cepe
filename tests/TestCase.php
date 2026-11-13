<?php

namespace Tests;

use App\Contracts\BoletoGateway;
use App\Contracts\PixGateway;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Fakes\FakeBoletoGateway;
use Tests\Fakes\FakePixGateway;

abstract class TestCase extends BaseTestCase
{
    protected FakeBoletoGateway $boleto;

    protected FakePixGateway $pix;

    //
    protected function setUp(): void
    {
        parent::setUp();

        $this->boleto = new FakeBoletoGateway;
        $this->app->instance(BoletoGateway::class, $this->boleto);
        $this->pix = new FakePixGateway;
        $this->app->instance(PixGateway::class, $this->pix);
    }
}
