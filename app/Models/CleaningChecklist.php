<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class CleaningChecklist extends Model
{
    protected static string $table = 'cleaning_checklists';

    /**
     * Retorna todos os checklists ordenados por data de realização (mais recentes primeiro).
     */
    public static function allRecent(int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT * FROM cleaning_checklists ORDER BY performed_at DESC, created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Retorna os itens padrão do checklist agrupados por setor.
     */
    public static function getDefaultItems(): array
    {
        return [
            'vestiario' => [
                'label' => 'Vestiário',
                'items' => [
                    'Piso limpo',
                    'Bancos limpos e organizados',
                    'Armários limpos e em boas condições',
                    'Lixeiras limpas e com saco plástico',
                    'Lixeiras sem excesso de resíduos',
                    'Iluminação funcionando',
                    'Ventilação adequada',
                    'Ausência de odores desagradáveis',
                    'Produtos de limpeza armazenados corretamente',
                ],
            ],
            'refeicao' => [
                'label' => 'Local para Refeição',
                'items' => [
                    'Mesas limpas',
                    'Cadeiras limpas',
                    'Piso limpo',
                    'Bancada limpa',
                    'Geladeira limpa e organizada',
                    'Micro-ondas limpo',
                    'Bebedouro higienizado',
                    'Lixeiras limpas e tampadas',
                    'Não há restos de alimentos expostos',
                    'Controle de pragas sem evidências',
                ],
            ],
            'almoxarifado' => [
                'label' => 'Almoxarifado',
                'items' => [
                    'Piso limpo',
                    'Materiais organizados',
                    'Corredores livres',
                    'Materiais identificados',
                    'Produtos químicos armazenados corretamente',
                    'Ausência de vazamentos',
                    'Iluminação adequada',
                    'Extintor acessível',
                    'Prateleiras limpas',
                    'Resíduos descartados corretamente',
                ],
            ],
            'escritorio' => [
                'label' => 'Escritório',
                'items' => [
                    'Piso limpo',
                    'Mesas limpas e organizadas',
                    'Cadeiras limpas e em boas condições',
                    'Computadores limpos',
                    'Impressoras limpas',
                    'Documentos organizados',
                    'Lixeiras limpas e com saco plástico',
                    'Lixeiras sem excesso de resíduos',
                    'Vidros limpos',
                    'Portas limpas',
                    'Ar-condicionado/ventilação limpos',
                    'Iluminação funcionando',
                    'Extintor acessível e sinalizado',
                    'Banheiro do escritório limpo',
                    'Ambiente sem odores desagradáveis',
                    'Organização geral satisfatória',
                ],
            ],
        ];
    }
}
