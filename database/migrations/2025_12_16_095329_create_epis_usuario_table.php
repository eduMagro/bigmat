<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `epis_usuario` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `epi_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `entregado_en` timestamp NULL DEFAULT NULL,
  `devuelto_en` timestamp NULL DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `epis_usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `epis_usuario_user_id_devuelto_en_index` (`user_id`,`devuelto_en`),
  ADD KEY `epis_usuario_epi_id_devuelto_en_index` (`epi_id`,`devuelto_en`),
  ADD KEY `epis_usuario_entregado_en_index` (`entregado_en`),
  ADD KEY `epis_usuario_devuelto_en_index` (`devuelto_en`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `epis_usuario`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
