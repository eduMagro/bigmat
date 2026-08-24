<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        // La tabla se creó en utf8 (3 bytes) y los mensajes con emojis
        // fallaban con "Incorrect string value" al insertar.
        $this->runSql(<<<'SQL'
ALTER TABLE `alertas` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL
        );
    }

    public function down(): void
    {
    }
};
