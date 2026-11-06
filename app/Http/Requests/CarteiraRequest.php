<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use App\Services\CarteiraService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarteiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function categoria(): ?string
    {
        return $this->input('categoria');
    }

    /** Beneficiário dependente informado no formulário. */
    public function informaBeneficiario(): bool
    {
        return config('cepe.carteiras.categorias.'.$this->categoria().'.beneficiario') === 'dependente';
    }

    /** Tipos de documento aceitos nesta solicitação (obrigatórios e opcionais). */
    public function tiposDocumento(): array
    {
        $servico = app(CarteiraService::class);
        $categoria = (string) $this->categoria();

        return [
            ...$servico->documentosExigidos($categoria, $this->input('parentesco')),
            ...$servico->documentosOpcionais($categoria),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('cpf'))) {
            $this->merge(['cpf' => preg_replace('/\D/', '', $this->input('cpf'))]);
        }
    }

    public static function regraArquivo(string $tipo): string
    {
        $config = config('cepe.carteiras.'.($tipo === 'foto' ? 'foto' : 'arquivo'));

        return "file|mimes:{$config['mimes']}|max:{$config['max_kb']}";
    }

    public function rules(): array
    {
        $servico = app(CarteiraService::class);
        $categoria = $this->categoria();
        $rules = [
            'categoria' => ['required', Rule::in(array_keys($servico->categoriasPermitidas($this->user())))],
        ];

        $config = config("cepe.carteiras.categorias.{$categoria}");
        if (! is_string($categoria) || ! $config) {
            return $rules;
        }

        if ($this->informaBeneficiario()) {
            $rules['nome'] = 'required|string|max:255';
            $rules['cpf'] = ['nullable', new Cpf];
            $rules['data_nascimento'] = 'required|date_format:d/m/Y|before:tomorrow|after:01/01/1900';
            $rules['parentesco'] = ['required', Rule::in(array_keys($config['parentescos'] ?? []))];
        }

        $rules['documentos'] = 'required|array';
        foreach ($servico->documentosExigidos($categoria, $this->input('parentesco')) as $tipo) {
            $rules["documentos.{$tipo}"] = 'required|'.self::regraArquivo($tipo);
        }
        foreach ($servico->documentosOpcionais($categoria) as $tipo) {
            $rules["documentos.{$tipo}"] = 'nullable|'.self::regraArquivo($tipo);
        }

        return $rules;
    }

    /** Faixa etária exigida pelo parentesco (ex.: filho até 21 anos, universitário de 22 a 24). */
    public function after(): array
    {
        return [function ($validator) {
            $faixa = config('cepe.carteiras.idade_parentesco.'.$this->input('parentesco'));
            if (! $this->informaBeneficiario() || ! $faixa || $validator->errors()->has('data_nascimento')) {
                return;
            }

            $idade = Carbon::createFromFormat('d/m/Y', $this->input('data_nascimento'))->age;
            if ($idade < $faixa[0] || $idade > $faixa[1]) {
                $validator->errors()->add('data_nascimento', 'A idade do beneficiário não corresponde ao parentesco informado ('.config('cepe.carteiras.parentescos.'.$this->input('parentesco')).').');
            }
        }];
    }

    public function attributes(): array
    {
        return collect(config('cepe.carteiras.documentos'))
            ->mapWithKeys(fn ($rotulo, $tipo) => ["documentos.{$tipo}" => $rotulo])
            ->all() + ['data_nascimento' => 'data de nascimento'];
    }
}
