<?php

namespace Tests\Unit;

use App\Exceptions\BoletoIntegracaoException;
use App\Services\UspdevBoletoGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UspdevBoletoGatewayTest extends TestCase
{
    public function test_wsdl_indisponivel_falha_antes_de_instanciar_o_pacote(): void
    {
        config([
            'services.wsboleto.username' => 'teste',
            'services.wsboleto.password' => 'teste',
            'services.wsboleto.dev' => true,
        ]);
        Http::fake([
            'https://dev.uspdigital.usp.br/wsboleto/wsdl/boleto.wsdl' => Http::response('indisponível', 503),
        ]);

        try {
            (new UspdevBoletoGateway)->gerar([]);
            $this->fail('A indisponibilidade do WSDL deveria impedir a emissão.');
        } catch (BoletoIntegracaoException $e) {
            $this->assertFalse($e->resultadoIndeterminado);
        }

        Http::assertSentCount(1);
    }
}
