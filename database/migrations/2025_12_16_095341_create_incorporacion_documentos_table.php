<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `incorporacion_documentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `incorporacion_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `completado` tinyint(1) DEFAULT 0,
  `completado_at` timestamp NULL DEFAULT NULL,
  `subido_por` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `incorporacion_documentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incorporacion_tipo` (`incorporacion_id`,`tipo`),
  ADD KEY `fk_documentos_subido_por` (`subido_por`),
  ADD KEY `idx_documentos_incorporacion` (`incorporacion_id`),
  ADD KEY `idx_documentos_tipo` (`tipo`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `incorporacion_documentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
