<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `ss_config` (
  `id` bigint(20) NOT NULL,
  `concepto` varchar(100) NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  `aplica` enum('trabajador','empresa') NOT NULL DEFAULT 'trabajador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `ss_config`
  ADD PRIMARY KEY (`id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `ss_config`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
