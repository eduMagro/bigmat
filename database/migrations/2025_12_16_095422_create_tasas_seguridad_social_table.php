<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `tasas_seguridad_social` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `destinatario` enum('trabajador','empresa') NOT NULL,
  `tipo_aportacion` varchar(255) NOT NULL COMMENT 'Contingencias comunes, desempleo, formación, etc.',
  `porcentaje` decimal(5,4) NOT NULL COMMENT 'Tipo de cotización en porcentaje',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha de inicio de vigencia',
  `fecha_fin` date DEFAULT NULL COMMENT 'Fecha de fin de vigencia (NULL = vigente)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `tasas_seguridad_social`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destinatario` (`destinatario`),
  ADD KEY `fecha_inicio` (`fecha_inicio`),
  ADD KEY `fecha_fin` (`fecha_fin`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `tasas_seguridad_social`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
