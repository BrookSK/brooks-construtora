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
     * Garante a existência da coluna preview_token e retorna um token válido
     * para a revista (gera um novo se ainda não existir).
     * Usado no modo teste para permitir acesso sem login via link.
     */
    public static function ensurePreviewToken(int $magazineId): string
    {
        // Garante a coluna (idempotente)
        try {
            $col = Database::fetch(
                "SELECT 1 FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'magazines' AND COLUMN_NAME = 'preview_token' LIMIT 1"
            );
            if (empty($col)) {
                Database::getConnection()->exec("ALTER TABLE magazines ADD COLUMN preview_token VARCHAR(64) NULL");
            }
        } catch (\Exception $e) {
            error_log('[MAGAZINE] Falha ao garantir coluna preview_token: ' . $e->getMessage());
        }

        $magazine = self::find($magazineId);
        if (!empty($magazine['preview_token'])) {
            return $magazine['preview_token'];
        }

        $token = bin2hex(random_bytes(16));
        self::updateById($magazineId, ['preview_token' => $token]);
        return $token;
    }

    /**
     * Verifica se o token de preview informado corresponde ao da revista.
     */
    public static function isValidPreviewToken(int $magazineId, string $token): bool
    {
        if (empty($token)) return false;
        $magazine = self::find($magazineId);
        return !empty($magazine['preview_token']) && hash_equals($magazine['preview_token'], $token);
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
