<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `epi_compras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` varchar(255) NOT NULL DEFAULT 'pendiente',
  `comprada_en` timestamp NULL DEFAULT NULL,
  `ticket_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `epi_compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `epi_compras_user_id_foreign` (`user_id`),
  ADD KEY `epi_compras_estado_index` (`estado`),
  ADD KEY `epi_compras_comprada_en_index` (`comprada_en`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `epi_compras`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
