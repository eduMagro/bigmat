<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `incorporacion_formaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `incorporacion_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('curso_20h_generico','curso_6h_ferralla','otros_cursos','formacion_generica_puesto','formacion_especifica_puesto') NOT NULL,
  `nombre` varchar(255) DEFAULT NULL COMMENT 'Nombre descriptivo del curso',
  `archivo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `incorporacion_formaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_formaciones_incorporacion` (`incorporacion_id`),
  ADD KEY `idx_formaciones_tipo` (`tipo`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `incorporacion_formaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
