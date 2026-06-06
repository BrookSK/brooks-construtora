<?php

namespace App\Core;

class Application
{
    private array $config;
    private static ?Application $instance = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        self::$instance = $this;

        // Configurações de timezone
        date_default_timezone_set($config['timezone'] ?? 'America/Sao_Paulo');

        // Inicia a sessão
        $this->startSession();
    }

    public static function getInstance(): ?Application
    {
        return self::$instance;
    }

    public function getConfig(?string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->config['session']['name'] ?? 'brooks_session');
            session_start();
        }
    }

    public function run(): void
    {
        $router = new Router();
        $router->dispatch();
    }
}
