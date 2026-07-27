<?php
/**
 * Configurações base da aplicação
 * As configurações dinâmicas (SMTP, API Keys, etc.) ficam no banco de dados
 * e são gerenciadas pela área administrativa.
 */

return [
    'app_name' => 'Brooks Construtora',
    'app_url' => 'https://www.brooksconstrutora.com.br',
    'timezone' => 'America/Sao_Paulo',
    'charset' => 'UTF-8',

    // Configurações do banco de dados (detecta ambiente pelo domínio ou .git/HEAD)
    'database' => (function () {
        $branch = 'main';

        // 1. Prioridade: detecta pelo domínio (funciona mesmo se .git/HEAD estiver incorreto)
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        if (str_contains($host, 'plesk.page') || str_contains($host, 'beta')) {
            $branch = 'beta';
        }
        // 2. Fallback: leitura do .git/HEAD (funciona local e em deploys que preservam o HEAD)
        else {
            $headFile = ROOT_PATH . '/.git/HEAD';
            if (file_exists($headFile)) {
                $head = trim(file_get_contents($headFile));
                if (str_starts_with($head, 'ref: refs/heads/')) {
                    $branch = substr($head, strlen('ref: refs/heads/'));
                }
            }
        }

        // Main = produção, qualquer outra branch = beta
        if ($branch === 'main') {
            return [
                'host' => 'localhost',
                'dbname' => 'brooks_construtora',
                'username' => 'brooks_construtora',
                'password' => 'nSTjm88b!md0%Ysr',
                'charset' => 'utf8mb4',
            ];
        }

        return [
            'host' => 'localhost',
            'dbname' => 'brooks_construtora_beta',
            'username' => 'brooks_construtora_beta',
            'password' => 'nSTjm88b!md0%Ysr',
            'charset' => 'utf8mb4',
        ];
    })(),

    // Configurações de sessão
    'session' => [
        'name' => 'brooks_session',
        'lifetime' => 7200, // 2 horas
    ],

    // Diretórios de upload
    'uploads' => [
        'path' => ROOT_PATH . '/public/uploads',
        'url' => '/uploads',
        'max_size' => 10 * 1024 * 1024, // 10MB
    ],
];
