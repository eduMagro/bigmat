<?php

namespace App\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;
use Throwable;

abstract class IdempotentSqlMigration extends Migration
{
    /** @param list<string> $statements */
    protected function runMany(array $statements): void
    {
        foreach ($statements as $statement) {
            $this->runSql($statement);
        }
    }

    protected function runSql(string $statement): void
    {
        $statement = trim($statement);
        if ($statement == '') {
            return;
        }

        if (!str_ends_with($statement, ';')) {
            $statement .= ';';
        }

        try {
            DB::unprepared($statement);
        } catch (Throwable $e) {
            if ($this->isIgnorableDatabaseError($e)) {
                return;
            }

            throw $e;
        }
    }

    protected function isIgnorableDatabaseError(Throwable $e): bool
    {
        $errorCode = null;

        if ($e instanceof QueryException && is_array($e->errorInfo) && isset($e->errorInfo[1])) {
            $errorCode = (int) $e->errorInfo[1];
        } elseif ($e instanceof PDOException && is_array($e->errorInfo ?? null) && isset($e->errorInfo[1])) {
            $errorCode = (int) $e->errorInfo[1];
        }

        // MySQL/MariaDB common idempotency codes
        if (in_array($errorCode, [
            1050, // table already exists
            1060, // duplicate column name
            1061, // duplicate key name
            1068, // multiple primary key defined
            1091, // can't drop; doesn't exist
            1826, // duplicate foreign key constraint name
        ], true)) {
            return true;
        }

        $message = $e->getMessage();

        return str_contains($message, 'already exists')
            || str_contains($message, 'Duplicate')
            || str_contains($message, 'Multiple primary key');
    }
}
