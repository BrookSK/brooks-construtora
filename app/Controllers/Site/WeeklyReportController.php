<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;

/**
 * Relatório visual (somente leitura) da LISTA SEMANAL x obras x gerentes.
 *
 * Página "solta", acessível por um token no path (não linkada em nenhum menu):
 *   /relatorio-lista-semanal/{TOKEN}
 *
 * Lê os dados AO VIVO do banco:
 *  - construction_sites (obras + status)
 *  - construction_site_approvers (phase = 'weekly') + pin_users (responsáveis)
 *
 * Cruza com a "listagem declarada" que cada gerente informou (fixa abaixo),
 * para apontar: o que bate, o que falta marcar e o que está marcado a mais.
 */
class WeeklyReportController extends Controller
{
    /** Token de acesso (troque se quiser invalidar o link antigo) */
    private const TOKEN = 'brooks-9f3a7c2e5b';

    /**
     * Gerentes que precisamos analisar, mapeados por identificação no banco.
     * A chave é o nome "amigável" usado na análise; os matchers ajudam a
     * localizar o pin_user correspondente por nome/e-mail (case-insensitive).
     */
    private array $managers = [
        'Eduardo Carvalho' => ['emails' => ['eduardobarcarvalho40@gmail.com'], 'names' => ['eduardo carvalho', 'carlos eduardo barbosa carvalho'], 'epi' => true],
        'Jefferson Duarte'  => ['emails' => ['arq.jefferson.duarte@gmail.com'], 'names' => ['jefferson duarte']],
        'Rodrigo Bastos'    => ['emails' => ['arq.rodrigobastos@gmail.com'], 'names' => ['rodrigo bastos', 'rodrigo claudino bastos']],
        'Gleice Aline'      => ['emails' => ['g.a.b.interiores@gmail.com'], 'names' => ['gleice aline', 'gleice aline bernardi']],
        'Eduardo Andrade'   => ['emails' => ['engeduardoandradebrooks@gmail.com'], 'names' => ['eduardo andrade', 'eduardo henrique melo andrade']],
        'Mayara Alves'      => ['emails' => ['mayaraengenheira306@gmail.com'], 'names' => ['mayara alves', 'mayara alves da silva']],
    ];

    /** Listagem que cada gerente declarou (texto livre informado por eles). */
    private array $declared = [
        'Jefferson Duarte' => [
            'P041 - Gisele Koraicho', 'P034 - Hélio Akitoshi', 'P010 - Joia Bergamo Fábio 214',
            'P030 - Mari Coser Vanessa', 'Studio Delar - Luciana', 'Mari Coser - Lucas Cataldi',
            'P010 - Joia Bergamo Fábio estúdios', 'P008 - Joia Bergamo Rodrigo',
        ],
        'Mayara Alves' => [
            'Vanessa e Caique', 'ID móveis', 'Casa da montanha',
            'João Doria SP - Lareira', 'João Doria - Residência',
        ],
        'Gleice Aline' => ['Fasano', 'Vanduir P009', 'Sônia', 'Mariana P032'],
        'Eduardo Andrade' => ['Emerson', 'Archduo', 'Monalisa', 'Renato'],
        'Rodrigo Bastos' => [
            'P004 - André de Ângelo', 'P007 - Antônio e Andréa', 'P011 - Marco Mello',
            'P039 - Tabata - Kenny', 'P040 - Rocha Andrade', 'P042 - Camila e Gabriela',
            '000055 - Orçamento Lareira Dória',
        ],
    ];

    public function index(?string $token = null): void
    {
        if ($token !== self::TOKEN) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return;
        }

        // ---------------- Dados AO VIVO do banco ----------------
        $sites = Database::fetchAll(
            "SELECT id, code, name, status FROM construction_sites ORDER BY code, name"
        );

        // Vínculos weekly: obra -> lista de pin_users
        $links = Database::fetchAll(
            "SELECT csa.construction_site_id AS site_id, pu.id AS pin_id, pu.name AS pin_name, pu.email AS pin_email
             FROM construction_site_approvers csa
             JOIN pin_users pu ON pu.id = csa.pin_user_id
             WHERE csa.phase = 'weekly'"
        );

        // Mapa: site_id -> [ 'Nome Gerente' ou 'pin_name cru' ]
        $weeklyBySite = [];      // site_id => [managerFriendly => true]
        $weeklyRawBySite = [];   // site_id => [pin_name => true]  (todos, inclusive não-gerentes)
        foreach ($links as $l) {
            $sid = (int) $l['site_id'];
            $weeklyRawBySite[$sid][$l['pin_name']] = true;
            $friendly = $this->matchManager($l['pin_name'], $l['pin_email']);
            if ($friendly) {
                $weeklyBySite[$sid][$friendly] = true;
            } else {
                $weeklyBySite[$sid]['__outro__:' . $l['pin_name']] = true;
            }
        }

        // Index de obras
        $siteById = [];
        foreach ($sites as $s) { $siteById[(int)$s['id']] = $s; }

        // ---------------- Estatísticas ----------------
        $statusCount = [];
        foreach ($sites as $s) {
            $st = $s['status'] ?: '(sem status)';
            $statusCount[$st] = ($statusCount[$st] ?? 0) + 1;
        }
        $activeStatuses = ['active', 'em_andamento'];
        $isActive = fn($s) => in_array($s['status'], $activeStatuses, true);
        $activeSites = array_values(array_filter($sites, $isActive));

