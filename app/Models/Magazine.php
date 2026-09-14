<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Magazine extends Model
{
    protected static string $table = 'magazines';

    const STATUS_DRAFT = 'draft';
    const STATUS_GENERATED = 'generated';
    const STATUS_REVIEW = 'review';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';
    const STATUS_TEST = 'test';

    public static function getByStatus(string $status): array
    {
        return Database::fetchAll("SELECT * FROM magazines WHERE status = ? ORDER BY created_at DESC", [$status]);
    }

    public static function getPublished(): array
    {
        return Database::fetchAll("SELECT * FROM magazines WHERE status = 'published' ORDER BY published_at DESC");
    }

    public static function getLatest(int $limit = 5): array
    {
        return Database::fetchAll("SELECT * FROM magazines WHERE status = 'published' ORDER BY published_at DESC LIMIT ?", [$limit]);
    }

    public static function getPages(int $magazineId): array
    {
        return Database::fetchAll("SELECT * FROM magazine_pages WHERE magazine_id = ? ORDER BY page_number ASC", [$magazineId]);
    }

    /**
     * Chave secreta usada para derivar o token de preview.
     * Fixa no código (não precisa de banco). Só quem tem o código consegue gerar.
     */
    private const PREVIEW_SECRET = 'brooks_magazine_preview_2026_v1';

    /**
     * Gera um token de preview DETERMINÍSTICO para a revista.
     * Baseado no id + data de criação + chave secreta (HMAC).
     * Não depende de coluna no banco — sempre gera o mesmo token para a mesma revista.
     * Usado no modo teste para permitir acesso sem login via link.
     */
    public static function ensurePreviewToken(int $magazineId): string
    {
        $magazine = self::find($magazineId);
        $seed = $magazineId . '|' . ($magazine['created_at'] ?? '');
        return substr(hash_hmac('sha256', $seed, self::PREVIEW_SECRET), 0, 32);
    }

    /**
     * Verifica se o token de preview informado corresponde ao da revista.
     */
    public static function isValidPreviewToken(int $magazineId, string $token): bool
    {
        if (empty($token)) return false;
        $expected = self::ensurePreviewToken($magazineId);
        return hash_equals($expected, $token);
    }

    public static function addPage(int $magazineId, array $data): int
    {
        $data['magazine_id'] = $magazineId;
        return Database::insert('magazine_pages', $data);
    }

    public static function updatePage(int $pageId, array $data): int
    {
        return Database::update('magazine_pages', $data, 'id = ?', [$pageId]);
    }

    public static function deletePage(int $pageId): int
    {
        return Database::delete('magazine_pages', 'id = ?', [$pageId]);
    }
}
