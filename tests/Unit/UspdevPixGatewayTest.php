<?php

namespace Tests\Unit;

use App\Services\UspdevPixGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UspdevPixGatewayTest extends TestCase
{
    public function test_gera_pix_com_credenciais_configuradas_e_normaliza_retorno(): void
    {
        config([
            'services.wspix.username' => 'usuario-teste',
            'services.wspix.password' => 'senha-teste',
            'services.wspix.base_url' => 'https://wspix.test/api',
        ]);
        Http::fake([
            'https://wspix.test/api/pix/gerar' => Http::response([
                'idfpix' => 'PIX-1',
                'qrCode' => '000201-copia-e-cola',
                'qrCodeImgBase64' => base64_encode('png'),
                'dataExpiracao' => '31/12/2026 12:00:00',
            ]),
            'https://wspix.test/api/pix/PIX-1' => Http::response([
                'status' => 'Concluído',
                'valor' => '50.00',
                'dataPag' => '31/12/2026 11:59:00',
            ]),
        ]);

        $gateway = new UspdevPixGateway;
        $pix = $gateway->gerar(['valor' => 50]);

        $this->assertSame('PIX-1', $pix['idfpix']);
        $this->assertSame('000201-copia-e-cola', $pix['qrCode']);
        $this->assertSame(base64_encode('png'), $pix['qrCodeImgBase64']);
        $this->assertSame('Concluído', $gateway->consultar('PIX-1')['status']);
        Http::assertSent(fn (Request $request) => $request->hasHeader('X-Username', 'usuario-teste')
            && $request->hasHeader('X-Password', 'senha-teste'));
    }
}
