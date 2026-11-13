<?php

namespace App\Services;

use App\Contracts\PixGateway;
use App\Exceptions\PixIntegracaoException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class UspdevPixGateway implements PixGateway
{
    public function gerar(array $dados): array
    {
        try {
            $resposta = Http::withHeaders($this->headers())
                ->asJson()
                ->timeout(20)
                ->post($this->url('/pix/gerar'), $dados);
        } catch (ConnectionException) {
            throw new PixIntegracaoException('Não foi possível confirmar a geração do Pix.', true);
        }

        if ($resposta->clientError()) {
            throw new PixIntegracaoException('O WSPIX recusou a geração do Pix.');
        }
        if (! $resposta->successful()) {
            throw new PixIntegracaoException('Não foi possível confirmar a geração do Pix.', true);
        }

        $pix = $resposta->json();
        if (! is_array($pix) || blank($pix['idfpix'] ?? null)) {
            throw new PixIntegracaoException('O WSPIX não retornou o identificador do Pix.', true);
        }

        $copiaECola = $pix['qrCodeText'] ?? $pix['copiaECola'] ?? $pix['qrCode'] ?? null;
        $imagem = $pix['qrCodeImgBase64'] ?? $pix['qrCodeImageBase64'] ?? null;
        if (is_string($copiaECola) && str_starts_with($copiaECola, 'data:image/')) {
            $imagem ??= preg_replace('/^data:image\/[^;]+;base64,/', '', $copiaECola);
            $copiaECola = $pix['qrCodeText'] ?? $pix['copiaECola'] ?? null;
        } elseif (is_string($copiaECola) && ! $imagem) {
            $imagemBinaria = base64_decode($copiaECola, true);
            if (is_string($imagemBinaria) && str_starts_with($imagemBinaria, "\x89PNG")) {
                $imagem = $copiaECola;
                $copiaECola = $pix['qrCodeText'] ?? $pix['copiaECola'] ?? null;
            }
        }
        if (is_string($imagem)) {
            $imagem = preg_replace('/^data:image\/[^;]+;base64,/', '', $imagem);
            if (base64_decode($imagem, true) === false) {
                $imagem = null;
            }
        }
        if (! $imagem && is_string($copiaECola)) {
            $imagemBinaria = base64_decode($copiaECola, true);
            if (is_string($imagemBinaria) && str_starts_with($imagemBinaria, "\x89PNG")) {
                $imagem = $copiaECola;
                $copiaECola = $pix['qrCodeText'] ?? $pix['copiaECola'] ?? null;
            }
        }
        if (blank($copiaECola) && blank($imagem)) {
            throw new PixIntegracaoException('O WSPIX gerou um identificador sem QR Code ou código copia e cola.', true);
        }

        return [
            'idfpix' => (string) $pix['idfpix'],
            'qrCode' => $copiaECola,
            'qrCodeImgBase64' => $imagem,
            'dataExpiracao' => $pix['dataExpiracao'] ?? null,
        ];
    }

    public function consultar(string $id): array
    {
        try {
            $resposta = Http::withHeaders($this->headers())
                ->acceptJson()
                ->timeout(15)
                ->get($this->url('/pix/'.rawurlencode($id)));
        } catch (ConnectionException) {
            throw new PixIntegracaoException('Não foi possível consultar a situação do Pix.', true);
        }

        if (! $resposta->successful()) {
            throw new PixIntegracaoException('Não foi possível consultar a situação do Pix.', true);
        }

        $pix = $resposta->json();
        if (! is_array($pix) || blank($pix['status'] ?? null)) {
            throw new PixIntegracaoException('O WSPIX não retornou a situação do Pix.', true);
        }

        return $pix;
    }

    private function headers(): array
    {
        $username = config('services.wspix.username');
        $password = config('services.wspix.password');
        if (blank($username) || blank($password)) {
            throw new PixIntegracaoException('As credenciais do WSPIX não estão configuradas.');
        }

        return [
            'Content-Type' => 'application/json',
            'X-Username' => $username,
            'X-Password' => $password,
        ];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.wspix.base_url'), '/').$path;
    }
}
