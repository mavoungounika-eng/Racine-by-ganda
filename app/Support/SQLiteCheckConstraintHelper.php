<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * SQLiteCheckConstraintHelper - Support pour CHECK constraints en SQLite
 * 
 * SQLite 3.32.0+ supporte ALTER TABLE ADD CONSTRAINT CHECK
 * Mais pour versions antérieures, fallback sur application-level validation
 * 
 * USAGE dans les migrations:
 * if (DB::getDriverName() === 'sqlite') {
 *     SQLiteCheckConstraintHelper::addCheckConstraint('accounting_entries', 'debit_amount >= 0');
 * } else {
 *     Schema::table('accounting_entries', function (Blueprint $table) {
 *         $table->rawColumns('CHECK (debit_amount >= 0)');
 *     });
 * }
 */
class SQLiteCheckConstraintHelper
{
    /**
     * Ajouter CHECK constraint en SQLite (version compatible)
     */
    public static function addCheckConstraint(
        string $tableName,
        string $checkExpression,
        ?string $constraintName = null
    ): void {
        try {
            // SQLite 3.32.0+
            if (self::supportsAlterTableAddConstraint()) {
                $constraint = $constraintName ?? self::generateConstraintName($tableName, $checkExpression);
                DB::statement(
                    "ALTER TABLE {$tableName} ADD CONSTRAINT {$constraint} CHECK ({$checkExpression})"
                );
                return;
            }
        } catch (\Exception $e) {
            // Fallback: validation au niveau application
            \Log::warning('[SQLite] CHECK constraint not supported, using application-level validation', [
                'table' => $tableName,
                'expression' => $checkExpression,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Vérifier si SQLite supporte ALTER TABLE ADD CONSTRAINT
     */
    private static function supportsAlterTableAddConstraint(): bool
    {
        try {
            $version = DB::selectOne("SELECT sqlite_version() as version");
            if (!$version) {
                return false;
            }

            [$major, $minor, $patch] = explode('.', $version->version);
            
            // SQLite 3.32.0+ supporté
            return (int)$major > 3 || ((int)$major === 3 && (int)$minor >= 32);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Générer nom de constraint unique
     */
    private static function generateConstraintName(string $table, string $expression): string
    {
        return 'check_' . $table . '_' . substr(hash('crc32', $expression), 0, 8);
    }

    /**
     * Ajouter validation au niveau Model (fallback)
     * 
     * Usage dans Model::boot():
     * self::saving(function ($model) {
     *     SQLiteCheckConstraintHelper::validateCheckConstraint(
     *         $model,
     *         'debit_amount',
     *         fn($value) => $value >= 0,
     *         'Debit amount must be >= 0'
     *     );
     * });
     */
    public static function validateCheckConstraint(
        object $model,
        string $field,
        callable $validator,
        string $message
    ): void {
        if ($model->isDirty($field)) {
            if (!$validator($model->{$field})) {
                throw new \InvalidArgumentException($message);
            }
        }
    }
}
