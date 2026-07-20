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

    // Configurações do banco de dados (detecta branch automaticamente)
    'database' => (function () {
        $branch = 'main';
        $headFile = ROOT_PATH . '/.git/HEAD';
        if (file_exists($headFile)) {
            $head = trim(file_get_contents($headFile));
            if (str_starts_with($head, 'ref: refs/heads/')) {
                $branch = substr($head, strlen('ref: refs/heads/'));
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
