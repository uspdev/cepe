<?php

namespace App\Services;

use App\Contracts\BoletoGateway;
use App\Exceptions\BoletoIntegracaoException;
use Illuminate\Support\Facades\Http;
use Throwable;
use Uspdev\Boleto;

class UspdevBoletoGateway implements BoletoGateway
{
    private ?Boleto $boleto = null;

    public function gerar(array $dados): string
    {
        try {
            $retorno = $this->cliente()->gerar($dados);
        } catch (BoletoIntegracaoException $e) {
            throw dd($e);
        } catch (Throwable) {
            throw new BoletoIntegracaoException('Não foi possível confirmar a emissão do boleto.', true);
        }

        if (! ($retorno['status'] ?? false)) {
            throw new BoletoIntegracaoException(
                'O WS Boleto não confirmou a emissão.',
                $this->erroDeTransporte((string) ($retorno['value'] ?? '')),
            );
        }
        if (blank($retorno['value'] ?? null)) {
            throw new BoletoIntegracaoException('O WS Boleto confirmou uma emissão sem retornar o identificador.', true);
        }

        return (string) $retorno['value'];
    }

    public function situacao(string $codigo): array
    {
        try {
            $retorno = $this->cliente()->situacao($codigo);
        } catch (BoletoIntegracaoException $e) {
            throw $e;
        } catch (Throwable) {
            throw new BoletoIntegracaoException('Não foi possível consultar a situação do boleto.', true);
        }

        if (! ($retorno['status'] ?? false) || ! is_array($retorno['value'] ?? null)) {
            throw new BoletoIntegracaoException('O WS Boleto não retornou a situação do boleto.', true);
        }

        return $retorno['value'];
    }

    public function obter(string $codigo): string
    {
        try {
            $retorno = $this->cliente()->obter($codigo);
        } catch (BoletoIntegracaoException $e) {
            throw $e;
        } catch (Throwable) {
            throw new BoletoIntegracaoException('Não foi possível obter o PDF do boleto.', true);
        }

        if (! ($retorno['status'] ?? false) || ! is_string($retorno['value'] ?? null)) {
            throw new BoletoIntegracaoException('O WS Boleto não retornou o PDF do boleto.', true);
        }

        $pdf = base64_decode($retorno['value'], true);
        if ($pdf === false || ! str_starts_with($pdf, '%PDF-')) {
            throw new BoletoIntegracaoException('O WS Boleto retornou um PDF inválido.', true);
        }

        return $pdf;
    }

    public function cancelar(string $codigo): void
    {
        try {
            $retorno = $this->cliente()->cancelar($codigo);
        } catch (BoletoIntegracaoException $e) {
            throw $e;
        } catch (Throwable) {
            throw new BoletoIntegracaoException('Não foi possível confirmar o cancelamento do boleto.', true);
        }

        if (! ($retorno['status'] ?? false)) {
            throw new BoletoIntegracaoException('O WS Boleto não confirmou o cancelamento.');
        }
    }

    private function cliente(): Boleto
    {
        if ($this->boleto) {
            return $this->boleto;
        }

        $usuario = config('services.wsboleto.username');
        $senha = config('services.wsboleto.password');
        if (blank($usuario) || blank($senha)) {
            throw new BoletoIntegracaoException('As credenciais do WS Boleto não estão configuradas.');
        }

        $desenvolvimento = filter_var(
            config('services.wsboleto.dev'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE,
        ) ?? true;
        $nomeConstante = $desenvolvimento ? 'WSBOLETO_URL_DEV' : 'WSBOLETO_URL_PRD';
        $wsdl = (new \ReflectionClass(Boleto::class))->getReflectionConstant($nomeConstante)?->getValue();
        if (! $wsdl) {
            throw new BoletoIntegracaoException('A URL WSDL do WS Boleto não está disponível.');
        }

        try {
            $resposta = Http::timeout(10)->get($wsdl);
        } catch (Throwable) {
            throw new BoletoIntegracaoException('O WSDL do WS Boleto está indisponível.');
        }

        $documentoWsdl = $resposta->successful() ? @simplexml_load_string($resposta->body()) : false;
        if (! $documentoWsdl || $documentoWsdl->getName() !== 'definitions') {
            throw new BoletoIntegracaoException('O WSDL do WS Boleto está indisponível.');
        }

        return $this->boleto = new Boleto($usuario, $senha, $desenvolvimento);
    }

    private function erroDeTransporte(string $erro): bool
    {
        return preg_match('/timeout|timed out|connection|conex[aã]o|transport|socket|http error/i', $erro) === 1;
    }
}
