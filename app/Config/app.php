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

    // Configurações do banco de dados
    'database' => [
        'host' => 'localhost',
        'dbname' => 'brooks_construtora_beta',
        'username' => 'brooks_construtora_beta',
        'password' => 'nSTjm88b!md0%Ysr',
        'charset' => 'utf8mb4',
    ]
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
