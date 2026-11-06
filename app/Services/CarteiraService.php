<?php

namespace App\Services;

use App\Models\Carteira;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CarteiraService
{
    /** Categorias que o titular pode solicitar, conforme vínculo USP e carteiras que já possui. */
    public function categoriasPermitidas(User $titular): array
    {
        $codigos = $titular->perfilOuNovo()->vinculos_usp ?? [];

        return array_filter(config('cepe.carteiras.categorias'), function (array $categoria) use ($titular, $codigos) {
            if (! empty($categoria['vinculos'])) {
                $temVinculo = collect($codigos)->contains(fn ($codigo) => Str::startsWith($codigo, $categoria['vinculos']));
                if (! $temVinculo) {
                    return false;
                }
            }

            return empty($categoria['requer_carteira'])
                || $titular->carteirasSolicitadas()->where('categoria', $categoria['requer_carteira'])->vigentes()->exists();
        });
    }

    /** Documentos obrigatórios da categoria, somados aos do parentesco do beneficiário. */
    public function documentosExigidos(string $categoria, ?string $parentesco): array
    {
        $config = config("cepe.carteiras.categorias.{$categoria}");

        return array_values(array_unique([
            ...$config['documentos'],
            ...($config['parentescos'][$parentesco] ?? []),
        ]));
    }

    public function documentosOpcionais(string $categoria): array
    {
        return config("cepe.carteiras.categorias.{$categoria}.opcionais", []);
    }

    /**
     * @param  array<string, mixed>  $beneficiario  nome, cpf, data_nascimento (Y-m-d), parentesco — ignorado para categorias próprias
     * @param  array<string, UploadedFile>  $arquivos  tipo do documento => arquivo
     */
    public function solicitar(User $solicitante, string $categoria, array $beneficiario, array $arquivos): Carteira
    {
        if (config("cepe.carteiras.categorias.{$categoria}.beneficiario") === 'proprio') {
            $perfil = $solicitante->perfilOuNovo();
            $beneficiario = [
                'nome' => $solicitante->name,
                'cpf' => $perfil->cpf,
                'data_nascimento' => $perfil->data_nascimento?->toDateString(),
                'parentesco' => null,
            ];
        }

        return DB::transaction(function () use ($solicitante, $categoria, $beneficiario, $arquivos) {
            $carteira = Carteira::create($beneficiario + [
                'solicitante_id' => $solicitante->id,
                'categoria' => $categoria,
                'status' => 'em_analise',
            ]);
            $this->guardarDocumentos($carteira, $arquivos);

            return $carteira;
        });
    }

    /** Grava (ou substitui) os arquivos enviados; usado na solicitação e no reenvio de documentação pendente. */
    public function guardarDocumentos(Carteira $carteira, array $arquivos): void
    {
        foreach ($arquivos as $tipo => $arquivo) {
            $atual = $carteira->documentos()->where('tipo', $tipo)->first();
            $caminho = $arquivo->store("carteiras/{$carteira->id}", 'local');
            if ($atual) {
                Storage::disk('local')->delete($atual->arquivo);
                $atual->update(['arquivo' => $caminho]);
            } else {
                $carteira->documentos()->create(['tipo' => $tipo, 'arquivo' => $caminho]);
            }
        }
    }

    public function aprovar(Carteira $carteira, string $numero, User $analista, ?int $via = null, ?string $observacao = null): void
    {
        DB::transaction(function () use ($carteira, $numero, $via, $analista, $observacao) {
            $carteira->forceFill([
                'status' => 'aprovada',
                'numero' => $numero,
                'via' => $via,
                'validade_inicio' => today(),
                'validade_fim' => today()->addMonths((int) config('cepe.carteiras.validade_meses', 12)),
                'observacao' => $observacao,
                'analisado_por' => $analista->id,
                'analisado_em' => now(),
                'user_id' => $carteira->beneficiarioProprio() ? $carteira->solicitante_id : $carteira->user_id,
            ])->save();
        });
    }

    public function registrarAnalise(Carteira $carteira, string $status, string $observacao, User $analista): void
    {
        $carteira->forceFill([
            'status' => $status,
            'observacao' => $observacao,
            'analisado_por' => $analista->id,
            'analisado_em' => now(),
        ])->save();
    }

    /** Avanço do ciclo de vida após a aprovação (pagamento, confecção, retirada, inativação). */
    public function transitar(Carteira $carteira, string $status, User $analista, ?string $observacao = null): void
    {
        if (! in_array($status, $carteira->proximosStatus(), true)) {
            throw ValidationException::withMessages(['status' => 'Transição não permitida a partir do status atual.']);
        }

        $carteira->forceFill([
            'status' => $status,
            'observacao' => $observacao ?? $carteira->observacao,
            'analisado_por' => $analista->id,
            'analisado_em' => now(),
        ])->save();
    }

    /** Atualiza número e via de carteira já aprovada (correção de lançamento ou nova via manual). */
    public function atualizarEmissao(Carteira $carteira, string $numero, ?int $via, User $analista): void
    {
        $carteira->forceFill([
            'numero' => $numero,
            'via' => $via,
            'analisado_por' => $analista->id,
            'analisado_em' => now(),
        ])->save();
    }

    /** Dependente com conta própria informa o número; confere com a carteira antes de vincular. */
    public function vincular(User $user, string $numero): Carteira
    {
        $carteira = Carteira::where('numero', $numero)->orderByDesc('validade_fim')->first();
        $erro = fn (string $mensagem) => throw ValidationException::withMessages(['numero' => $mensagem]);

        if (! $carteira) {
            $erro('Número de carteira não encontrado.');
        }
        if (! $carteira->vigente()) {
            $erro('Esta carteira não está vigente. Aguarde a confirmação do pagamento ou procure a SRU.');
        }
        if ($carteira->user_id && $carteira->user_id !== $user->id) {
            $erro('Esta carteira já está vinculada a outra conta. Procure a SRU.');
        }
        if ($carteira->beneficiarioProprio()) {
            $erro('Esta carteira já está vinculada ao titular.');
        }
        if (! $this->confere($carteira, $user->perfilOuNovo(), $user->name)) {
            $erro('Os dados do seu perfil (CPF ou nome e data de nascimento) não conferem com os da carteira. Corrija o perfil ou procure a SRU.');
        }

        $carteira->forceFill(['user_id' => $user->id])->save();

        return $carteira;
    }

    private function confere(Carteira $carteira, Perfil $perfil, string $nome): bool
    {
        if ($carteira->cpf && $perfil->cpf) {
            return $carteira->cpf === $perfil->cpf;
        }

        $normalizar = fn (?string $texto) => Str::lower(Str::ascii(Str::squish((string) $texto)));

        return $carteira->data_nascimento && $perfil->data_nascimento
            && $carteira->data_nascimento->isSameDay($perfil->data_nascimento)
            && $normalizar($carteira->nome) === $normalizar($nome);
    }
}
