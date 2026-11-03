<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProfessorSeeder extends Seeder
{
    private const PROFESSORES = [
        ['codpes' => 2416651, 'nome' => 'Ana Cristina A. Mutarelli', 'email' => 'anamutarelli@usp.br', 'apelidos' => ['Ana Cristina']],
        ['codpes' => 484290, 'nome' => 'Carolina Magalhães', 'email' => 'carolinacepe@usp.br', 'apelidos' => ['Carolina']],
        ['codpes' => 2412521, 'nome' => 'Celso Buchler Teixeira', 'email' => 'celsobteixeira@uol.com.br', 'apelidos' => ['Celso']],
        ['codpes' => 608780, 'nome' => 'Carlos Bezerra de Albuquerque', 'email' => 'stargate@usp.br', 'apelidos' => ['Bezerra']],
        ['codpes' => 379646, 'nome' => 'Christian Klausener', 'email' => 'chklause@usp.br', 'apelidos' => ['Christian']],
        ['codpes' => 612628, 'nome' => 'Danilo Forghieri Santaella', 'email' => 'danyoga@usp.br', 'apelidos' => ['Danilo']],
        ['codpes' => 518539, 'nome' => 'Erica Takigahira', 'email' => 'ericakika@usp.br', 'apelidos' => ['Erica']],
        ['codpes' => 1032092, 'nome' => 'Fabiane Barros', 'email' => 'fabivilla@usp.br', 'apelidos' => ['Fabiane']],
        ['codpes' => 5969712, 'nome' => 'Guilherme Leutwiler Gabas', 'email' => 'guilherme.gabas@usp.br', 'apelidos' => ['Guilherme']],
        ['codpes' => 18550400, 'nome' => 'João Guilherme Silva Vieira de Oliveira', 'email' => 'joao.gsv.oliveira@usp.br', 'apelidos' => []],
        ['codpes' => 51856, 'nome' => 'José Carlos Simon Farah', 'email' => 'jcsfarah@usp.br', 'apelidos' => ['Farah']],
        ['codpes' => 935198, 'nome' => 'José Luiz Sinhorini', 'email' => 'signorini@usp.br', 'apelidos' => ['Signorini']],
        ['codpes' => 1073612, 'nome' => 'Katia Aparecida Pereira Moraes', 'email' => 'katiaapm@usp.br', 'apelidos' => ['Katia']],
        ['codpes' => 50365, 'nome' => 'Luzimar Raimundo Teixeira', 'email' => 'luzimar@usp.br', 'apelidos' => ['Luzimar']],
        ['codpes' => 3746922, 'nome' => 'Marcos Massatoshi Ito', 'email' => 'marcos_ito@usp.br', 'apelidos' => ['Marcos Ito']],
        ['codpes' => 1569153, 'nome' => 'Marcos Vinicius Moura e Silva', 'email' => 'mvmoura@usp.br', 'apelidos' => []],
        ['codpes' => 1389830, 'nome' => 'Maria de Lourdes A. M. Cassis', 'email' => 'mecassis@usp.br', 'apelidos' => ['Mª Lourdes', 'Maria de Lourdes']],
        ['codpes' => 1459021, 'nome' => 'Maykell Araújo Carvalho', 'email' => 'maykell@usp.br', 'apelidos' => ['Maykell']],
        ['codpes' => 2786922, 'nome' => 'Patrícia Peres', 'email' => 'patriciaperes@usp.br', 'apelidos' => ['Patricia Peres']],
        ['codpes' => 1343680, 'nome' => 'Patrícia Sakai', 'email' => 'psakai@usp.br', 'apelidos' => ['Patricia Sakai']],
        ['codpes' => 3467712, 'nome' => 'Paula M. Lavieri Viana', 'email' => 'paula.lavieri@usp.br', 'apelidos' => ['Paula']],
        ['codpes' => 17700051, 'nome' => 'Paulo José dos Santos de Souza', 'email' => 'paulosantos2594@usp.br', 'apelidos' => ['Paulo']],
        ['codpes' => 18359657, 'nome' => 'Pedro de Oliveira da Silva', 'email' => 'deoliveiradasilvapedro@usp.br', 'apelidos' => ['Pedro']],
        ['codpes' => 10264845, 'nome' => 'Rafael Luiz Martins Monteiro', 'email' => 'rafaell_mmonteiro@usp.br', 'apelidos' => ['Rafael']],
        ['codpes' => 2843665, 'nome' => 'Suzana Cavalheiro', 'email' => 'su.cav@usp.br', 'apelidos' => []],
        ['codpes' => 1753221, 'nome' => 'Vera Lucia Gonçalves', 'email' => 'verahidro@yahoo.com.br', 'apelidos' => ['Vera']],
        ['codpes' => 1881788, 'nome' => 'Vinicius Heine', 'email' => 'vheine@usp.br', 'apelidos' => ['Vinicius']],
        ['codpes' => 7475712, 'nome' => 'Womualy Conzaga dos Santos', 'email' => 'womualy@usp.br', 'apelidos' => ['Womualy']],
    ];

    public function run(): void
    {
        foreach (self::PROFESSORES as $dados) {
            $user = User::firstOrNew(['codpes' => $dados['codpes']]);
            $user->codpes = $dados['codpes'];
            $user->name = $dados['nome'];
            $user->email = $dados['email'];
            $user->save();
            $user->assignRole('professor');
        }
    }

    public static function idPorNome(?string $nome): ?int
    {
        $codpes = self::codpesPorNome($nome);

        return $codpes ? User::where('codpes', $codpes)->value('id') : null;
    }

    private static function codpesPorNome(?string $nome): ?int
    {
        $nome = self::normalizar((string) $nome);

        foreach (self::PROFESSORES as $professor) {
            foreach ($professor['apelidos'] as $apelido) {
                if (Str::startsWith($nome, self::normalizar($apelido))) {
                    return $professor['codpes'];
                }
            }
        }

        return null;
    }

    private static function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(preg_replace('/\s+/', ' ', trim($texto))));
    }
}