        // ---------------- Análise por gerente ----------------
        $analysis = [];
        foreach ($this->managers as $mg => $cfg) {
            $mgSites = [];
            foreach ($weeklyBySite as $sid => $names) {
                if (isset($names[$mg])) $mgSites[$sid] = $siteById[$sid] ?? null;
            }

            $entry = ['weekly' => $mgSites, 'declaredRows' => [], 'extra' => [], 'epi' => !empty($cfg['epi'])];

            if (!empty($cfg['epi'])) {
                // Deve estar em TODAS as obras ativas. Listar as ativas onde falta.
                $missing = [];
                foreach ($activeSites as $s) {
                    $sid = (int) $s['id'];
                    if (!isset($weeklyBySite[$sid][$mg])) $missing[$sid] = $s;
                }
                $entry['missingActive'] = $missing;
                $analysis[$mg] = $entry;
                continue;
            }

            // Comparar com a listagem declarada
            $declared = $this->declared[$mg] ?? [];
            $declaredSiteIds = [];
            foreach ($declared as $termo) {
                $matches = $this->findSites($termo, $sites);
                if (!$matches) {
                    $entry['declaredRows'][] = ['termo' => $termo, 'site' => null, 'weekly' => false];
                    continue;
                }
                foreach ($matches as $s) {
                    $sid = (int) $s['id'];
                    $declaredSiteIds[$sid] = true;
                    $entry['declaredRows'][] = [
                        'termo' => $termo,
                        'site'  => $s,
                        'weekly' => isset($weeklyBySite[$sid][$mg]),
                    ];
                }
            }

            // Está marcado weekly mas NÃO apareceu na listagem declarada
            foreach ($mgSites as $sid => $s) {
                if (!isset($declaredSiteIds[$sid])) $entry['extra'][$sid] = $s;
            }

            $analysis[$mg] = $entry;
        }

        // ---------------- Obras ativas sem responsável ----------------
        $noResponsible = [];   // nenhum weekly
        $onlyEpi = [];         // só Eduardo Carvalho (EPI), sem engenheiro
        foreach ($activeSites as $s) {
            $sid = (int) $s['id'];
            $names = $weeklyBySite[$sid] ?? [];
            if (empty($names)) {
                $noResponsible[] = $s;
            } else {
                $others = array_filter(array_keys($names), fn($n) => $n !== 'Eduardo Carvalho');
                if (empty($others)) $onlyEpi[] = $s;
            }
        }

        // Responsáveis weekly que NÃO são gerentes conhecidos
        $strangers = []; // [ ['site'=>..., 'name'=>...] ]
        foreach ($weeklyBySite as $sid => $names) {
            foreach ($names as $n => $_) {
                if (strpos($n, '__outro__:') === 0) {
                    $strangers[] = ['site' => $siteById[$sid] ?? null, 'name' => substr($n, strlen('__outro__:'))];
                }
            }
        }

        // ---------------- Render ----------------
        $data = [
            'sites' => $sites,
            'siteById' => $siteById,
            'statusCount' => $statusCount,
            'totalSites' => count($sites),
            'activeCount' => count($activeSites),
            'analysis' => $analysis,
            'weeklyBySite' => $weeklyBySite,
            'noResponsible' => $noResponsible,
            'onlyEpi' => $onlyEpi,
            'strangers' => $strangers,
            'generatedAt' => date('d/m/Y H:i'),
        ];

        $this->view('site.weekly_report.index', $data);
    }

    /** Retorna o nome amigável do gerente para um pin_user, ou null. */
    private function matchManager(string $name, ?string $email): ?string
    {
        $n = $this->norm($name);
        $e = strtolower(trim((string) $email));
        foreach ($this->managers as $mg => $cfg) {
            foreach (($cfg['emails'] ?? []) as $em) {
                if ($e !== '' && $e === strtolower($em)) return $mg;
            }
            foreach (($cfg['names'] ?? []) as $nm) {
                if ($n === $this->norm($nm)) return $mg;
            }
        }
        return null;
    }

    /** Localiza obras que casam com um termo declarado (por código Pxxx ou palavras). */
    private function findSites(string $termo, array $sites): array
    {
        $nt = $this->norm($termo);
        $projCode = null;
        if (preg_match('/\bp0*\d+\b/', $nt, $pm)) $projCode = $pm[0];

        $words = array_values(array_filter(
            explode(' ', $nt),
            fn($w) => strlen($w) > 2 && !preg_match('/^p?0*\d+$/', $w)
        ));

        $found = [];
        foreach ($sites as $s) {
            $nm = $this->norm(($s['name'] ?? '') . ' ' . ($s['code'] ?? ''));
            $hit = false;
            if ($projCode !== null && strpos($nm, $projCode) !== false) $hit = true;
            if (!$hit && $words) {
                $ok = true;
                foreach ($words as $w) if (strpos($nm, $w) === false) { $ok = false; break; }
                if ($ok) $hit = true;
            }
            if ($hit) $found[] = $s;
        }
        return $found;
    }

    /** Normaliza texto: remove acentos, minúsculas, só alfanumérico e espaço. */
    private function norm(string $s): string
    {
        $map = ['Á','À','Â','Ã','É','Ê','Í','Ó','Ô','Õ','Ú','Ç','á','à','â','ã','é','ê','í','ó','ô','õ','ú','ç'];
        $to  = ['A','A','A','A','E','E','I','O','O','O','U','C','a','a','a','a','e','e','i','o','o','o','u','c'];
        $s = str_replace($map, $to, $s);
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
}
