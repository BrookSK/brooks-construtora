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
