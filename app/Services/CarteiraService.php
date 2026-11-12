<?php

namespace App\Services;

use App\Models\Carteira;
use App\Models\SolicitacaoCarteira;
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
                || $titular->carteiras()->where('categoria', $categoria['requer_carteira'])->vigentes()->exists();
        });
    }

    public function podeRenovar(User $solicitante, Carteira $carteira): bool
    {
        $podeSolicitar = $carteira->solicitacoes()->where('solicitante_id', $solicitante->id)->exists();

        return $podeSolicitar
            && in_array($carteira->status, Carteira::STATUS_BENEFICIO, true)
            && ! $carteira->solicitacoes()->whereIn('status', ['em_analise', 'pendente'])->exists();
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
    public function solicitar(User $solicitante, string $categoria, array $beneficiario, array $arquivos, ?Carteira $renovar = null): SolicitacaoCarteira
    {
        if ($renovar) {
            if (! $this->podeRenovar($solicitante, $renovar) || $renovar->categoria !== $categoria) {
                throw ValidationException::withMessages(['carteira_id' => 'Esta carteira não pode ser renovada por esta conta.']);
            }

            $beneficiario = [
                'nome' => $renovar->nome,
                'cpf' => $renovar->cpf,
                'data_nascimento' => $renovar->data_nascimento?->toDateString(),
                'parentesco' => $renovar->parentesco,
            ];
        } elseif (config("cepe.carteiras.categorias.{$categoria}.beneficiario") === 'proprio') {
            $perfil = $solicitante->perfilOuNovo();
            $beneficiario = [
                'nome' => $solicitante->name,
                'cpf' => $perfil->cpf,
                'data_nascimento' => $perfil->data_nascimento?->toDateString(),
                'parentesco' => null,
            ];
        }

        return DB::transaction(function () use ($solicitante, $categoria, $beneficiario, $arquivos, $renovar) {
            if ($renovar) {
                $renovar = Carteira::whereKey($renovar->id)->lockForUpdate()->firstOrFail();
                if (! $this->podeRenovar($solicitante, $renovar) || $renovar->categoria !== $categoria) {
                    throw ValidationException::withMessages(['carteira_id' => 'Esta carteira não pode ser renovada por esta conta ou já possui solicitação em análise.']);
                }
                $beneficiario = [
                    'nome' => $renovar->nome,
                    'cpf' => $renovar->cpf,
                    'data_nascimento' => $renovar->data_nascimento?->toDateString(),
                    'parentesco' => $renovar->parentesco,
                ];
            }

            $solicitacao = SolicitacaoCarteira::create($beneficiario + [
                'solicitante_id' => $solicitante->id,
                'categoria' => $categoria,
                'status' => 'em_analise',
                'carteira_id' => $renovar?->id,
            ]);
            $this->guardarDocumentos($solicitacao, $arquivos);

            return $solicitacao;
        });
    }

    /** Grava (ou substitui) os arquivos enviados; usado na solicitação e no reenvio de documentação pendente. */
    public function guardarDocumentos(SolicitacaoCarteira $solicitacao, array $arquivos): void
    {
        foreach ($arquivos as $tipo => $arquivo) {
            $atual = $solicitacao->documentos()->where('tipo', $tipo)->first();
            $caminho = $arquivo->store("solicitacoes-carteiras/{$solicitacao->id}", 'local');
            if ($atual) {
                Storage::disk('local')->delete($atual->arquivo);
                $atual->update(['arquivo' => $caminho]);
            } else {
                $solicitacao->documentos()->create(['tipo' => $tipo, 'arquivo' => $caminho]);
            }
        }
    }

    public function aprovar(SolicitacaoCarteira $solicitacao, string $numero, User $analista, ?int $via = null, ?string $observacao = null): Carteira
    {
        return DB::transaction(function () use ($solicitacao, $numero, $via, $analista, $observacao) {
            $solicitacao = SolicitacaoCarteira::whereKey($solicitacao->id)->lockForUpdate()->firstOrFail();
            $carteira = $solicitacao->carteira_id
                ? Carteira::whereKey($solicitacao->carteira_id)->lockForUpdate()->firstOrFail()
                : new Carteira;

            $carteira->forceFill([
                'status' => 'aprovada',
                'numero' => $numero,
                'via' => $via ?? ($carteira->via ?: 1),
                'categoria' => $solicitacao->categoria,
                'nome' => $solicitacao->nome,
                'cpf' => $solicitacao->cpf,
                'data_nascimento' => $solicitacao->data_nascimento,
                'parentesco' => $solicitacao->parentesco,
                'observacao' => $observacao,
                'analisado_por' => $analista->id,
                'analisado_em' => now(),
                'validade_inicio' => today(),
                'validade_fim' => today()->addMonths((int) config('cepe.carteiras.validade_meses', 12)),
            ])->save();

            $solicitacao->forceFill([
                'status' => 'aprovada',
                'observacao' => $observacao,
                'analisado_por' => $analista->id,
                'analisado_em' => now(),
                'carteira_id' => $carteira->id,
            ])->save();

            return $carteira;
        });
    }

    public function registrarAnalise(SolicitacaoCarteira $solicitacao, string $status, string $observacao, User $analista): void
    {
        $solicitacao->forceFill([
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

    public function vincular(User $user, Carteira $carteira): void
    {
        DB::transaction(function () use ($user, $carteira) {
            $carteira = Carteira::whereKey($carteira->id)->lockForUpdate()->firstOrFail();
            if ($carteira->user_id && $carteira->user_id !== $user->id) {
                throw ValidationException::withMessages(['carteira_id' => 'Esta carteira já está vinculada a outra conta.']);
            }

            $perfil = $user->perfilOuNovo();
            $carteira->forceFill([
                'user_id' => $user->id,
                'nome' => $carteira->nome ?: $user->name,
                'cpf' => $carteira->cpf ?: $perfil->cpf,
                'data_nascimento' => $carteira->data_nascimento ?: $perfil->data_nascimento,
            ])->save();
        });
    }

    public function criarParaUsuario(User $user, array $dados): Carteira
    {
        return DB::transaction(function () use ($user, $dados) {
            $perfil = $user->perfilOuNovo();
            $dados['via'] ??= 1;

            return Carteira::create($dados + [
                'user_id' => $user->id,
                'nome' => $user->name,
                'cpf' => $perfil->cpf,
                'data_nascimento' => $perfil->data_nascimento?->toDateString(),
            ]);
        });
    }

    public function desvincular(User $user, Carteira $carteira): void
    {
        DB::transaction(function () use ($user, $carteira) {
            $carteira = Carteira::whereKey($carteira->id)->lockForUpdate()->firstOrFail();
            if ($carteira->user_id !== $user->id) {
                throw ValidationException::withMessages(['carteira' => 'Esta carteira não está vinculada a este usuário.']);
            }

            $carteira->forceFill(['user_id' => null])->save();
        });
    }
}
