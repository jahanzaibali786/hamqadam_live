<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs the foreign keys `family_introductions` was meant to be created with.
 *
 * 2026_09_25_000001 declared four of them, but MySQL rejected the whole CREATE
 * with errno 150 because the types did not line up:
 *
 *     express_interests.id              bigint(20)            <- signed
 *     family_introductions.proposal_id  bigint(20) unsigned
 *
 * A foreign key needs both sides to match exactly, signedness included.
 * `express_interests` is the odd one out — it is the only table whose primary
 * key is a SIGNED bigint, where `$table->id()` produces an unsigned one
 * everywhere else — so the fix is to bring it in line rather than to make the
 * referencing column signed.
 *
 * On a database where the first attempt left the table behind, the original
 * migration's `if (! Schema::hasTable(...))` guard then skipped the block on the
 * retry, so the table exists with its columns but without a single constraint.
 * That is the state this migration repairs; on a database that never hit the
 * failure it simply finds the keys already present and does nothing.
 */
return new class extends Migration
{
    /** The keys 2026_09_25_000001 intended, as [column, table, onDelete]. */
    private const FOREIGN_KEYS = [
        ['proposal_id', 'express_interests', 'cascade'],
        ['initiated_by', 'users', 'cascade'],
        ['first_guardian_link_id', 'family_guardian_links', 'set null'],
        ['second_guardian_link_id', 'family_guardian_links', 'set null'],
    ];

    public function up(): void
    {
        // The ALTER below is MySQL syntax, and the whole problem is MySQL's
        // strictness about key types — nothing to do on other drivers.
        if (! $this->isMySql()) {
            return;
        }

        // 1. Bring express_interests.id in line with every other primary key.
        //    Safe to do in place here: nothing references it yet (the keys this
        //    migration adds are the first), so there are no constraints to drop
        //    and recreate around the change.
        if ($this->isSignedBigInt('express_interests', 'id')) {
            DB::statement(
                'ALTER TABLE `express_interests` '
                . 'MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }

        // 2. Add the four keys, skipping any that already exist so this runs
        //    cleanly on a database that got them the first time.
        if (! Schema::hasTable('family_introductions')) {
            return;
        }

        foreach (self::FOREIGN_KEYS as [$column, $onTable, $onDelete]) {
            if (! Schema::hasColumn('family_introductions', $column)) {
                continue;
            }
            if (! Schema::hasTable($onTable)) {
                continue;
            }
            if ($this->hasForeignKey('family_introductions', $column)) {
                continue;
            }

            Schema::table('family_introductions', function (Blueprint $table) use (
                $column,
                $onTable,
                $onDelete
            ): void {
                $table->foreign($column)
                    ->references('id')
                    ->on($onTable)
                    ->onDelete($onDelete);
            });
        }
    }

    public function down(): void
    {
        if (! $this->isMySql() || ! Schema::hasTable('family_introductions')) {
            return;
        }

        foreach (self::FOREIGN_KEYS as [$column, , ]) {
            if (! $this->hasForeignKey('family_introductions', $column)) {
                continue;
            }
            Schema::table('family_introductions', function (Blueprint $table) use ($column): void {
                $table->dropForeign([$column]);
            });
        }

        // express_interests.id is deliberately left unsigned: reverting it
        // would only reintroduce the mismatch, and any key added later would
        // hit the same errno 150.
    }

    private function isMySql(): bool
    {
        return in_array(
            DB::connection()->getDriverName(),
            ['mysql', 'mariadb'],
            true
        );
    }

    /** True when [table].[column] is a SIGNED bigint. */
    private function isSignedBigInt(string $table, string $column): bool
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return false;
        }

        $type = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?',
            [$table, $column]
        );

        if ($type === null) {
            return false;
        }

        $columnType = strtolower((string) $type->column_type);

        return str_contains($columnType, 'bigint')
            && ! str_contains($columnType, 'unsigned');
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        return DB::selectOne(
            'SELECT 1 AS present
               FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1',
            [$table, $column]
        ) !== null;
    }
};
